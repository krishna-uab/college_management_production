<?php

namespace App\Models\SuperAdmin;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class CollegeClient extends Model
{
    use SoftDeletes;

    protected $table = 'college_clients';

    protected $connection = "default";

    public const PRIVILEGE_COLUMN = 'user_priviledge_id';

    public const ADMIN_PRIVILEGE_IDS   = [2];
    public const STAFF_PRIVILEGE_IDS   = [3];
    public const STUDENT_PRIVILEGE_IDS = [4];

    protected $fillable = [
        'name',
        'group',
        'owner_name',
        'address',
        'city',
        'mobile_no',
        'email',
        'state',
        'country',
        'common_branch_id',
        'school_app_code',
        'status',
        'reg_date',
        'renewal_date',
        'plan_type_id',
        'plan_id',
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

    // sabhi users (college_id se)
    public function users(): HasMany
    {
        return $this->hasMany(CollegeAppUser::class, 'college_id', 'id');
    }

    public function adminUser(): HasMany
    {
        return $this->users()->select('id', 'name', 'login_status', 'is_active', 'user_key', 'email', self::PRIVILEGE_COLUMN)->where(self::PRIVILEGE_COLUMN, self::ADMIN_PRIVILEGE_IDS);
    }

    public function staffAppUsers(): HasMany
    {
        return $this->users()->select('id', 'name', 'login_status', 'is_active', 'user_key', 'email', self::PRIVILEGE_COLUMN)->whereIn(self::PRIVILEGE_COLUMN, self::STAFF_PRIVILEGE_IDS);
    }

    public function studentAppUsers(): HasMany
    {
        return $this->users()->select('id', 'name', 'login_status', 'is_active', 'user_key', 'email', self::PRIVILEGE_COLUMN)->whereIn(self::PRIVILEGE_COLUMN, self::STUDENT_PRIVILEGE_IDS);
    }

    public function franchise(): BelongsTo
    {
        return $this->belongsTo(FranchiseMaster::class, 'franchise_id');
    }


    public function getAdmin(): HasOne
    {
        return $this->hasOne(
            CollegeAppUser::class,
            'college_id',
            'id'
        )->where('user_privilege_id', 2);
    }

    public function getStaffAppUsers(): Collection
    {
        return $this->staffAppUsers()->get();
    }

    public function getStudentAppUsers(): Collection
    {
        return $this->studentAppUsers()->get();
    }
}
