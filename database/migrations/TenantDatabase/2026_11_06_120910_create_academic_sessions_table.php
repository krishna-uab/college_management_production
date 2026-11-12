<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('academic_sessions', function (Blueprint $table) {
            $table->increments('id');
            $table->date('session_start');
            $table->date('session_end');
            $table->string('session_name', 255)->nullable();
            $table->boolean('status')->default(1);
            $table->unsignedBigInteger('school_id');
            $table->index('school_id');
            $table->index('status');
            $table->index(['school_id', 'status']);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_sessions');
    }
};
