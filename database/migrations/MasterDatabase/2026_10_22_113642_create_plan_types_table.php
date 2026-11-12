<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::connection('default')->create('plan_types', function (Blueprint $table) {
            $table->increments('plan_type_id');
            $table->string('slug', 100)->nullable();
            $table->string('user_type', 150)->nullable();
            $table->integer('period_days')->nullable();
            $table->dateTime('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_types');
    }
};
