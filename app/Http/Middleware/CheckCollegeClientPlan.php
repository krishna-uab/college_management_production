<?php

namespace App\Http\Middleware;

use App\Models\SuperAdmin\CollegeClient;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Symfony\Component\HttpFoundation\Response;


class CheckCollegeClientPlan
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

        if (in_array($type, [0, 1], true)) {
            return $next($request);
        }

        if (!in_array($type, [2, 3, 4], true)) {
            return response()->json([
                'status'  => false,
                'message' => 'Invalid user type.',
            ], 403);
        }

        $collegeClientId = $payload->get('college_id');

        $college = CollegeClient::find($collegeClientId);

        if (!$college) {
            return response()->json([
                'status'  => false,
                'message' => 'College account not found. Please contact support.',
            ], 403);
        }

        if (!$college->status) {

            $message = match ($type) {
                2 => 'Your service provider account is inactive. Please contact support.',
                3 => 'Your login has been denied. Please contact your college administrator.',
                4 => 'Your login has been denied. Please contact your school administrator.',
            };

            return response()->json([
                'status'  => false,
                'message' => $message,
            ], 403);
        }

        if ($college->renewal_date) {

            $renewalDate = Carbon::parse($college->renewal_date);
            $gracePeriod = (int) ($college->grace_period ?? 0);

            $graceEndDate = $renewalDate
                ->copy()
                ->addDays($gracePeriod)
                ->endOfDay();

            if (now()->gt($graceEndDate)) {

                $message = match ($type) {
                    2 => 'The college plan has expired. Please contact your service provider.',
                    3 => 'College plan has expired. Please contact your college administrator.',
                    4 => 'College plan has expired. Please contact your school administrator.',
                };

                return response()->json([
                    'status'  => false,
                    'message' => $message,
                ], 403);
            }
        }

        return $next($request);
    }
}
