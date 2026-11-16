<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('franchise_agreement', function (Blueprint $table) {
            $table->id();

            $table->foreignId('franchise_id')->constrained('franchise_master')->cascadeOnDelete();

            // ---------- Basic ----------
            $table->string('agreement_number', 50)->unique();
            $table->enum('agreement_type', ['new', 'renewal', 'amendment'])->default('new');
            $table->unsignedInteger('version')->default(1);
            $table->foreignId('parent_agreement_id')->nullable()
                ->constrained('franchise_agreement')->nullOnDelete();   // renewal ho to purana agreement
            $table->date('agreement_date');
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedInteger('duration_months')->nullable();
            $table->boolean('auto_renew')->default(false);
            $table->unsignedInteger('renewal_notice_days')->nullable();

            // ---------- Financial terms ----------
            $table->decimal('franchise_fee', 12, 2);
            $table->decimal('fee_gst_percent', 5, 2)->nullable();
            $table->decimal('security_deposit', 12, 2)->nullable();
            $table->enum('deposit_status', ['pending', 'received', 'refunded', 'adjusted'])->nullable();
            $table->decimal('royalty_percent', 5, 2)->nullable();
            $table->decimal('revenue_share_percent', 5, 2)->nullable();
            $table->decimal('minimum_sales_target', 12, 2)->nullable();
            $table->enum('target_period', ['monthly', 'quarterly', 'yearly'])->nullable();
            $table->text('payment_terms')->nullable();

            // ---------- Territory aur conditions ----------
            $table->string('territory_state')->nullable();
            $table->string('territory_district', 100)->nullable();        // null = poora state
            $table->boolean('is_exclusive')->default(false);
            $table->unsignedInteger('non_compete_months')->nullable();
            $table->unsignedInteger('termination_notice_days')->nullable();
            $table->string('jurisdiction', 150)->nullable();              // kis shehar ki court
            $table->text('special_terms')->nullable();

            // ---------- Legal / Signing ----------
            $table->string('stamp_paper_number', 50)->nullable();
            $table->decimal('stamp_duty_amount', 10, 2)->nullable();
            $table->string('company_signatory_name', 150)->nullable();
            $table->string('company_signatory_designation', 100)->nullable();
            $table->string('franchisee_signatory_name', 150)->nullable();
            $table->string('witness1_name', 150)->nullable();
            $table->string('witness2_name', 150)->nullable();
            $table->boolean('signed_by_franchisee')->default(false);
            $table->date('franchisee_signed_on')->nullable();
            $table->boolean('signed_by_company')->default(false);
            $table->date('company_signed_on')->nullable();
            $table->string('template_file_url', 500)->nullable();         // draft PDF
            $table->string('signed_file_url', 500)->nullable();           // signed PDF

            // ---------- Status aur Termination ----------
            $table->enum('status', ['draft', 'sent', 'signed', 'active', 'expired', 'terminated'])
                ->default('draft');
            $table->date('terminated_on')->nullable();
            $table->text('termination_reason')->nullable();

            // ---------- System ----------
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            // ---------- Indexes ----------
            $table->index(['franchise_id', 'status']);
            $table->index('end_date');
            $table->index(['territory_state', 'territory_district']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('franchise_agreement');
    }
};
