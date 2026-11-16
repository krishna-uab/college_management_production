<?php

namespace App\Models\SuperAdmin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FranchiseMaster extends Model
{
    use SoftDeletes;

    protected $table = 'franchise_master';

    protected $fillable = [
        // Basic
        'franchise_code',
        'franchise_name',
        'franchise_type',
        'status',
        'registered_on',
        'source',
        'referred_by',
        'approved_by',
        'approved_at',
        'rejection_reason',

        // Company
        'business_name',
        'business_category',
        'cin_number',
        'llpin_number',
        'business_registration_no',
        'date_of_incorporation',
        'company_pan',
        'gst_number',
        'tan_number',
        'msme_udyam_number',
        'company_email',
        'company_phone',
        'website',
        'experience_years',
        'staff_count',

        // Owner
        'owner_name',
        'father_or_spouse_name',
        'designation',
        'dob',
        'gender',
        'owner_email',
        'owner_phone',
        'alternate_phone',
        'whatsapp_number',
        'owner_pan',
        'aadhaar_number_encrypted',
        'aadhaar_last4',
        'photo_url',

        // Address
        'address_line1',
        'address_line2',
        'landmark',
        'city',
        'district',
        'state',
        'pincode',
        'country',

        // Territory
        'territory_state',
        'territory_district',
        'is_exclusive',

        // System
        'remarks',
        'created_by',
    ];

    // API response me Aadhaar ka encrypted data kabhi na jaye
    protected $hidden = ['aadhaar_number_encrypted'];

    protected $casts = [
        'registered_on' => 'date:Y-m-d',
        'date_of_incorporation'    => 'date',
        'dob'                      => 'date',
        'approved_at'              => 'datetime',
        'is_exclusive'             => 'boolean',
        'experience_years'         => 'integer',
        'staff_count'              => 'integer',
        'aadhaar_number_encrypted' => 'encrypted',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(
            CollegeClient::class
        );
    }


    protected static function booted(): void
    {
        static::saving(function (self $model) {
            if ($model->isDirty('aadhaar_number_encrypted') && $model->aadhaar_number_encrypted) {
                $model->aadhaar_last4 = substr($model->aadhaar_number_encrypted, -4);
            }
        });
    }

    public function documents(): HasMany
    {
        return $this->hasMany(FranchiseDocument::class, 'franchise_id');
    }


    public function bankDetails(): HasMany
    {
        return $this->hasMany(FranchiseBankDetail::class, 'franchise_id');
    }


    public function agreements(): HasMany
    {
        return $this->hasMany(FranchiseAgreement::class, 'franchise_id');
    }


    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    // ---------- Helpers ----------
    public function isCompany(): bool
    {
        return $this->franchise_type === 'company';
    }

    public static function generateCode(string $stateCode): string
    {
        $next = (static::withTrashed()->max('id') ?? 0) + 1;
        return sprintf('FR-%s-%04d', strtoupper($stateCode), $next);
    }


    
}
