<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_activity_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_name', 150)->nullable();
            $table->string('action', 20);
            $table->string('module', 100);
            $table->string('record_id', 100)->nullable();
            $table->string('description', 500)->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('request_method', 10)->nullable();
            $table->string('url', 2048)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(
                ['user_id', 'created_at'],
                'ual_user_created_idx'
            );

            $table->index(
                ['module', 'created_at'],
                'ual_module_created_idx'
            );

            $table->index(
                ['action', 'created_at'],
                'ual_action_created_idx'
            );

            $table->index(
                ['module', 'record_id'],
                'ual_record_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_activity_logs');
    }
};
