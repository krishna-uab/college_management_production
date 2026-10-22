<?php

namespace App\Http\Controllers;

use App\Models\SuperAdmin\AllCollege;
use App\Models\SuperAdmin\CollegeAppUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    private const TYPE_COMPANY = 0;
    private const TYPE_FRANCHISE = 1;
    private const TYPE_COLLEGE   = 2;
    private const TYPE_STAFF     = 3;
    private const TYPE_STUDENT   = 4;

    public function login(Request $request)
    {
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

        if ((int) $user->is_active !== 1) {
            return response()->json([
                'status'  => false,
                'message' => 'Your account is inactive. Please contact administrator.',
            ], 403);
        }

        return $this->generateLoginResponse($user);
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
            "db_name" => $college->db_name,
            "TENANT_DB_USERNAME" => $college->db_user,
            "TENANT_DB_PASSWORD" => $college->db_password,
            "user_name" => $college->name,
            "mobile_no" => $user->user_privilege_id,
            "email" => $user->email,
        ];

        return $this->generateJWTToken($user, $tokenData);

        // return $this->generateLoginResponse($user);
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

        $college = AllCollege::query()
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

    private function generateJWTToken(CollegeAppUser $user, $customClaims)
    {

        $token = null;

        if (!empty($user)) {
            $token = JWTAuth::claims($customClaims)->fromUser($user);
        }

        if (empty($token)) {
            return response()->json([
                "message" => "Login failed | Token not generated ",
                "token"  => $token
            ]);
        }

        return response()->json([
            'status'       => true,
            'message'      => 'Login successful',
            'token' => $token,
            'token_type'   => 'bearer',
        ]);
    }

    private function generateLoginResponse(CollegeAppUser $user)
    {
        $customClaims = [
            'user_id'           => (int) $user->id,
            'college_id'        => $user->college_id !== null
                ? (int) $user->college_id
                : null,
            'registration_id'   => $user->registration_id,
            'user_privilege_id' => (int) $user->user_privilege_id,
            'check_flag'        => (int) $user->check_flag,
        ];

        $token = JWTAuth::claims($customClaims)->fromUser($user);

        return response()->json([
            'status'       => true,
            'message'      => 'Login successful',
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => JWTAuth::factory()->getTTL() * 60,

            'user' => [
                'id'                       => $user->id,
                'registration_id'          => $user->registration_id,
                'mobile_no'                => $user->mobile_no,
                'email'                    => $user->email,
                'college_id'               => $user->college_id,
                'user_privilege_id'        => $user->user_privilege_id,
                'check_flag'               => $user->check_flag,
                'switch_branch_permission' => $user->switch_branch_permission,
            ],
        ]);
    }

    public function getUser()
    {
        try {
            $user = Auth::guard('api')->user();

            if (!$user) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Unauthenticated. Please login again.',
                ], 401);
            }

            $payload = JWTAuth::parseToken()->getPayload();

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
            // Blacklists the current token so it can't be reused
            Auth::guard('api')->logout();
        } catch (JWTException $e) {
            // Token missing, expired, invalid, or already blacklisted.
            // The goal (an unusable token) is already met, so treat it as logged out.
        }

        return response()->json([
            'status'  => true,
            'message' => 'Successfully logged out',
        ]);
    }
}
