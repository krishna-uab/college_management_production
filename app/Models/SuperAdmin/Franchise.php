<?php

namespace App\Models\SuperAdmin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Franchise extends Model
{
    use SoftDeletes;

    protected $table = 'franchises';

    protected $connection = "default";

    protected $fillable = [
        'company_name',
        'person_name',
        'city',
        'state',
        'email',
        'country',
        'contact_number',
        'support_contact_no_1',
        'support_contact_no_2',
        'support_time',
        'ip_address',
        'reg_date',
        'renewal_date',
        'plan_type',
        'active_day',
        'grace_period',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(
            CollegeClient::class
        );
    }
}
