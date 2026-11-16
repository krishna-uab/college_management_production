<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('website_popups', function (Blueprint $table) {
            $table->increments('website_popup_id');

            $table->date('from_date');
            $table->date('to_date');
            $table->text('image');
            $table->text('redirect_url');

            $table->unsignedBigInteger('college_id');
            $table->unsignedInteger('session_id');

            $table->unsignedInteger('created_by');
            $table->unsignedInteger('updated_by');
            $table->unsignedInteger('deleted_by');

            $table->tinyInteger('is_deleted')->default(0);
            $table->string('ip_address', 45)->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('college_id');
            $table->index('session_id');
            $table->index('from_date');
            $table->index('to_date');
            $table->index('is_deleted');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('website_popups');
    }
};
