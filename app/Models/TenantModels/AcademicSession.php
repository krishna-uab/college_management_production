<?php

namespace App\Models\TenantModels;

use App\Traits\UsesDatabaseConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AcademicSession extends Model
{
    use SoftDeletes, UsesDatabaseConnection;

    protected $table = 'academic_sessions';

    protected $primaryKey = 'id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'session_start',
        'session_end',
        'session_name',
        'status',
        'school_id',
    ];

    protected $casts = [
        'session_start' => 'date',
        'session_end'   => 'date',
        'status'        => 'boolean',
        'school_id'     => 'integer',
    ];


    public static function getCurrentSession()
    {
        return self::where('status', 1)->first();
    }


}
