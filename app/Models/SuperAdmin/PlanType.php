<?php

namespace App\Models\SuperAdmin;
use Illuminate\Database\Eloquent\Model;

class PlanType extends Model
{

    protected $table = 'plan_types';

    protected $primaryKey = 'plan_type_id';

    protected $connection = 'default';

    public $timestamps = false;

    protected $fillable = [
        'user_type',
        'slug',
        'period_days',
        'created_at',
    ];

    protected $casts = [
        'plan_type_id' => 'integer',
        'period_days' => 'integer',
        'created_at' => 'datetime:d M Y',
    ];
}
