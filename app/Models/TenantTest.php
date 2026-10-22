<?php

namespace App\Models;

use App\Traits\UsesDatabaseConnection;
use Illuminate\Database\Eloquent\Model;

class TenantTest extends Model
{
    use UsesDatabaseConnection;

    protected $table = 'tenant_tests';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'status',
        'renewal_date',
    ];

    protected $casts = [
        'status'       => 'boolean',
        'renewal_date' => 'date',
    ];
}
