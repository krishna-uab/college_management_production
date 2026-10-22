<?php

namespace App\Services;

use App\Models\SuperAdmin\CollegeAppUser;
use App\Models\SuperAdmin\UserDeviceToken;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CommonService
{
    private const DEFAULT_PASSWORD = '97538642';

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
            'college_id' => 'nullable|integer|exists:all_colleges,id',
        ]);

        $validated = $validator->validate();

        $registrationId = $validated['registration_id'] ?? null;
        $mobileNo = $validated['mobile_no'] ?? null;

        // Password priority:
        // 1. Request password
        // 2. Mobile number
        // 3. Default password

        $password = $validated['password']
            ?? $mobileNo
            ?? self::DEFAULT_PASSWORD;

        $validated['user_key'] = base64_encode($password);
        $validated['password'] = $password;

        if ($id !== null) {
            $user = CollegeAppUser::findOrFail($id);
            $user->fill($validated);
        } elseif ($registrationId !== null && $mobileNo !== null) {

            $user = CollegeAppUser::where(
                'registration_id',
                $registrationId
            )
                ->where('mobile_no', $mobileNo)
                ->first();

            if ($user) {
                $user->fill($validated);
            } else {
                $user = new CollegeAppUser();
                $user->fill($validated);
            }
        } else {
            $user = new CollegeAppUser();
            $user->fill($validated);
        }

        $user->save();

        return $user->fresh();
    }

    public function getAppUserById(int $id): CollegeAppUser
    {
        return CollegeAppUser::findOrFail($id);
    }

    // add update user device token

    // # how to use addUpdateUserDeviceTokens
    public function addUpdateUserDeviceTokens(array $data): array
    {

        // data need

        // [
        //     'college_id'   => $collegeId,
        //     'user_id'      => $userId,
        //     'device_id'    => $request->device_id,
        //     'device_token' => $request->device_token,
        //     'device_name'  => $request->device_name,
        //     'platform'     => $request->platform ?? 'android',
        //     'is_active'    => 1,
        //     'ip_address'   => $request->ip(),
        // ]

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
            $query = UserDeviceToken::where('college_id', $collegeId)
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
            $devices = UserDeviceToken::where('college_id', $collegeId)
                ->where('user_id', $userId)
                ->orderByRaw('last_login_at IS NULL DESC')
                ->orderBy('last_login_at', 'asc')
                ->orderBy('created_at', 'asc')
                ->lockForUpdate()
                ->get();

            // Less than 3 devices: create a new record.
            if ($devices->count() < 3) {
                $token = UserDeviceToken::create([
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

    // queue tracker management

}
