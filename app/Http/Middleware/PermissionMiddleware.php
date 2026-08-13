<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    /**
     * @param  string  ...$permissions  Một trong các quyền (OR). Hỗ trợ "a|b".
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isAdmin()) {
            return $this->deny($request, 'Bạn không có quyền quản trị.');
        }

        $needed = [];
        foreach ($permissions as $perm) {
            foreach (explode('|', $perm) as $p) {
                $p = trim($p);
                if ($p !== '') {
                    $needed[] = $p;
                }
            }
        }

        if ($needed === []) {
            return $next($request);
        }

        foreach ($needed as $permission) {
            if ($user->hasPermission($permission)) {
                return $next($request);
            }
        }

        return $this->deny($request, 'Bạn không có quyền thực hiện thao tác này.');
    }

    private function deny(Request $request, string $message): Response
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => false,
                'message' => $message,
            ], 403);
        }

        abort(403, $message);
    }
}
