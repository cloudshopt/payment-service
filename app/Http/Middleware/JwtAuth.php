<?php

namespace App\Http\Middleware;

use App\Services\JwtService;
use Closure;
use Illuminate\Http\Request;

class JwtAuth
{
    public function handle(Request $request, Closure $next)
    {
        $authHeader = (string) $request->header('Authorization', '');

        if (!str_starts_with($authHeader, 'Bearer ')) {
            return response()->json(['message' => 'Missing Bearer token'], 401);
        }

        $token = trim(substr($authHeader, 7));

        try {
            $claims = app(JwtService::class)->decode($token);
            $userId = $claims['sub'] ?? null;

            if (!$userId) {
                return response()->json(['message' => 'Invalid token'], 401);
            }

            $request->attributes->set('user_id', (int) $userId);

            return $next($request);
        } catch (\Throwable) {
            return response()->json(['message' => 'Invalid or expired token'], 401);
        }
    }
}