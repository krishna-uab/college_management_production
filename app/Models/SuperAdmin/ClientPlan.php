<?php

namespace App\Models\SuperAdmin;

use Illuminate\Database\Eloquent\Model;

class ClientPlan extends Model
{

    protected $table = 'client_plans';

    protected $primaryKey = 'plan_id';

    protected $connection = 'default';

    public $timestamps = false;

    protected $fillable = [
        'plan_name',
        'slug',
        'orders',
        'plan_module_ids',
        'created_at',
    ];

    protected $casts = [
        'plan_id' => 'integer',
        'plan_module_ids' => 'array',
        'created_at' => 'datetime:d M Y',
    ];
}
