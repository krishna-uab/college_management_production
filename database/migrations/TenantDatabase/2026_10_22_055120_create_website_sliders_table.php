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
            $table->unsignedInteger('session_id')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->unsignedInteger('deleted_by')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->softDeletes();
            $table->index('session_id');
            $table->index('is_active');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('website_sliders');
    }
};
