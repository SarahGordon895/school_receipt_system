<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    public function send(string $to, string $message): bool
    {
        $config = $this->resolveConfig();

        if (!$config['enabled']) {
            Log::info('SMS skipped (disabled in settings).', ['to' => $to]);

            return false;
        }

        if ($config['simulate']) {
            Log::info('SMS simulated (localhost/demo mode).', [
                'to' => $to,
                'sender_id' => $config['sender_id'],
                'message' => $message,
            ]);

            return true;
        }

        $endpoint = $config['endpoint'];
        $token = $config['token'];
        $sender = $config['sender_id'];

        if (!$endpoint || !$token) {
            Log::warning('SMS not sent: configure API endpoint and token in Settings or .env.', [
                'to' => $to,
            ]);

            return false;
        }

        $response = Http::timeout(15)
            ->withToken($token)
            ->post($endpoint, [
                'to' => $to,
                'message' => $message,
                'sender_id' => $sender,
            ]);

        if (!$response->successful()) {
            Log::error('SMS provider request failed.', [
                'to' => $to,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        }

        return true;
    }

    private function resolveConfig(): array
    {
        $setting = Setting::query()->first();

        if ($setting) {
            return $setting->smsConfig();
        }

        return [
            'enabled' => (bool) config('services.sms.endpoint') && (bool) config('services.sms.token'),
            'simulate' => app()->environment('local'),
            'endpoint' => config('services.sms.endpoint'),
            'token' => config('services.sms.token'),
            'sender_id' => config('services.sms.sender_id', 'SCHOOL'),
        ];
    }
}
