<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_device_tokens', function (Blueprint $table) {
            $table->id();

            $table->foreignId('college_id')
                ->constrained('all_colleges')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained('college_app_users')
                ->cascadeOnDelete();

            $table->longText('device_token');
            $table->string('device_id', 255)->nullable();
            $table->string('device_name', 255)->nullable();

            $table->enum('platform', ['android', 'ios', 'web'])
                ->default('android')
                ->nullable();

            $table->tinyInteger('is_active')->default(1)->nullable();

            $table->dateTime('last_login_at')->nullable();

            $table->string('ip_address', 45)->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('college_id');
            $table->index('user_id');
            $table->index('device_id');
            $table->index('is_active');
            $table->index('ip_address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_device_tokens');
    }
};
