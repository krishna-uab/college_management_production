<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('quequ_db_trackers', function (Blueprint $table) {
            
            $table->id('id');
            $table->string('db_name', 100)->unique();
            $table->unsignedInteger('pending_count')->default(0);
            $table->unsignedInteger('processing_count')->default(0);
            $table->unsignedInteger('done_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);

            $table->enum('current_status', [
                'idle',
                'running',
                'completed',
                'failed',
                'locked',
                'paused',
            ])->default('idle');

            $table->string('current_worker', 100)->nullable();
            $table->unsignedBigInteger('current_queue_id')->nullable();

            $table->dateTime('started_at')->nullable();
            $table->dateTime('last_queue_at')->nullable();
            $table->dateTime('last_activity_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->dateTime('failed_at')->nullable();

            $table->text('last_error')->nullable();
            $table->unsignedInteger('retry_count')->default(0);

            $table->string('ip_address', 45)->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('current_status');
            $table->index('last_activity_at');
            $table->index('ip_address');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quequ_db_trackers');
    }
};
