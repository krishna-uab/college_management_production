<?php

namespace App\Models\SuperAdmin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FranchiseDocument extends Model
{
    use SoftDeletes;

    protected $table = 'franchise_documents';

    protected $fillable = [
        'franchise_id',
        'pan_url',
        'company_pan_url',
        'aadhaar_url',
        'gst_certificate_url',
        'cin_certificate_url',
        'llp_incorporation_certificate_url',
        'moa_aoa_url',
        'partnership_deed_url',
        'board_resolution_url',
        'address_proof_url',
        'office_proof_url',
        'photo_url',
        'cancelled_cheque_url',
        'msme_certificate_url',
        'agreement_copy_url',
        'other_document_url',
    ];

    public function franchise(): BelongsTo
    {
        return $this->belongsTo(FranchiseMaster::class, 'franchise_id');
    }
}
