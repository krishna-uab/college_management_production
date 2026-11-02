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
        'account_number_encrypted',
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

    // API response me encrypted account number kabhi na jaye
    protected $hidden = ['account_number_encrypted'];

    protected $casts = [
        'account_number_encrypted' => 'encrypted',   // APP_KEY se auto encrypt/decrypt
        'is_primary'               => 'boolean',
        'is_active'                => 'boolean',
        'verified_at'              => 'datetime',
    ];

    // ---------- Boot ----------
    protected static function booted(): void
    {
        static::saving(function (self $model) {
            // Account number se last 4 digit auto set
            if ($model->isDirty('account_number_encrypted') && $model->account_number_encrypted) {
                $model->account_last4 = substr($model->account_number_encrypted, -4);
            }

            // IFSC hamesha uppercase
            if ($model->ifsc_code) {
                $model->ifsc_code = strtoupper($model->ifsc_code);
            }
        });

        // Ek franchise ka sirf ek primary account
        static::saved(function (self $model) {
            if ($model->is_primary) {
                static::where('franchise_id', $model->franchise_id)
                    ->where('id', '!=', $model->id)
                    ->where('is_primary', true)
                    ->update(['is_primary' => false]);
            }
        });
    }

    // ---------- Relations ----------
    public function franchise(): BelongsTo
    {
        return $this->belongsTo(FranchiseMaster::class, 'franchise_id');
    }

    public function cancelledChequeDocument(): BelongsTo
    {
        return $this->belongsTo(FranchiseDocument::class, 'cancelled_cheque_doc_id');
    }


    // ---------- Scopes ----------
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

    // ---------- Accessors ----------
    // Display ke liye: XXXXXXXX1234
    public function getMaskedAccountNumberAttribute(): string
    {
        return 'XXXXXXXX' . $this->account_last4;
    }

    // ---------- Helpers ----------
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

    // IFSC format check: 4 letters + 0 + 6 alphanumeric (e.g. SBIN0001234)
    public static function isValidIfsc(string $ifsc): bool
    {
        return (bool) preg_match('/^[A-Z]{4}0[A-Z0-9]{6}$/', strtoupper($ifsc));
    }
}
