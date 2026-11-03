<?php

namespace App\Models\SuperAdmin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class FranchiseBankDetail extends Model
{
    use SoftDeletes;

    protected $table = 'franchise_bank_details';

    protected $fillable = [
        'franchise_id',
        'account_holder_name',
        'bank_name',
        'branch_name',
        'branch_address',
        'account_number',
        'account_last4',
        'ifsc_code',
        'micr_code',
        'account_type',
        'upi_id',
        'is_primary',
        'is_active',
        'cancelled_cheque_doc_id',
        'verification_status',
        'verified_by',
        'verified_at',
        'remarks',
    ];

    protected $hidden = ['account_number'];

    protected $casts = [
        'is_primary'               => 'boolean',
        'is_active'                => 'boolean',
        'verified_at'              => 'datetime',
    ];

    // ---------- Boot ----------
    protected static function booted(): void
    {
        static::saving(function (self $model) {
            if ($model->ifsc_code) {
                $model->ifsc_code = strtoupper($model->ifsc_code);
            }
        });

        static::saved(function (self $model) {
            if ($model->is_primary) {
                static::where('franchise_id', $model->franchise_id)
                    ->where('id', '!=', $model->id)
                    ->where('is_primary', true)
                    ->update(['is_primary' => false]);
            }
        });
    }

    public function franchise(): BelongsTo
    {
        return $this->belongsTo(FranchiseMaster::class, 'franchise_id');
    }

    public function cancelledChequeDocument(): BelongsTo
    {
        return $this->belongsTo(FranchiseDocument::class, 'cancelled_cheque_doc_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    public function scopeVerified($query)
    {
        return $query->where('verification_status', 'verified');
    }

    public function getMaskedAccountNumberAttribute(): string
    {
        return 'XXXXXXXX' . $this->account_last4;
    }

    public function isVerified(): bool
    {
        return $this->verification_status === 'verified';
    }

    public function markVerified(int $userId): void
    {
        $this->update([
            'verification_status' => 'verified',
            'verified_by'         => $userId,
            'verified_at'         => now(),
        ]);
    }

    public function markRejected(int $userId, ?string $remarks = null): void
    {
        $this->update([
            'verification_status' => 'rejected',
            'verified_by'         => $userId,
            'verified_at'         => now(),
            'remarks'             => $remarks,
        ]);
    }

    public function makePrimary(): void
    {
        DB::transaction(function () {
            static::where('franchise_id', $this->franchise_id)->update(['is_primary' => false]);
            $this->update(['is_primary' => true]);
        });
    }

    public static function isValidIfsc(string $ifsc): bool
    {
        return (bool) preg_match('/^[A-Z]{4}0[A-Z0-9]{6}$/', strtoupper($ifsc));
    }
}
