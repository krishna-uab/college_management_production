<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('franchise_master', function (Blueprint $table) {
            $table->id();

            // ---------- Basic ----------
            $table->string('franchise_code', 30)->unique();
            $table->string('franchise_name', 150);
            $table->enum('franchise_type', ['individual', 'proprietorship', 'partnership', 'llp', 'company']);
            $table->enum('status', ['pending', 'under_review', 'active', 'suspended', 'terminated', 'rejected'])
                ->default('pending');
            $table->date('registered_on');
            $table->string('source', 50)->nullable();                // website / referral / agent
            $table->string('referred_by', 100)->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();

            // ---------- Company / Business ----------
            $table->string('business_name', 200)->nullable();
            $table->string('business_category', 100)->nullable();
            $table->string('cin_number', 21)->nullable()->unique();
            $table->string('llpin_number', 8)->nullable()->unique();
            $table->string('business_registration_no', 50)->nullable(); // Shop Act / partnership reg.
            $table->date('date_of_incorporation')->nullable();
            $table->string('company_pan', 10)->nullable();
            $table->string('gst_number', 15)->nullable();
            $table->string('tan_number', 10)->nullable();
            $table->string('msme_udyam_number', 25)->nullable();
            $table->string('company_email', 150)->nullable();
            $table->string('company_phone', 15)->nullable();
            $table->string('website', 200)->nullable();
            $table->unsignedTinyInteger('experience_years')->nullable();
            $table->unsignedInteger('staff_count')->nullable();

            // ---------- Owner / Authorized person ----------
            $table->string('owner_name', 150)->nullable();
            $table->string('father_or_spouse_name', 150)->nullable();
            $table->string('designation', 100)->nullable();          // Director / Partner / Proprietor
            $table->date('dob')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->string('owner_email', 150)->unique();
            $table->string('owner_phone', 15)->nullable();
            $table->string('alternate_phone', 15)->nullable();
            $table->string('whatsapp_number', 15)->nullable();
            $table->string('owner_pan', 10)->nullable();
            $table->text('aadhaar_number_encrypted')->nullable();    // encrypted via model cast
            $table->char('aadhaar_last4', 4)->nullable();
            $table->string('photo_url', 500)->nullable();

            // ---------- Registered / Office address ----------
            $table->string('address_line1', 255)->nullable();
            $table->string('address_line2', 255)->nullable();
            $table->string('landmark', 150)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('district', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('pincode', 6)->nullable();
            $table->string('country', 50)->default('India');

            $table->string('territory_state')->nullable();
            $table->string('territory_district', 100)->nullable();   // null = poora state
            $table->boolean('is_exclusive')->default(false);
            
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // ---------- Indexes ----------
            $table->index('status');
            $table->index('franchise_type');
            $table->index('state');
            $table->index(['territory_state', 'territory_district']);
            $table->index('owner_phone');
            $table->index('owner_pan');
            $table->index('gst_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('franchise_master');
    }
};
