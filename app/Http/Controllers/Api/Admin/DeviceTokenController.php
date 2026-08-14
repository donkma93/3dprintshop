<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Models\DeviceToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceTokenController extends ApiController
{
    /**
     * Đăng ký / cập nhật FCM token của thiết bị admin hiện tại.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'max:512'],
            'platform' => ['nullable', 'string', 'max:32'],
            'device_name' => ['nullable', 'string', 'max:120'],
        ]);

        $user = $request->user();
        $token = trim($data['token']);

        $row = DeviceToken::query()->where('token', $token)->first();
        if ($row) {
            $row->user_id = $user->id;
            $row->platform = $data['platform'] ?? $row->platform ?? 'android';
            $row->device_name = $data['device_name'] ?? $row->device_name;
            $row->last_used_at = now();
            $row->save();
        } else {
            $row = DeviceToken::create([
                'user_id' => $user->id,
                'token' => $token,
                'platform' => $data['platform'] ?? 'android',
                'device_name' => $data['device_name'] ?? null,
                'last_used_at' => now(),
            ]);
        }

        return $this->ok([
            'id' => $row->id,
            'platform' => $row->platform,
        ], 'Đã đăng ký thiết bị nhận thông báo.');
    }

    /**
     * Hủy đăng ký token (logout).
     */
    public function destroy(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'max:512'],
        ]);

        DeviceToken::query()
            ->where('user_id', $request->user()->id)
            ->where('token', trim($data['token']))
            ->delete();

        return $this->ok(null, 'Đã hủy đăng ký thông báo trên thiết bị này.');
    }
}
