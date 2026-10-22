<?php

namespace App\Models\SuperAdmin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FranchiseMaster extends Model
{
    use SoftDeletes;

    protected $table = 'franchise_masters';

    protected $connection = "default";

    protected $fillable = [
        'company_name',
        'person_name',
        'contact_number',
        'support_contact_no_1',
        'support_contact_no_2',
        'support_time',
        'ip_address',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];
}
