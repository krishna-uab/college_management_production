<?php

namespace App\Traits;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

trait ResolveDatabaseConnection
{
    protected function tokenPayload(): array
    {
        try {

            return JWTAuth::parseToken()->getPayload()->toArray();
        } catch (\Throwable $e) {
            abort(response()->json([
                'status'  => false,
                'message' => 'Invalid or expired token.',
            ], 401));
        }
    }

    protected function tokenValue(string $key, $default = null)
    {
        return $this->tokenPayload()[$key] ?? $default;
    }

    protected function collegeConnection(): string
    {
        return $this->validateConnection($this->tokenValue('db_name'));
    }

    protected function validateConnection(?string $group): string
    {
        $allowed = array_diff(array_keys(config('database.connections')), ['default']);

        if (!$group || !in_array($group, $allowed, true)) {
            abort(response()->json([
                'status'  => false,
                'message' => 'Invalid db group.',
            ], 403));
        }

        return $group;
    }


    protected function switchModels(array $models): string
    {
        $conn = $this->collegeConnection();

        foreach ($models as $model) {
            $model::useConnection($conn);
        }
        return $conn;
    }


    // protected function collegeConnection(): string
    // {
    //     $dbName = $this->tokenValue('db_name');

    //     if (!$dbName) {
    //         abort(response()->json([
    //             'status'  => false,
    //             'message' => 'Invalid db group.',
    //         ], 403));
    //     }

    //     // Yahan DB username/password set karein
    //     Config::set('database.connections.tenant.database', $dbName);
    //     Config::set('database.connections.tenant.username', env('TENANT_DB_USERNAME', 'root'));
    //     Config::set('database.connections.tenant.password', env('TENANT_DB_PASSWORD', ''));

    //     DB::purge('tenant');

    //     return 'tenant';
    // }

    // protected function switchModels(array $models): string
    // {
    //     $conn = $this->collegeConnection();

    //     foreach ($models as $model) {
    //         $model::useConnection($conn);
    //     }

    //     return $conn;
    // }
}
