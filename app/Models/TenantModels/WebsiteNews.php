<?php

namespace App\Models;

use App\Traits\UsesDatabaseConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WebsiteNews extends Model
{
    use SoftDeletes, UsesDatabaseConnection;

    protected $table = 'website_news';

    protected $primaryKey = 'news_id';

    public $incrementing = true;

    protected $keyType = 'int';

    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'news_title',
        'description',
        'date',
        'image',
        'is_active',
        'bulleten_status',
        'school_id',
        'session_id',
        'created_by',
        'updated_by',
        'deleted_by',
        'is_deleted',
        'ip_address',
    ];

    protected $casts = [
        'date' => 'date',
        'is_active' => 'integer',
        'bulleten_status' => 'integer',
        'is_deleted' => 'integer',
        'school_id' => 'integer',
        'session_id' => 'integer',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'deleted_by' => 'integer',
    ];
}
