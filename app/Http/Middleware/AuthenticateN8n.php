<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateN8n
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) config('services.n8n.api_key', '');

        if ($expected === '') {
            return new JsonResponse([
                'success' => false,
                'message' => 'N8N_API_KEY chưa được cấu hình trên server.',
            ], 503);
        }

        $provided = $request->bearerToken() ?: $request->header('X-N8N-Key', '');

        if (! is_string($provided) || $provided === '' || ! hash_equals($expected, $provided)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Thông tin xác thực automation không hợp lệ.',
            ], 401);
        }

        return $next($request);
    }
}
