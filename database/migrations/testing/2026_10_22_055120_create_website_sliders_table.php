<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('website_sliders', function (Blueprint $table) {
            $table->id();
            $table->text('image');
            $table->tinyInteger('is_active')->default(1);
            $table->unsignedBigInteger('college_id');
            $table->unsignedInteger('session_id');
            $table->unsignedInteger('created_by');
            $table->unsignedInteger('updated_by');
            $table->unsignedInteger('deleted_by');

            $table->tinyInteger('is_deleted')->default(0);
            $table->string('ip_address', 45)->nullable();
            $table->softDeletes();

            // Indexes
            $table->index('college_id');
            $table->index('session_id');
            $table->index('is_active');
            $table->index('is_deleted');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('website_sliders');
    }
};
