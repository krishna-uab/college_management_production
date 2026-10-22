<?php

namespace App\Models\SuperAdmin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserDeviceToken extends Model
{
    use SoftDeletes;

    protected $table = 'user_device_tokens';

    protected $primaryKey = 'id';

    protected $connection = 'default';

    protected $fillable = [
        'college_id',
        'user_id',
        'device_token',
        'device_id',
        'device_name',
        'platform',
        'is_active',
        'last_login_at',
        'ip_address',
    ];

    protected $casts = [
        'school_id' => 'integer',
        'user_id' => 'integer',
        'is_active' => 'integer',
        'last_login_at' => 'datetime',
    ];
}
