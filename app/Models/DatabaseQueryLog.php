<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DatabaseQueryLog extends Model
{
    protected $table = 'school_sql_query_logs';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'title',
        'db_name',
        'query_text',
        'status',
        'error_message',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];
}
