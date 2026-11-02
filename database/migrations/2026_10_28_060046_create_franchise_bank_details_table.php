<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('franchise_bank_details', function (Blueprint $table) {
            $table->id();

            $table->foreignId('franchise_id')->constrained('franchise_master')->cascadeOnDelete();

            $table->string('account_holder_name', 150);
            $table->string('bank_name', 100);
            $table->string('branch_name', 100)->nullable();
            $table->string('branch_address', 255)->nullable();

            $table->text('account_number_encrypted');              // encrypted via model cast
            $table->char('account_last4', 4);                      // display ke liye
            $table->string('ifsc_code', 11);
            $table->string('micr_code', 9)->nullable();
            $table->enum('account_type', ['savings', 'current', 'overdraft']);
            $table->string('upi_id', 100)->nullable();

            $table->boolean('is_primary')->default(false);
            $table->boolean('is_active')->default(true);

            // Cancelled cheque ka link (franchise_documents se)
            $table->foreignId('cancelled_cheque_doc_id')->nullable()
                ->constrained('franchise_documents')->nullOnDelete();

            // Verification
            $table->enum('verification_status', ['pending', 'verified', 'rejected'])->default('pending');

            $table->unsignedBigInteger('verified_by')->nullable();
            $table->dateTime('verified_at')->nullable();

            $table->text('remarks')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['franchise_id', 'is_primary']);
            $table->index('ifsc_code');
            $table->index('verification_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('franchise_bank_details');
    }
};
