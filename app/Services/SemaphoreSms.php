<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Throwable;

class SemaphoreSms
{
    public function configured(): bool
    {
        return filled(config('services.semaphore.api_key'));
    }

    public function sendMessage(string $phone, string $message): ?string
    {
        return $this->send('messages', $phone, $message);
    }

    public function sendPasswordResetCode(string $phone, string $code): bool
    {
        return $this->send(
            'otp',
            $phone,
            "D'fitness Gym: Your password reset code is {otp}. It expires in 15 minutes. If you did not request this, ignore this message.",
            $code
        ) !== null;
    }

    private function send(string $endpoint, string $phone, string $message, ?string $code = null): ?string
    {
        if (! $this->configured()) return null;

        $payload = [
            'apikey' => config('services.semaphore.api_key'),
            'number' => ltrim($phone, '+'),
            'message' => $message,
        ];
        if (filled(config('services.semaphore.sender_name'))) {
            $payload['sendername'] = config('services.semaphore.sender_name');
        }
        if ($code !== null) $payload['code'] = $code;

        try {
            $response = Http::asForm()->acceptJson()->timeout(15)
                ->post("https://api.semaphore.co/api/v4/{$endpoint}", $payload);
            if (! $response->successful()) return null;

            $result = $response->json();
            $entry = is_array($result) ? ($result[0] ?? null) : null;
            if (! is_array($entry) || ! filled($entry['message_id'] ?? null)
                || in_array(strtolower((string) ($entry['status'] ?? '')), ['failed', 'refunded'], true)) return null;
            if ($code !== null && str_pad((string) ($entry['code'] ?? ''), 5, '0', STR_PAD_LEFT) !== $code) return null;

            return (string) $entry['message_id'];
        } catch (Throwable) {
            return null;
        }
    }
}
