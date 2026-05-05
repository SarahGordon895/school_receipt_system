<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    public function send(string $to, string $message): bool
    {
        $endpoint = config('services.sms.endpoint');
        $token = config('services.sms.token');
        $sender = config('services.sms.sender_id', 'SCHOOL');

        if (!$endpoint || !$token) {
            Log::warning('SMS not sent: missing SMS endpoint/token configuration.', [
                'to' => $to,
                'message' => $message,
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
}
