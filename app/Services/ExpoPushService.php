<?php

namespace App\Services;

use App\Models\PushToken;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExpoPushService
{
    private const EXPO_PUSH_URL = 'https://exp.host/--/api/v2/push/send';

    /**
     * @param  array<string, mixed>  $data
     */
    public function sendToUser(User $user, string $title, string $body, array $data = []): void
    {
        $tokens = PushToken::query()
            ->where('user_id', $user->id)
            ->pluck('token');

        $this->sendToTokens($tokens, $title, $body, $data);
    }

    /**
     * @param  Collection<int, string>|list<string>  $tokens
     * @param  array<string, mixed>  $data
     */
    public function sendToTokens(Collection|array $tokens, string $title, string $body, array $data = []): void
    {
        $messages = collect($tokens)
            ->filter(fn (mixed $token): bool => is_string($token) && str_starts_with($token, 'ExponentPushToken['))
            ->unique()
            ->values()
            ->map(fn (string $token): array => [
                'to' => $token,
                'title' => $title,
                'body' => $body,
                'data' => $data,
                'sound' => 'default',
                'priority' => 'high',
                'channelId' => 'orders',
            ])
            ->all();

        if ($messages === []) {
            return;
        }

        foreach (array_chunk($messages, 100) as $chunk) {
            $this->dispatchChunk($chunk);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $messages
     */
    private function dispatchChunk(array $messages): void
    {
        try {
            $response = Http::acceptJson()
                ->timeout(10)
                ->post(self::EXPO_PUSH_URL, $messages);

            if (! $response->successful()) {
                Log::warning('Expo push request failed.', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return;
            }

            $payload = $response->json('data');

            if (! is_array($payload)) {
                return;
            }

            foreach ($payload as $index => $ticket) {
                if (! is_array($ticket)) {
                    continue;
                }

                $status = $ticket['status'] ?? null;
                $details = is_array($ticket['details'] ?? null) ? $ticket['details'] : [];
                $error = $details['error'] ?? null;

                if ($status === 'error' && $error === 'DeviceNotRegistered') {
                    $token = $messages[$index]['to'] ?? null;

                    if (is_string($token)) {
                        PushToken::query()->where('token', $token)->delete();
                    }
                }
            }
        } catch (\Throwable $exception) {
            Log::warning('Expo push dispatch failed.', [
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
