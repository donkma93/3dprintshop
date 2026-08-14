<?php

namespace App\Services;

use App\Models\ChatMessage;
use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Firebase Cloud Messaging (HTTP v1) — push khi app admin đóng / nền.
 *
 * Cấu hình: FCM_PROJECT_ID + FCM_CREDENTIALS (đường dẫn service account JSON).
 * Nếu thiếu cấu hình, service no-op (local poll vẫn hoạt động).
 */
class FcmPushService
{
    public function isConfigured(): bool
    {
        $project = (string) config('services.fcm.project_id', '');
        $credentials = (string) config('services.fcm.credentials', '');

        return $project !== '' && $credentials !== '' && is_readable($credentials);
    }

    public function notifyGuestChatMessage(ChatMessage $message): void
    {
        if ($message->sender !== 'guest') {
            return;
        }

        if (! $this->isConfigured()) {
            return;
        }

        $message->loadMissing('conversation');
        $conversation = $message->conversation;
        if (! $conversation) {
            return;
        }

        $guest = trim((string) ($conversation->guest_name ?: 'Khách'));
        $isFirstGuest = ChatMessage::query()
            ->where('conversation_id', $conversation->id)
            ->where('sender', 'guest')
            ->where('id', '<', $message->id)
            ->doesntExist();

        $title = $isFirstGuest ? "Chat mới · {$guest}" : "Tin nhắn từ {$guest}";
        $body = Str::limit(trim((string) $message->body), 120) ?: 'Có tin nhắn mới';
        if ($conversation->guest_phone) {
            $body = $conversation->guest_phone.' · '.$body;
        }

        $tokens = $this->adminChatTokens();
        if ($tokens === []) {
            return;
        }

        $data = [
            'type' => 'chat',
            'conversation_id' => (string) $conversation->id,
            'message_id' => (string) $message->id,
        ];

        foreach ($tokens as $token) {
            $this->sendToToken($token, $title, $body, $data);
        }
    }

    /**
     * @return list<string>
     */
    private function adminChatTokens(): array
    {
        $userIds = User::query()
            ->where('is_admin', true)
            ->where(function ($q) {
                $q->whereNull('is_active')->orWhere('is_active', true);
            })
            ->get()
            ->filter(fn (User $u) => $u->hasPermission('chat.manage'))
            ->pluck('id')
            ->all();

        if ($userIds === []) {
            return [];
        }

        return DeviceToken::query()
            ->whereIn('user_id', $userIds)
            ->pluck('token')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  array<string, string>  $data
     */
    public function sendToToken(string $token, string $title, string $body, array $data = []): bool
    {
        if (! $this->isConfigured() || $token === '') {
            return false;
        }

        $projectId = config('services.fcm.project_id');
        $accessToken = $this->accessToken();
        if ($accessToken === null) {
            return false;
        }

        $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

        $payload = [
            'message' => [
                'token' => $token,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'data' => $data,
                'android' => [
                    'priority' => 'high',
                    'notification' => [
                        'channel_id' => 'chat_messages',
                        'sound' => 'default',
                        'notification_priority' => 'PRIORITY_HIGH',
                        'default_vibrate_timings' => true,
                    ],
                ],
                'apns' => [
                    'headers' => [
                        'apns-priority' => '10',
                    ],
                    'payload' => [
                        'aps' => [
                            'sound' => 'default',
                            'badge' => 1,
                        ],
                    ],
                ],
            ],
        ];

        try {
            $response = Http::withToken($accessToken)
                ->acceptJson()
                ->timeout(12)
                ->post($url, $payload);

            if ($response->successful()) {
                return true;
            }

            $status = $response->status();
            $json = $response->json();
            $errorCode = data_get($json, 'error.details.0.errorCode')
                ?? data_get($json, 'error.status')
                ?? '';

            // Token hết hạn / gỡ app → xóa khỏi DB.
            if (in_array($status, [404, 400], true) ||
                in_array((string) $errorCode, ['UNREGISTERED', 'INVALID_ARGUMENT', 'NOT_FOUND'], true) ||
                str_contains(strtolower((string) data_get($json, 'error.message', '')), 'not found') ||
                str_contains(strtolower((string) data_get($json, 'error.message', '')), 'registration token')) {
                DeviceToken::where('token', $token)->delete();
            }

            Log::warning('FCM send failed', [
                'status' => $status,
                'body' => $response->body(),
                'token' => Str::limit($token, 24, '…'),
            ]);

            return false;
        } catch (\Throwable $e) {
            Log::warning('FCM send exception: '.$e->getMessage());

            return false;
        }
    }

    private function accessToken(): ?string
    {
        return Cache::remember('fcm_access_token', now()->addMinutes(50), function () {
            $path = (string) config('services.fcm.credentials');
            $sa = json_decode((string) file_get_contents($path), true);
            if (! is_array($sa) || empty($sa['client_email']) || empty($sa['private_key'])) {
                Log::error('FCM credentials JSON invalid (need client_email + private_key).');

                return null;
            }

            $now = time();
            $header = $this->b64url(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
            $claim = $this->b64url(json_encode([
                'iss' => $sa['client_email'],
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $now + 3600,
            ]));

            $unsigned = $header.'.'.$claim;
            $key = openssl_pkey_get_private($sa['private_key']);
            if ($key === false) {
                Log::error('FCM private_key openssl parse failed.');

                return null;
            }

            $signature = '';
            $ok = openssl_sign($unsigned, $signature, $key, OPENSSL_ALGO_SHA256);
            if (! $ok) {
                Log::error('FCM JWT sign failed.');

                return null;
            }

            $jwt = $unsigned.'.'.$this->b64url($signature);

            $response = Http::asForm()
                ->timeout(12)
                ->post('https://oauth2.googleapis.com/token', [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $jwt,
                ]);

            if (! $response->successful()) {
                Log::error('FCM OAuth token failed', ['body' => $response->body()]);

                return null;
            }

            $token = $response->json('access_token');

            return is_string($token) && $token !== '' ? $token : null;
        });
    }

    private function b64url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
