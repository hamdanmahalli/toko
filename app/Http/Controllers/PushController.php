<?php

namespace App\Http\Controllers;

use App\Models\PushToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Pendaftaran token FCM dari perangkat Android (APK).
 *
 * Dipanggil oleh `public/native-bridge.js` setelah login berhasil. Token
 * berlaku per pemasangan aplikasi, jadi satu akun bisa punya beberapa token
 * bila dipasang di beberapa perangkat.
 */
class PushController extends Controller
{
    public function simpan(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'max:255'],
            'platform' => ['nullable', 'string', 'max:16'],
            'device_id' => ['nullable', 'string', 'max:64'],
        ]);

        PushToken::query()->updateOrCreate(
            ['token' => $data['token']],
            [
                'user_id' => $request->user()->getKey(),
                'platform' => $data['platform'] ?? 'android',
                'device_id' => $data['device_id'] ?? null,
                'last_seen_at' => now(),
            ],
        );

        return response()->json(['ok' => true]);
    }

    /**
     * Cabut token perangkat ini (dipanggil saat keluar bila diinginkan).
     */
    public function hapus(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'max:255'],
        ]);

        PushToken::query()
            ->where('user_id', $request->user()->getKey())
            ->where('token', $data['token'])
            ->delete();

        return response()->json(['ok' => true]);
    }
}
