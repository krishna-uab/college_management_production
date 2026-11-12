
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('franchise_documents', function (Blueprint $table) {
            $table->id();

            $table->foreignId('franchise_id')
                ->constrained('franchise_master')
                ->cascadeOnDelete();

            $table->string('pan_url', 500)->nullable();
            $table->string('company_pan_url', 500)->nullable();
            $table->string('aadhaar_url', 500)->nullable();
            $table->string('gst_certificate_url', 500)->nullable();
            $table->string('cin_certificate_url', 500)->nullable();
            $table->string('llp_incorporation_certificate_url', 500)->nullable();
            $table->string('moa_aoa_url', 500)->nullable();
            $table->string('partnership_deed_url', 500)->nullable();
            $table->string('board_resolution_url', 500)->nullable();
            $table->string('address_proof_url', 500)->nullable();
            $table->string('office_proof_url', 500)->nullable();
            $table->string('photo_url', 500)->nullable();
            $table->string('cancelled_cheque_url', 500)->nullable();
            $table->string('msme_certificate_url', 500)->nullable();
            $table->string('agreement_copy_url', 500)->nullable();
            $table->string('other_document_url', 500)->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('franchise_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('franchise_documents');
    }
};
