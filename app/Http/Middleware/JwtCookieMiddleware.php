<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Symfony\Component\HttpFoundation\Response;

class JwtCookieMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->cookie('access_token');


        try {
            JWTAuth::setToken($token);

            Log::info('JWT token set successfully');

            $payload = JWTAuth::getPayload();

            Log::info('JWT payload validated', [
                'sub' => $payload->get('sub'),
                'exp' => $payload->get('exp'),
                'user_privilege_id' => $payload->get('user_privilege_id'),
            ]);

            $user = JWTAuth::authenticate();

            Log::info('JWT user authentication result', [
                'user_found' => !empty($user),
                'user_id' => $user?->id,
            ]);

            if (!$user) {
                return response()->json([
                    'message' => 'JWT valid but user not found.',
                ], 401);
            }

            Auth::guard('api')->setUser($user);
            Auth::shouldUse('api');

            return $next($request);
        } catch (\Throwable $e) {

            Log::error('JWT AUTHENTICATION ERROR', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'message' => 'JWT authentication failed.',
            ], 401);
        }
    }
}
