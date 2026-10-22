<?php

namespace App\Models\SuperAdmin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuequDbTracker extends Model
{
    use SoftDeletes;

    protected $table = 'quequ_db_trackers';

    protected $primaryKey = 'id';

    protected $connection = "default";

    public $timestamps = true;

    protected $fillable = [
        'db_name',
        'pending_count',
        'processing_count',
        'done_count',
        'failed_count',
        'current_status',
        'current_worker',
        'current_queue_id',
        'started_at',
        'last_queue_at',
        'last_activity_at',
        'completed_at',
        'failed_at',
        'last_error',
        'retry_count',
        'ip_address',
    ];

    protected $casts = [
        'pending_count' => 'integer',
        'processing_count' => 'integer',
        'done_count' => 'integer',
        'failed_count' => 'integer',
        'current_queue_id' => 'integer',
        'retry_count' => 'integer',
        'started_at' => 'datetime',
        'last_queue_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];
}
