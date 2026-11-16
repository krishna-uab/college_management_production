
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('website_news', function (Blueprint $table) {
            $table->increments('news_id');

            $table->text('news_title')->nullable();
            $table->text('description')->nullable();
            $table->date('date')->nullable();
            $table->text('image')->nullable();

            $table->tinyInteger('is_active')->default(1);
            $table->tinyInteger('bulleten_status')->default(0);

            $table->unsignedBigInteger('school_id');
            $table->unsignedBigInteger('session_id');
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by');
            $table->unsignedBigInteger('deleted_by');

            $table->tinyInteger('is_deleted')->default(0);

            $table->string('ip_address', 45)->nullable();

            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->nullable();

            // Soft delete support
            $table->softDeletes();

            // Indexes
            $table->index('school_id');
            $table->index('session_id');
            $table->index('created_by');
            $table->index('updated_by');
            $table->index('deleted_by');
            $table->index('ip_address');
            $table->index('is_active');
            $table->index('bulleten_status');
            $table->index('is_deleted');
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('website_news');
    }
};
