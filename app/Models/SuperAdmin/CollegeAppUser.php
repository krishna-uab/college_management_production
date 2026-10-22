<?php

namespace App\Models\SuperAdmin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Hash;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

class CollegeAppUser extends Authenticatable implements JWTSubject
{
    use HasFactory, SoftDeletes;

    protected $table = 'college_app_users';

    protected $primaryKey = 'id';

    protected $connection = "default";

    protected $fillable = [
        'registration_id',
        'mobile_no',
        'email',
        'password',
        'user_key',
        'check_flag',
        'user_privilege_id',
        'switch_branch_permission',
        'is_active',
        'login_status',
        'college_id',
    ];

    protected $hidden = [
        'password',
        'user_key',
    ];

    protected function casts(): array
    {
        return [
            'registration_id'        => 'integer',
            'check_flag'             => 'integer',
            'user_privilege_id'      => 'integer',
            'is_active'              => 'boolean',
            'login_status'           => 'boolean',
            'college_id'             => 'integer',
            'password'               => 'hashed',
        ];
    }

    public function college(): BelongsTo
    {
        return $this->belongsTo(
            AllCollege::class,
            'college_id',
            'id'
        );
    }

    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [];
    }

    protected function setPasswordAttribute($value): void
    {
        if ($value !== null && $value !== '') {
            $this->attributes['password'] = Hash::make($value);
        }
    }
}

