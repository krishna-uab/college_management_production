<?php

namespace App\Http\Middleware;

use App\Models\SuperAdmin\FranchiseMaster;
use Closure;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Symfony\Component\HttpFoundation\Response;

class CheckFranchiseStatus
{

    public function handle(Request $request, Closure $next): Response
    {
        try {

            $payload = JWTAuth::getPayload();
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Unauthenticated. Please login again.',
            ], 401);
        }

        $type = (int) $payload->get('user_priviledge_id');

        if ($type === 0) {
            return $next($request);
        }

        if (!in_array($type, [1, 2, 3, 4], true)) {
            return response()->json([
                'status'  => false,
                'message' => 'Invalid user type.',
            ], 403);
        }

        $franchiseId = $payload->get('franchise_id');

        $franchise = FranchiseMaster::find($franchiseId);

        if (!$franchise) {
            return response()->json([
                'status'  => false,
                'message' => 'Franchise not found. Please contact support.',
            ], 403);
        }

        if ($franchise->status != true) {
            return response()->json([
                'status'  => false,
                'message' => 'Your service provider is not active.',
            ]);
        }

        return $next($request);
    }
}
