<?php

namespace App\Services;

use App\Models\PushToken;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Pengirim notifikasi Firebase Cloud Messaging (HTTP v1) ke perangkat APK.
 *
 * Kredensial diambil dari service account Firebase: letakkan JSON-nya di
 * `storage/app/firebase/service-account.json` atau isi env
 * `FCM_CREDENTIALS_BASE64` dengan isi file itu dalam base64. Selama belum
 * diisi, seluruh metode di sini tidak melakukan apa-apa (aman).
 */
class FcmService
{
    public function terkonfigurasi(): bool
    {
        return $this->projectId() !== '' && $this->kredensial() !== null;
    }

    /**
     * Kirim ke semua token milik satu user. Mengembalikan jumlah yang sukses.
     */
    public function kirimKeUser(int $userId, string $judul, string $pesan, array $data = []): int
    {
        $tokens = PushToken::query()->where('user_id', $userId)->pluck('token')->all();

        return $this->kirim($tokens, $judul, $pesan, $data);
    }

    /**
     * Kirim ke sekumpulan id user.
     */
    public function kirimKeUsers(iterable $userIds, string $judul, string $pesan, array $data = []): int
    {
        $tokens = PushToken::query()
            ->whereIn('user_id', Collection::make($userIds)->all())
            ->pluck('token')
            ->all();

        return $this->kirim($tokens, $judul, $pesan, $data);
    }

    /**
     * Kirim ke daftar token. Token yang sudah tidak terdaftar dihapus.
     */
    public function kirim(array $tokens, string $judul, string $pesan, array $data = []): int
    {
        $tokens = array_values(array_filter(array_unique($tokens)));

        if ($tokens === [] || ! $this->terkonfigurasi()) {
            return 0;
        }

        $access = $this->accessToken();

        if ($access === null) {
            return 0;
        }

        $endpoint = sprintf(
            'https://fcm.googleapis.com/v1/projects/%s/messages:send',
            $this->projectId(),
        );

        $sukses = 0;

        foreach ($tokens as $token) {
            $payload = [
                'message' => [
                    'token' => $token,
                    'notification' => ['title' => $judul, 'body' => $pesan],
                    'data' => array_map('strval', $data),
                    'android' => [
                        'priority' => 'high',
                        'notification' => ['sound' => 'default'],
                    ],
                ],
            ];

            try {
                $res = Http::withToken($access)
                    ->acceptJson()
                    ->post($endpoint, $payload);
            } catch (\Throwable $e) {
                Log::warning('FCM kirim gagal: '.$e->getMessage());

                continue;
            }

            if ($res->successful()) {
                $sukses++;

                continue;
            }

            $status = $res->json('error.status');

            if (in_array($status, ['NOT_FOUND', 'UNREGISTERED', 'INVALID_ARGUMENT'], true)) {
                PushToken::query()->where('token', $token)->delete();
            }

            Log::warning('FCM menolak token', ['status' => $res->status(), 'body' => $res->json()]);
        }

        return $sukses;
    }

    private function accessToken(): ?string
    {
        return Cache::remember('fcm.access_token', 3300, function (): ?string {
            $kred = $this->kredensial();

            if ($kred === null || empty($kred['client_email']) || empty($kred['private_key'])) {
                return null;
            }

            $now = time();
            $header = $this->base64url(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
            $claims = $this->base64url(json_encode([
                'iss' => $kred['client_email'],
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $now + 3600,
            ]));

            $signingInput = $header.'.'.$claims;
            $signature = '';

            if (! openssl_sign($signingInput, $signature, $kred['private_key'], OPENSSL_ALGO_SHA256)) {
                Log::warning('FCM: gagal menandatangani JWT.');

                return null;
            }

            $jwt = $signingInput.'.'.$this->base64url($signature);

            $res = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

            if (! $res->successful()) {
                Log::warning('FCM: gagal ambil access token', ['body' => $res->json()]);

                return null;
            }

            $token = $res->json('access_token');

            return $token ? (string) $token : null;
        });
    }

    private function projectId(): string
    {
        $dariConfig = (string) config('services.fcm.project_id', '');

        if ($dariConfig !== '') {
            return $dariConfig;
        }

        return (string) ($this->kredensial()['project_id'] ?? '');
    }

    /** @return array<string, mixed>|null */
    private function kredensial(): ?array
    {
        $path = config('services.fcm.credentials');

        if (is_string($path) && $path !== '' && is_file($path)) {
            $json = json_decode((string) file_get_contents($path), true);

            if (is_array($json)) {
                return $json;
            }
        }

        $b64 = config('services.fcm.credentials_base64');

        if (is_string($b64) && $b64 !== '') {
            $json = json_decode((string) base64_decode($b64, true), true);

            if (is_array($json)) {
                return $json;
            }
        }

        return null;
    }

    private function base64url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
