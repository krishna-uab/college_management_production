<?php

namespace App\Models\TenantModels;

use App\Traits\UsesDatabaseConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WebsiteSlider extends Model
{
    use SoftDeletes, UsesDatabaseConnection;

    protected $table = 'website_sliders';

    protected $fillable = [
        'image',
        'is_active',
        'college_id',
        'session_id',
        'created_by',
        'updated_by',
        'deleted_by',
        'is_deleted',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_deleted' => 'boolean',
            'college_id' => 'integer',
            'session_id' => 'integer',
        ];
    }
}
