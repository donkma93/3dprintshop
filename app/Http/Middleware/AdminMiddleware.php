<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isAdmin()) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền quản trị.',
                ], 403);
            }

            abort(403, 'Bạn không có quyền truy cập trang quản trị.');
        }

        $permission = null;
        $routeName = optional($request->route())->getName();

        if ($routeName && str_starts_with($routeName, 'admin.')) {
            $permission = \App\Support\Permission::forRoute($routeName);
        } elseif ($request->is('api/v1/admin/*') || $request->is('api/v1/admin')) {
            $permission = \App\Support\Permission::forApiPath($request->path());
        }

        if ($permission && ! $user->hasPermission($permission)) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền thực hiện thao tác này.',
                ], 403);
            }

            abort(403, 'Bạn không có quyền truy cập chức năng này.');
        }

        return $next($request);
    }
}
