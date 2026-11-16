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
        'session_id',
        'created_by',
        'updated_by',
        'deleted_by',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'session_id' => 'integer',
        ];
    }
}
