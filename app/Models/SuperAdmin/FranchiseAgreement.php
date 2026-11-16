<?php

namespace App\Models\SuperAdmin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class FranchiseAgreement extends Model
{
    use SoftDeletes;

    protected $table = 'franchise_agreement';

    protected $fillable = [
        // Basic
        'franchise_id', 'agreement_number', 'agreement_type', 'version', 'parent_agreement_id',
        'agreement_date', 'start_date', 'end_date', 'duration_months', 'auto_renew', 'renewal_notice_days',

        // Financial
        'franchise_fee', 'fee_gst_percent', 'security_deposit', 'deposit_status',
        'royalty_percent', 'revenue_share_percent', 'minimum_sales_target', 'target_period', 'payment_terms',

        // Territory & conditions
        'territory_state', 'territory_district', 'is_exclusive',
        'non_compete_months', 'termination_notice_days', 'jurisdiction', 'special_terms',

        // Legal / signing
        'stamp_paper_number', 'stamp_duty_amount',
        'company_signatory_name', 'company_signatory_designation', 'franchisee_signatory_name',
        'witness1_name', 'witness2_name',
        'signed_by_franchisee', 'franchisee_signed_on', 'signed_by_company', 'company_signed_on',
        'template_file_url', 'signed_file_url',

        // Status
        'status', 'terminated_on', 'termination_reason',

        // System
        'remarks', 'created_by',
    ];

    protected $casts = [
        'agreement_date'        => 'date',
        'start_date'            => 'date',
        'end_date'              => 'date',
        'franchisee_signed_on'  => 'date',
        'company_signed_on'     => 'date',
        'terminated_on'         => 'date',
        'auto_renew'            => 'boolean',
        'is_exclusive'          => 'boolean',
        'signed_by_franchisee'  => 'boolean',
        'signed_by_company'     => 'boolean',
        'franchise_fee'         => 'decimal:2',
        'fee_gst_percent'       => 'decimal:2',
        'security_deposit'      => 'decimal:2',
        'royalty_percent'       => 'decimal:2',
        'revenue_share_percent' => 'decimal:2',
        'minimum_sales_target'  => 'decimal:2',
        'stamp_duty_amount'     => 'decimal:2',
        'version'               => 'integer',
    ];

    // ---------- Boot ----------
    protected static function booted(): void
    {
        static::saving(function (self $model) {
            // duration_months auto calculate
            if ($model->start_date && $model->end_date && !$model->duration_months) {
                $model->duration_months = $model->start_date->diffInMonths($model->end_date);
            }
        });
    }

    public function franchise(): BelongsTo
    {
        return $this->belongsTo(FranchiseMaster::class, 'franchise_id');
    }

    public function parentAgreement(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_agreement_id');
    }

    public function renewals(): HasMany
    {
        return $this->hasMany(self::class, 'parent_agreement_id');
    }

    // ---------- Scopes ----------
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeExpiringWithin($query, int $days = 30)
    {
        return $query->where('status', 'active')
                     ->whereBetween('end_date', [now()->toDateString(), now()->addDays($days)->toDateString()]);
    }

    // ---------- Helpers ----------
    public function isFullySigned(): bool
    {
        return $this->signed_by_franchisee && $this->signed_by_company;
    }

    public function isExpired(): bool
    {
        return $this->end_date->isPast();
    }

    public function daysLeft(): int
    {
        return max(0, (int) now()->startOfDay()->diffInDays($this->end_date, false));
    }

    public function markSignedByFranchisee(?string $date = null): void
    {
        $this->update([
            'signed_by_franchisee' => true,
            'franchisee_signed_on' => $date ?? now()->toDateString(),
        ]);
        $this->refreshSignedStatus();
    }

    public function markSignedByCompany(?string $date = null): void
    {
        $this->update([
            'signed_by_company' => true,
            'company_signed_on' => $date ?? now()->toDateString(),
        ]);
        $this->refreshSignedStatus();
    }

    protected function refreshSignedStatus(): void
    {
        if ($this->isFullySigned() && in_array($this->status, ['draft', 'sent'])) {
            $this->update(['status' => 'signed']);
        }
    }

    // Agreement activate: dono signature ho chuke hon tabhi
    public function activate(): void
    {
        if (!$this->isFullySigned()) {
            throw new \RuntimeException('Dono parties ke signature ke bina agreement active nahi ho sakta.');
        }

        $this->update(['status' => 'active']);
    }

    public function terminate(string $reason, ?string $date = null): void
    {
        $this->update([
            'status'             => 'terminated',
            'terminated_on'      => $date ?? now()->toDateString(),
            'termination_reason' => $reason,
        ]);
    }

    // Renewal: purani row edit nahi hoti, naya record banta hai (version + 1)
    public function renew(string $newAgreementNumber, string $newStartDate, string $newEndDate, array $overrides = []): self
    {
        return DB::transaction(function () use ($newAgreementNumber, $newStartDate, $newEndDate, $overrides) {
            $new = $this->replicate([
                'agreement_number', 'agreement_type', 'version', 'parent_agreement_id',
                'agreement_date', 'start_date', 'end_date', 'duration_months',
                'signed_by_franchisee', 'franchisee_signed_on', 'signed_by_company', 'company_signed_on',
                'signed_file_url', 'status', 'terminated_on', 'termination_reason',
            ]);

            $new->fill(array_merge([
                'agreement_number'     => $newAgreementNumber,
                'agreement_type'       => 'renewal',
                'version'              => $this->version + 1,
                'parent_agreement_id'  => $this->id,
                'agreement_date'       => now()->toDateString(),
                'start_date'           => $newStartDate,
                'end_date'             => $newEndDate,
                'duration_months'      => null,
                'signed_by_franchisee' => false,
                'signed_by_company'    => false,
                'status'               => 'draft',
            ], $overrides));

            $new->save();

            return $new;
        });
    }

    // Is territory me koi aur active exclusive agreement to nahi?
    public static function territoryTaken(int $stateId, ?string $district = null, ?int $exceptFranchiseId = null): bool
    {
        return static::active()
            ->where('is_exclusive', true)
            ->where('territory_state_id', $stateId)
            ->where(function ($q) use ($district) {
                // poora state diya ho ya wahi district
                $q->whereNull('territory_district');
                if ($district) {
                    $q->orWhere('territory_district', $district);
                }
            })
            ->when($exceptFranchiseId, fn ($q) => $q->where('franchise_id', '!=', $exceptFranchiseId))
            ->exists();
    }
}
