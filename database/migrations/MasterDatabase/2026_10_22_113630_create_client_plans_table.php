<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('default')->create('client_plans', function (Blueprint $table) {
            $table->increments('plan_id');
            $table->string('plan_name', 100);
            $table->integer('orders')->nullable();
            $table->string('slug', 100)->nullable();
            $table->json('plan_module_ids')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('client_plans');
    }
};
