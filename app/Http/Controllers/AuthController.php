<?php

namespace App\Http\Controllers;

use App\Models\SuperAdmin\CollegeAppUser;
use App\Models\SuperAdmin\CollegeClient;
use App\Models\SuperAdmin\FranchiseMaster;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    private const TYPE_SUPERADMIN = 0;
    private const TYPE_FRANCHISE = 1;
    private const TYPE_COLLEGE   = 2;
    private const TYPE_STAFF     = 3;
    private const TYPE_STUDENT   = 4;

    public function login(Request $request)
    {


        // return response()->json([
        //     'status'  => false,
        //     'message' => 'Done here',
        // ], 500);

        $validator = Validator::make($request->all(), [
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
            'type'     => ['required', 'integer', 'in:0,1,2,3,4'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $username = trim($request->input('username'));
        $password = $request->input('password');
        $type     = (int) $request->input('type');

        return match ($type) {
            self::TYPE_SUPERADMIN => $this->superAdminLogin(
                $username,
                $password
            ),

            self::TYPE_FRANCHISE => $this->franchiseLogin(
                $username,
                $password
            ),

            self::TYPE_COLLEGE => $this->collegeLogin(
                $username,
                $password
            ),

            self::TYPE_STAFF => $this->staffLogin(
                $username,
                $password
            ),

            self::TYPE_STUDENT => $this->studentLogin(
                $username,
                $password
            ),
        };
    }

    private function superAdminLogin(string $username, string  $password): JsonResponse
    {

        $loginColumn = filter_var($username, FILTER_VALIDATE_EMAIL) ? 'email' : 'mobile_no';

        $user = CollegeAppUser::query()
            ->where($loginColumn, $username)
            ->where('user_privilege_id', self::TYPE_SUPERADMIN)
            ->whereNull('deleted_at')
            ->first();

        if (!$user || !Hash::check($password, $user->password)) {
            return response()->json([
                'status'  => false,
                'message' => 'Invalid credentials.',
            ], 401);
        }

        $tokenData = [
            "user_privilege_id" => $user->user_privilege_id,
            "mobile_no" => $user->mobile_no,
            "email" => $user->email,
        ];

        return $this->generateJWTToken($user, $tokenData);
    }

    private function franchiseLogin(string $username, string $password): JsonResponse
    {
        $loginColumn = filter_var($username, FILTER_VALIDATE_EMAIL) ? 'email' : 'mobile_no';

        $user = CollegeAppUser::query()
            ->where($loginColumn, $username)
            ->where('user_privilege_id', self::TYPE_FRANCHISE)
            ->whereNull('deleted_at')
            ->first();

        if (!$user || !Hash::check($password, $user->password)) {
            return response()->json([
                'status'  => false,
                'message' => 'Invalid credentials.',
            ], 401);
        }

        if ((int) $user->is_active != 1) {
            return response()->json([
                'status'  => false,
                'message' => 'Your account is inactive. Please contact administrator.',
            ], 403);
        }

        $franchise = FranchiseMaster::findOrFail($user->registration_id);

        if (!$franchise->status) {
            return response()->json([
                'status'  => false,
                'message' => 'Your franchise account is inactive. Please contact the administrator.',
            ], 403);
        }

        if ($franchise) {
            $user->login_status = 1;
            $user->save();
        }

        $tokenData = [
            'user_privilege_id' => $user->user_privilege_id,
            'franchise_id'      => $franchise->id,
            'franchise_name'    => $franchise->franchise_name,
            'owner'             => $franchise->owner_name,
            'mobile_no'         => $user->owner_phone,
            'email'             => $user->owner_email,
        ];

        return $this->generateJWTToken($user, $tokenData);
    }

    private function collegeLogin(string $username, string $password): JsonResponse
    {
        $loginColumn = filter_var($username, FILTER_VALIDATE_EMAIL) ? 'email' : 'mobile_no';

        $user = CollegeAppUser::query()
            ->where($loginColumn, $username)
            ->where('user_privilege_id', self::TYPE_COLLEGE)
            ->whereNull('deleted_at')
            ->first();

        if (!$user || !Hash::check($password, $user->password)) {
            return response()->json([
                'status'  => false,
                'message' => 'Invalid credentials.',
            ], 401);
        }

        if ((int) $user->is_active !== 1) {
            return response()->json([
                'status'  => false,
                'message' => 'Your account is inactive. Please contact administrator.',
            ], 403);
        }

        $collegeDetails = $this->checkCollegePlan($user->college_id);

        if (!$collegeDetails['valid']) {
            return response()->json([
                'status'  => false,
                'message' => $collegeDetails['message'],
            ], 403);
        }

        // $college

        $college = $collegeDetails['college'];

        $tokenData = [
            "user_privilege_id" => $user->user_privilege_id,
            "college_name" => $college->name,
            "college_id" => $college->id,
            "franchise_id" => $college->franchise_id,
            "group" => $college->group,
            "user_name" => $college->name,
            "mobile_no" => $user->user_privilege_id,
            "email" => $user->email,
        ];

        return $this->generateJWTToken($user, $tokenData);
    }

    private function staffLogin(string $username, string $password)
    {
        $loginColumn = filter_var($username, FILTER_VALIDATE_EMAIL) ? 'email' : 'mobile_no';

        $user = CollegeAppUser::query()
            ->where($loginColumn, $username)
            ->where('user_privilege_id', self::TYPE_STAFF)
            ->whereNull('deleted_at')
            ->first();

        if (!$user || !Hash::check($password, $user->password)) {
            return response()->json([
                'status'  => false,
                'message' => 'Invalid credentials.',
            ], 401);
        }

        if ((int) $user->is_active !== 1) {
            return response()->json([
                'status'  => false,
                'message' => 'Your account is inactive. Please contact administrator.',
            ], 403);
        }

        $collegeDetails = $this->checkCollegePlan($user->college_id);

        if (!$collegeDetails['valid']) {
            return response()->json([
                'status'  => false,
                'message' => $collegeDetails['message'],
            ], 403);
        }


        $college = $collegeDetails['college'];


        // return $this->generateLoginResponse($user);
    }

    private function studentLogin(string $username, string $password)
    {
        $loginColumn = filter_var($username, FILTER_VALIDATE_EMAIL) ? 'email' : 'mobile_no';

        $user = CollegeAppUser::query()
            ->where($loginColumn, $username)
            ->where('user_privilege_id', self::TYPE_STUDENT)
            ->whereNull('deleted_at')
            ->first();

        if (!$user || !Hash::check($password, $user->password)) {
            return response()->json([
                'status'  => false,
                'message' => 'Invalid credentials.',
            ], 401);
        }

        if ((int) $user->is_active !== 1) {
            return response()->json([
                'status'  => false,
                'message' => 'Your account is inactive. Please contact administrator.',
            ], 403);
        }

        $collegeDetails = $this->checkCollegePlan($user->college_id);

        if (!$collegeDetails['valid']) {
            return response()->json([
                'status'  => false,
                'message' => $collegeDetails['message'],
            ], 403);
        }

        // franchise_id jwt mai rkhana hai dhyan rhe

        // return $this->generateLoginResponse($user);
    }

    private function checkCollegePlan(?int $collegeId): array
    {
        if (!$collegeId) {
            return [
                'valid'   => false,
                'message' => 'College is not assigned to this user.',
            ];
        }

        $college = CollegeClient::query()
            ->where('id', $collegeId)
            ->where('status', 1)
            ->where(function ($query) {
                $query->whereNull('renewal_date')
                    ->orWhereDate('renewal_date', '>=', now()->toDateString());
            })
            ->first();

        if (!$college) {
            return [
                'valid'   => false,
                'message' => 'College is inactive. Please contact administrator.',
            ];
        }

        return [
            'valid'   => true,
            'message' => 'College plan is valid.',
            'college' => $college
        ];
    }

    private function generateJWTToken(
        CollegeAppUser $user,
        array $userData = []
    ): JsonResponse {

        $ttlMinutes = 60 * 24 * 180; // 180 days = 259200 minutes

        JWTAuth::factory()->setTTL($ttlMinutes);

        $token = JWTAuth::claims($userData)->fromUser($user);

        if (empty($token)) {
            return response()->json([
                'status'  => false,
                'message' => 'Login failed | Token not generated',
            ], 500);
        }

        $userData['is_authenticated'] = true;
        $userData['expires_at']       = now()->addMinutes($ttlMinutes)->timestamp;

        $isProduction = app()->environment('production');

        $cookie = cookie(
            'access_token',                  // name
            $token,                          // value
            $ttlMinutes,                     // minutes
            '/',                             // path
            null,                            // domain
            $isProduction,                   // secure (localhost par false)
            true,                            // httpOnly
            false,                           // raw
            $isProduction ? 'None' : 'Lax'   // sameSite
        );

        return response()->json([
            'status'    => true,
            'message'   => 'Login successful',
            'user_data' => $this->encryptUserData($userData),
        ])->withCookie($cookie);
    }

    public function getUser()
    {
        try {

            $user = Auth::guard('api')->user();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthenticated. Please login again.',
                ], 401);
            }

            // Middleware already set the JWT token.
            $payload = JWTAuth::getPayload();

            return response()->json([
                'status' => true,
                'message' => 'User details fetched successfully',

                'user' => [
                    'id' => $user->id,
                    'mobile_no' => $user->mobile_no,
                    'college_id' => $user->college_id,
                    'user_privilege_id' => $user->user_privilege_id,
                ],

                'token_data' => [
                    'user_id' => $payload->get('user_id'),
                    'college_id' => $payload->get('college_id'),
                    'registration_id' => $payload->get('registration_id'),
                    'user_privilege_id' => $payload->get('user_privilege_id'),
                    'check_flag' => $payload->get('check_flag'),
                    'issued_at' => $payload->get('iat'),
                    'expires_at' => $payload->get('exp'),
                ],
            ]);
        } catch (\PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException $e) {

            return response()->json([
                'status' => false,
                'message' => 'Token expired. Please login again.',
            ], 401);
        } catch (\PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException $e) {

            return response()->json([
                'status' => false,
                'message' => 'Invalid or missing token.',
            ], 401);
        }
    }

    public function logout(): JsonResponse
    {
        try {
            Auth::guard('api')->logout();

            return response()->json([
                'status'  => true,
                'message' => 'Logged out successfully.',
            ])->withCookie(cookie()->forget('access_token'));
        } catch (JWTException $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Unable to logout. Please check your token.',
            ], 401)->withCookie(cookie()->forget('access_token'));
        }
    }

    // public function refresh()
    // {
    //     try {
    //         $oldToken = JWTAuth::getToken();

    //         if (!$oldToken) {
    //             return response()->json([
    //                 'status' => false,
    //                 'message' => 'Token not provided.',
    //             ], 401);
    //         }

    //         $token = JWTAuth::refresh($oldToken);

    //         JWTAuth::setToken($token);

    //         $payload = JWTAuth::getPayload();

    //         $user = JWTAuth::authenticate();

    //         if (!$user) {
    //             JWTAuth::invalidate($token);

    //             return response()->json([
    //                 'status' => false,
    //                 'message' => 'User not found.',
    //             ], 401);
    //         }

    //         $collegeDetails = $this->checkCollegePlan($user->college_id);


    //         if (!$collegeDetails['valid']) {
    //             JWTAuth::invalidate($token);
    //             return response()->json([
    //                 'status' => false,
    //                 'message' => $collegeDetails['message'],
    //             ], 403);
    //         }

    //         return response()->json([
    //             'status' => true,
    //             'message' => 'Token refreshed successfully.',
    //             'token' => $token,
    //             'token_type' => 'Bearer',
    //             'expires_in' => auth('api')->factory()->getTTL() * 60,
    //         ]);
    //     } catch (JWTException $e) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Token expired or invalid. Please login again.',
    //         ], 401);
    //     }
    // }


    public function refresh(): JsonResponse
    {
        try {

            // 1. Cookie se existing JWT token read karo
            $oldToken = request()->cookie('access_token');

            if (empty($oldToken)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Token not provided. Please login again.',
                ], 401)->withCookie(cookie()->forget('access_token'));
            }

            // 2. Old token set karo
            JWTAuth::setToken($oldToken);

            // 3. Token refresh karke new token generate karo
            $token = JWTAuth::refresh($oldToken);

            if (empty($token)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unable to refresh token. Please login again.',
                ], 401)->withCookie(cookie()->forget('access_token'));
            }

            // 4. New token set karo
            JWTAuth::setToken($token);

            // 5. New token se user authenticate karo
            $user = JWTAuth::authenticate();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'User not found. Please login again.',
                ], 401)->withCookie(cookie()->forget('access_token'));
            }

            // 6. College plan validate karo
            if (in_array((int) $user->user_privilege_id, [
                self::TYPE_COLLEGE,
                self::TYPE_STAFF,
                self::TYPE_STUDENT
            ], true)) {

                $collegeDetails = $this->checkCollegePlan($user->college_id);

                if (!$collegeDetails['valid']) {

                    return response()->json([
                        'status' => false,
                        'message' => $collegeDetails['message'],
                    ], 403)->withCookie(cookie()->forget('access_token'));
                }
            }

            // 7. New cookie generate karo
            $ttlMinutes = auth('api')->factory()->getTTL();

            $isProduction = app()->environment('production');

            $cookie = cookie(
                'access_token',
                $token,
                $ttlMinutes,
                '/',
                null,
                $isProduction,
                true,
                false,
                $isProduction ? 'None' : 'Lax'
            );

            // 8. Success response
            return response()->json([
                'status' => true,
                'message' => 'Token refreshed successfully.',
                'expires_in' => $ttlMinutes * 60,
            ])->withCookie($cookie);
        } catch (\Throwable $e) {

            Log::error('JWT Refresh Error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Token expired or invalid. Please login again.',
            ], 401)->withCookie(cookie()->forget('access_token'));
        }
    }

    private function encryptUserData(array $data): string
    {
        $key = hash('sha256', config('app.frontend_crypto_key'), true);
        $iv  = random_bytes(12);
        $tag = '';

        $cipher = openssl_encrypt(
            json_encode($data),
            'aes-256-gcm',
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '',
            16
        );

        return base64_encode($iv . $cipher . $tag);
    }
}
