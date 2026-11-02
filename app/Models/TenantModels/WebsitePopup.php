<?php

namespace App\Models\TenantModels;

use App\Traits\UsesDatabaseConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WebsitePopup extends Model
{
    use SoftDeletes, UsesDatabaseConnection;

    protected $table = 'website_popups';

    protected $primaryKey = 'website_popup_id';

    protected $fillable = [
        'from_date',
        'to_date',
        'image',
        'redirect_url',
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
            'from_date'  => 'date',
            'to_date'    => 'date',
            'college_id' => 'integer',
            'session_id' => 'integer',
            'is_deleted' => 'boolean',
        ];
    }
}
