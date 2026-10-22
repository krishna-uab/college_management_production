<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('all_colleges', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150)->unique();
            $table->text('db_name')->nullable();
            $table->text('db_user')->nullable();
            $table->text('db_password')->nullable();
            $table->unsignedInteger('common_branch_id')->default(0);
            $table->string('school_app_code', 32)
                ->nullable()
                ->unique();
            $table->boolean('status')->default(true)->index();
            $table->date('reg_date');
            $table->date('renewal_date')->nullable();
            $table->unsignedTinyInteger('user_type');
            $table->unsignedSmallInteger('plan_type');
            $table->unsignedInteger('log_time')->default(360);
            $table->unsignedInteger('active_day')->nullable();
            $table->unsignedInteger('grace_period')->default(0);
            $table->unsignedBigInteger('franchise_id')->default(1)->index();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('renewal_date');
            $table->index(['franchise_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('all_colleges');
    }
};
