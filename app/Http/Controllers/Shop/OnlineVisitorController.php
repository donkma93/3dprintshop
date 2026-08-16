<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\OnlineVisitor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class OnlineVisitorController extends Controller
{
    private const ACTIVE_FOR_SECONDS = 90;

    public function heartbeat(Request $request): JsonResponse
    {
        $data = $request->validate(['visitor_token' => ['required', 'string', 'size:36']]);
        $now = Carbon::now();

        OnlineVisitor::updateOrCreate(
            ['visitor_token' => $data['visitor_token']],
            ['last_seen_at' => $now]
        );

        $cutoff = $now->copy()->subSeconds(self::ACTIVE_FOR_SECONDS);
        OnlineVisitor::where('last_seen_at', '<', $cutoff)->delete();

        return response()->json([
            'online' => OnlineVisitor::where('last_seen_at', '>=', $cutoff)->count(),
        ]);
    }
}
