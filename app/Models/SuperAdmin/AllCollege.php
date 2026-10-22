<?php

namespace App\Models\SuperAdmin;

use App\Models\SuperAdmin\CollegeAppUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AllCollege extends Model
{
    use SoftDeletes;

    protected $table = 'all_colleges';

    protected $connection = "default";

    protected $fillable = [
        'name',
        'db_name',
        'db_user',
        'db_password',
        'common_branch_id',
        'school_app_code',
        'status',
        'reg_date',
        'renewal_date',
        'user_type',
        'plan_type',
        'log_time',
        'active_day',
        'grace_period',
        'franchise_id',
        'created_by',
        'updated_by',
        'deleted_by',
        'ip_address',
    ];


    protected function casts(): array
    {
        return [
            'status'       => 'boolean',
            'reg_date'     => 'date',
            'renewal_date' => 'date',
            'active_day'   => 'integer',
            'log_time'     => 'integer',
            'grace_period' => 'integer',
        ];
    }


    public function users(): HasMany
    {
        return $this->hasMany(
            CollegeAppUser::class,
            'college_id',
            'id'
        );
    }
}
