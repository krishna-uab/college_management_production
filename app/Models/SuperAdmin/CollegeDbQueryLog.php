<?php

namespace App\Models\SuperAdmin;

use Illuminate\Database\Eloquent\Model;

class CollegeDbQueryLog extends Model
{
    protected $table = 'college_db_query_logs';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'title',
        'group',
        'query_text',
        'status',
        'error_message',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];
}
