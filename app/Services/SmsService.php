<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    public function send(string $to, string $message): SmsSendResult
    {
        $config = $this->resolveConfig();
        $recipient = $this->normalizeRecipient($to);

        if (! $config['enabled']) {
            Log::info('SMS skipped (disabled in settings).', ['to' => $to]);

            return SmsSendResult::skipped('SMS disabled in Admin → Settings.', $recipient);
        }

        if ($config['simulate']) {
            Log::info('SMS simulated (localhost/demo mode).', [
                'to' => $to,
                'sender_id' => $config['sender_id'],
                'message' => $message,
            ]);

            return SmsSendResult::skipped(
                'SMS simulated (logged only — turn off Simulate SMS in Settings for live delivery).',
                $recipient
            );
        }

        $endpoint = $config['endpoint'];
        $token = $config['token'];
        $sender = $config['sender_id'];

        if (! $endpoint || ! $token) {
            Log::warning('SMS not sent: configure API endpoint and token in Settings or .env.', [
                'to' => $to,
            ]);

            return SmsSendResult::failed('SMS API endpoint or token not configured.', $recipient);
        }

        $payload = $this->buildPayload($config['driver'], $recipient, $message, $sender);

        $response = Http::timeout(20)
            ->acceptJson()
            ->withToken($token)
            ->post($endpoint, $payload);

        $body = $response->json() ?? [];
        $providerStatus = strtolower((string) ($body['status'] ?? ''));
        $uid = (string) data_get($body, 'data.uid', '');

        if (! $response->successful() || $providerStatus === 'error') {
            Log::error('SMS provider request failed.', [
                'to' => $to,
                'recipient' => $recipient,
                'driver' => $config['driver'],
                'sender_id' => $sender,
                'status' => $response->status(),
                'body' => $response->body(),
                'payload' => $payload,
            ]);

            return SmsSendResult::failed(
                'SMS provider rejected the request. Check sender ID ('.$sender.') and API token.',
                $recipient,
                $uid ?: null
            );
        }

        $delivery = $uid !== '' ? $this->fetchDeliveryStatus($token, $uid) : null;
        $deliveryStatus = (string) ($delivery['status'] ?? 'accepted');
        $detail = 'SMS submitted to '.$recipient.' via sender '.$sender.'.';

        if ($delivery) {
            $delivered = (int) ($delivery['delivered_count'] ?? 0);
            $failed = (int) ($delivery['failed_count'] ?? 0);

            if ($delivered > 0) {
                $detail .= ' Carrier reports delivered.';
            } elseif ($failed > 0) {
                $detail .= ' Carrier initial report: '.$deliveryStatus.' (message may still arrive).';
            } else {
                $detail .= ' Gateway status: '.$deliveryStatus.'.';
            }
        }

        Log::info('SMS accepted by provider.', [
            'to' => $to,
            'recipient' => $recipient,
            'driver' => $config['driver'],
            'sender_id' => $sender,
            'uid' => $uid,
            'delivery_status' => $deliveryStatus,
            'delivered_count' => $delivery['delivered_count'] ?? null,
            'failed_count' => $delivery['failed_count'] ?? null,
        ]);

        return SmsSendResult::sent($detail, $recipient, $uid ?: null, $deliveryStatus ?: null);
    }

    /** @return array{status: string, delivered_count: int, failed_count: int, detail: string}|null */
    public function checkDelivery(string $uid): ?array
    {
        $token = $this->resolveConfig()['token'];

        if (! $token || $uid === '') {
            return null;
        }

        $delivery = $this->fetchDeliveryStatus($token, $uid, waitSeconds: 0);

        if ($delivery === null) {
            return null;
        }

        $status = strtolower((string) ($delivery['status'] ?? 'unknown'));
        $delivered = (int) ($delivery['delivered_count'] ?? 0);
        $failed = (int) ($delivery['failed_count'] ?? 0);

        return [
            'status' => $status,
            'delivered_count' => $delivered,
            'failed_count' => $failed,
            'detail' => $delivered > 0
                ? 'Carrier confirms delivery.'
                : ($failed > 0
                    ? 'Carrier reports '.$status.'.'
                    : 'Gateway status: '.$status.'.'),
        ];
    }

    /** @param array<string, mixed>|null $delivery */
    public function deliveryIndicatesSuccess(?array $delivery): bool
    {
        if ($delivery === null) {
            return true;
        }

        if ((int) ($delivery['delivered_count'] ?? 0) > 0) {
            return true;
        }

        $status = strtolower((string) ($delivery['status'] ?? ''));

        return in_array($status, ['delivered', 'success', 'sent', 'submitted', 'accepted', 'queued', 'pending', 'processing'], true);
    }

    /** @return array<string, mixed> */
    private function buildPayload(string $driver, string $recipient, string $message, string $sender): array
    {
        return match ($driver) {
            'imart' => [
                'recipient' => $recipient,
                'sender_id' => $sender,
                'type' => 'plain',
                'message' => $message,
            ],
            default => [
                'to' => $recipient,
                'message' => $message,
                'sender_id' => $sender,
            ],
        };
    }

    /** @return array<string, mixed>|null */
    private function fetchDeliveryStatus(string $token, string $uid, int $waitSeconds = 1): ?array
    {
        $base = rtrim((string) config('services.sms.endpoint'), '/');
        $base = preg_replace('#/send$#', '', $base) ?? $base;
        $statusUrl = $base.'/'.$uid;

        try {
            if ($waitSeconds > 0) {
                sleep($waitSeconds);
            }

            $response = Http::timeout(15)
                ->acceptJson()
                ->withToken($token)
                ->get($statusUrl);

            if (! $response->successful()) {
                return null;
            }

            return $response->json('data');
        } catch (\Throwable $e) {
            Log::warning('SMS delivery status check failed.', [
                'uid' => $uid,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function normalizeRecipient(string $to): string
    {
        $digits = preg_replace('/\D+/', '', $to) ?? '';

        if ($digits === '') {
            return $to;
        }

        if (str_starts_with($digits, '0')) {
            return '255'.substr($digits, 1);
        }

        if (str_starts_with($digits, '255')) {
            return $digits;
        }

        return '255'.$digits;
    }

    /** @return array<string, mixed> */
    private function resolveConfig(): array
    {
        $driver = (string) config('services.sms.driver', 'generic');
        $endpoint = config('services.sms.endpoint');
        $token = config('services.sms.token');
        $envSender = strtoupper((string) config('services.sms.sender_id', 'SCHOOL'));
        $hasLiveSms = filled($endpoint) && filled($token);

        try {
            $setting = Setting::query()->first();
        } catch (\Throwable $e) {
            Log::warning('SMS settings DB unavailable; using .env configuration.', [
                'error' => $e->getMessage(),
            ]);
            $setting = null;
        }

        if ($setting) {
            $dbConfig = $setting->smsConfig();

            return [
                'enabled' => (bool) $dbConfig['enabled'],
                'simulate' => (bool) $dbConfig['simulate'],
                'driver' => $driver,
                'endpoint' => $dbConfig['endpoint'] ?: $endpoint,
                'token' => $dbConfig['token'] ?: $token,
                'sender_id' => strtoupper((string) ($dbConfig['sender_id'] ?: $envSender)),
            ];
        }

        return [
            'enabled' => $hasLiveSms,
            'simulate' => ! $hasLiveSms,
            'driver' => $driver,
            'endpoint' => $endpoint,
            'token' => $token,
            'sender_id' => $envSender,
        ];
    }
}
