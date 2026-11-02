<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('franchises', function (Blueprint $table) {
            $table->id();
            $table->string('company_name', 250);
            $table->string('person_name', 250);
            $table->string('contact_number', 15);
            $table->string('email', 50);
            $table->string('support_contact_no_1', 15)->nullable();
            $table->string('support_contact_no_2', 15)->nullable();
            $table->string('support_time', 250)->nullable();
            $table->string('city', 250)->nullable();
            $table->string('state', 250)->nullable();
            $table->string('country', 250)->nullable();
            $table->string('ip_address', 45)->nullable();

            $table->date('reg_date')->nullable();
            $table->date('renewal_date')->nullable();
            $table->unsignedSmallInteger('plan_type')->nullable();
            $table->unsignedInteger('active_day')->nullable();
            $table->unsignedInteger('grace_period')->nullable();

            $table->index('company_name');
            $table->index('person_name');
            $table->index('contact_number');
            $table->index('ip_address');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('franchises');
    }
};
