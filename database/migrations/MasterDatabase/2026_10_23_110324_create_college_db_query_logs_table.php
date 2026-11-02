<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('college_db_query_logs', function (Blueprint $table) {
            $table->id();
            $table->string('title', 500)->nullable();
            $table->string('group', 255)->nullable();
            $table->longText('query_text')->nullable();
            $table->string('status', 50)->nullable();
            $table->text('error_message')->nullable();
            $table->index('group');
            $table->index('status');
            $table->index('created_at');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('college_db_query_logs');
    }
};
