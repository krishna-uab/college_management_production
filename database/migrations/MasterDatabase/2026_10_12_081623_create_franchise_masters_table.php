<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('franchise_masters', function (Blueprint $table) {

            $table->id();
            $table->string('company_name', 250);
            $table->string('person_name', 250);
            $table->string('contact_number', 15);
            $table->string('support_contact_no_1', 15);
            $table->string('support_contact_no_2', 15);
            $table->string('support_time', 250);

            $table->string('ip_address', 45)->nullable();

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
        Schema::dropIfExists('franchise_masters');
    }
};
