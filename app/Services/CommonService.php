<?php

namespace App\Services;

use App\Models\SuperAdmin\CollegeAppUser;
use App\Models\SuperAdmin\CollegeClient;
use App\Models\SuperAdmin\CollegeUserDeviceToken;
use App\Traits\DatabaseConnectionWriter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class CommonService
{
    private const DEFAULT_PASSWORD = '97538642';

    use DatabaseConnectionWriter;

    public function addUpdateCollege(
        Request $request,
        $data,
        bool $isSuperAdmin = false
    ): JsonResponse {
        $id = $request->input('id');

        $validator = Validator::make($request->all(), [
            'id' => 'nullable|integer|exists:college_clients,id',

            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('college_clients', 'name')->ignore($id),
            ],

            'group' => 'required_without:id|string|max:64|regex:/^[a-zA-Z0-9_-]+$/',
            'db_name' => 'required_without:id|string|max:64',
            'db_user' => 'required_without:id|string|max:64',
            'db_password' => 'nullable|string',

            'owner_name' => 'nullable|string|max:150',
            'address'    => 'nullable|string',
            'city'       => 'nullable|string|max:100',
            'state'      => 'nullable|string|max:100',
            'country'    => 'nullable|string|max:50',

            'common_branch_id' => 'sometimes|integer|min:0',

            'school_app_code' => [
                'nullable',
                'string',
                'max:32',
                Rule::unique('college_clients', 'school_app_code')->ignore($id),
            ],

            'status' => 'nullable|boolean',
            'reg_date' => 'required|date',
            'renewal_date' => 'nullable|date|after_or_equal:reg_date',
            'user_type' => 'required|integer|between:0,255',
            'plan_type' => 'required|integer|between:0,65535',
            'log_time' => 'sometimes|integer|min:0',
            'active_day' => 'nullable|integer|min:0',
            'grace_period' => 'sometimes|integer|min:0',

            'app_user' => 'nullable|array',
            'app_user.registration_id' => 'nullable|integer',
            'app_user.mobile_no' => 'nullable|string|max:10',
            'app_user.email' => 'nullable|email|max:255',
            // app_user.password nahi lete, ye generate hota hai
        ]);

        $validated = $validator->validate();

        $id = $validated['id'] ?? null;

        $franchiseId = (int) ($data->get('franchise_id') ?? 1);

        if ($franchiseId < 1) {
            $franchiseId = 1;
        }

        $userId = Auth::id();
        $ipAddress = $request->ip();
        $generatedPassword = null;

        try {
            $college = DB::transaction(function () use (
                $validated,
                $id,
                $franchiseId,
                $userId,
                $ipAddress,
                $isSuperAdmin,
                &$generatedPassword
            ) {
                $collegeData = collect($validated)
                    ->except(['id', 'app_user', 'db_name', 'db_user', 'db_password'])
                    ->all();


                unset(
                    $collegeData['created_by'],
                    $collegeData['updated_by'],
                    $collegeData['deleted_by'],
                    $collegeData['ip_address'],
                    $collegeData['created_at'],
                    $collegeData['updated_at'],
                    $collegeData['deleted_at']
                );

                if ($id) {
                    $collegeQuery = CollegeClient::query();

                    // Franchise users can update only their own colleges.
                    if (!$isSuperAdmin) {
                        $collegeQuery->where(
                            'franchise_id',
                            $franchiseId
                        );
                    }

                    $college = $collegeQuery->lockForUpdate()->findOrFail($id);

                    // Do not change ownership using request data.
                    unset($collegeData['franchise_id']);

                    $college->fill($collegeData);
                    $college->updated_by = $userId;
                    $college->ip_address = $ipAddress;
                    $college->save();
                } else {
                    $collegeData['franchise_id'] = $franchiseId;
                    $collegeData['created_by'] = $userId;
                    $collegeData['updated_by'] = $userId;
                    $collegeData['ip_address'] = $ipAddress;

                    $college = CollegeClient::create($collegeData);

                    $connectionResult = $this->addConnection(
                        $validated['group'],
                        $validated['db_name'],
                        $validated['db_user'],
                        $validated['db_password'] ?? '',
                        $validated['name']
                    );

                    // getData(true) hamesha array deta hai, isliye 'success' key check karo
                    $connResult = $connectionResult->getData(true);

                    if (($connResult['success'] ?? false) !== true) {
                        throw new RuntimeException(
                            $connResult['message'] ?? 'Unable to create database connection'
                        );
                    }
                }

                $existingAppUser = CollegeAppUser::where(
                    'college_id',
                    $college->id
                )
                    ->where('user_privilege_id', 2)
                    ->first();

                // app_user na aaye to bhi error nahi aayega
                $userData = $validated['app_user'] ?? [];
                $userData['college_id'] = $college->id;
                $userData['user_privilege_id'] = 2;
                unset($userData['password']);

                // Password sirf naye app user ke liye generate hoga
                if (!$existingAppUser) {
                    $generatedPassword = $this->generateRandomPassword(12);
                    $userData['password'] = $generatedPassword;
                }

                $this->createAppUser(
                    $userData,
                    $existingAppUser?->id
                );

                return $college->fresh();
            });

            return response()->json([
                'status' => true,
                'message' => $id
                    ? 'College and app user updated successfully.'
                    : 'College and app user created successfully.',
                'data' => $college,
                // Naya app user bana ho tabhi value aayegi, warna null
                'created_app_user_password' => $generatedPassword,
            ], $id ? 200 : 201);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'status' => false,
                'message' => 'Unable to save college details.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function createAppUser(array $data, ?int $id = null): CollegeAppUser
    {
        $validator = Validator::make($data, [
            'registration_id' => 'nullable|integer',
            'mobile_no' => 'nullable|string|max:10',
            'email' => 'nullable|email|max:255',
            'password' => 'nullable|string|min:1',
            'user_key' => 'sometimes|nullable|string|max:255',
            'check_flag' => 'sometimes|integer|between:0,255',
            'user_privilege_id' => 'required|integer|between:0,255',
            'switch_branch_permission' => 'nullable|string|max:50',
            'is_active' => 'sometimes|boolean',
            'login_status' => 'sometimes|boolean',
            'college_id' => 'nullable|integer|exists:college_clients,id',
        ]);

        $validated = $validator->validate();

        $registrationId = $validated['registration_id'] ?? null;
        $mobileNo = $validated['mobile_no'] ?? null;

        // Pehle user dhundo, kyunki password ka faisla isi par depend karta hai
        if ($id !== null) {
            $user = CollegeAppUser::findOrFail($id);
        } elseif ($registrationId !== null && $mobileNo !== null) {
            $user = CollegeAppUser::where('registration_id', $registrationId)
                ->where('mobile_no', $mobileNo)
                ->first() ?? new CollegeAppUser();
        } else {
            $user = new CollegeAppUser();
        }

        // Password priority:
        // 1. Request / generated password
        // 2. Naya user ho to mobile number
        // 3. Naya user ho to default password
        // Existing user ka password tab tak nahi badalta jab tak naya na diya jaye.
        if (!empty($validated['password'])) {
            $password = $validated['password'];
        } elseif (!$user->exists) {
            $password = $mobileNo ?? self::DEFAULT_PASSWORD;
        } else {
            $password = null;
        }

        if ($password !== null) {
            $validated['password'] = $password;
            $validated['user_key'] = base64_encode($password);
        } else {
            unset($validated['password'], $validated['user_key']);
        }

        $user->fill($validated);
        $user->save();

        return $user->fresh();
    }

    public function getAppUserById(int $id): CollegeAppUser
    {
        return CollegeAppUser::findOrFail($id);
    }

    // for generate random password
    public function generateRandomPassword(int $length = 12): string
    {
        $upper   = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $lower   = 'abcdefghijkmnpqrstuvwxyz';
        $numbers = '23456789';
        $all     = $upper . $lower . $numbers;

        $password = [
            $upper[random_int(0, strlen($upper) - 1)],
            $lower[random_int(0, strlen($lower) - 1)],
            $numbers[random_int(0, strlen($numbers) - 1)],
        ];

        for ($i = count($password); $i < $length; $i++) {
            $password[] = $all[random_int(0, strlen($all) - 1)];
        }

        for ($i = count($password) - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            [$password[$i], $password[$j]] = [$password[$j], $password[$i]];
        }

        return implode('', $password);
    }


    // # how to use addUpdateUserDeviceTokens
    public function addUpdateUserDeviceTokens(array $data): array
    {
        return DB::transaction(function () use ($data) {

            $collegeId = $data['college_id'];
            $userId = $data['user_id'];
            $deviceId = $data['device_id'] ?? null;
            $deviceToken = $data['device_token'];

            $details = [
                'device_token'  => $deviceToken,
                'device_id'     => $deviceId,
                'device_name'   => $data['device_name'] ?? null,
                'platform'      => $data['platform'] ?? 'android',
                'is_active'     => $data['is_active'] ?? 1,
                'last_login_at' => now(),
                'ip_address'    => $data['ip_address'] ?? null,
            ];

            // Same user + same device: update existing record.
            $query = CollegeUserDeviceToken::where('college_id', $collegeId)
                ->where('user_id', $userId);

            if ($deviceId !== null && $deviceId !== '') {
                $query->where('device_id', $deviceId);
            } else {
                $query->where('device_token', $deviceToken);
            }

            $existing = $query->first();

            if ($existing) {
                $existing->update($details);

                return [
                    'status'  => true,
                    'message' => 'Device token updated successfully.',
                    'data'    => $existing->fresh(),
                ];
            }

            // Lock existing records for this user.
            $devices = CollegeUserDeviceToken::where('college_id', $collegeId)
                ->where('user_id', $userId)
                ->orderByRaw('last_login_at IS NULL DESC')
                ->orderBy('last_login_at', 'asc')
                ->orderBy('created_at', 'asc')
                ->lockForUpdate()
                ->get();

            // Less than 3 devices: create a new record.
            if ($devices->count() < 3) {
                $token = CollegeUserDeviceToken::create([
                    'college_id' => $collegeId,
                    'user_id'    => $userId,
                    ...$details,
                ]);

                return [
                    'status'  => true,
                    'message' => 'Device token added successfully.',
                    'data'    => $token,
                ];
            }

            // Three devices already exist: replace the oldest login.
            $oldestDevice = $devices->first();

            $oldestDevice->update($details);

            return [
                'status'  => true,
                'message' => 'Oldest device token replaced successfully.',
                'data'    => $oldestDevice->fresh(),
            ];
        });
    }
}
