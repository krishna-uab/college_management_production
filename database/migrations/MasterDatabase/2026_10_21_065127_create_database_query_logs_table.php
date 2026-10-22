<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('database_query_logs', function (Blueprint $table) {
            $table->id();
            $table->string('title', 500)->nullable();
            $table->string('db_name', 255)->nullable();
            $table->longText('query_text')->nullable();
            $table->string('status', 50)->nullable();
            $table->text('error_message')->nullable();
            $table->index('db_name');
            $table->index('status');
            $table->index('created_at');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('database_query_logs');
    }
};
