<?php

namespace App\Models\TenantModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Institute extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'institutes';

    protected $guarded = ['id'];

    protected $casts = [
        'establishment_year' => 'integer',

        'affiliation_valid_from' => 'date',
        'affiliation_valid_to' => 'date',
        'naac_valid_upto' => 'date',

        'ugc_recognition' => 'boolean',

        'nirf_rank' => 'integer',

        'starting_date' => 'date',
        'start_month' => 'integer',

        'semester_system' => 'boolean',
        'credit_system' => 'boolean',
        'default_course_duration_years' => 'decimal:1',

        'is_attendance' => 'boolean',
        'display_result' => 'boolean',
        'examination_secure' => 'boolean',
        'promotion_enable' => 'boolean',

        'online_adm_from_date' => 'datetime',
        'online_adm_to_date' => 'datetime',

        'form_limit' => 'integer',
        'status' => 'boolean',

        'college_id' => 'integer',
        'updated_by' => 'integer',
    ];
}
