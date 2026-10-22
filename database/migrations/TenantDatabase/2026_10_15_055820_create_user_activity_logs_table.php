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

            // User reference; no FK so audit history survives user deletion
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_name', 150)->nullable();

            // create, update, delete, login, logout, etc.
            $table->string('action', 20);

            // Example: users, items, languages
            $table->string('module', 100);

            // Supports numeric and non-numeric record identifiers
            $table->string('record_id', 100)->nullable();

            $table->string('description', 500)->nullable();

            // Before and after snapshots
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();

            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();

            $table->string('request_method', 10)->nullable();
            $table->string('url', 2048)->nullable();

            // Activity timestamp; no updated_at because logs are immutable
            $table->timestamp('created_at')->useCurrent();

            // Optimized for activity reports and filters
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
