
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('college_app_users', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('registration_id')->nullable();

            $table->string('mobile_no', 10);
            $table->string('email', 255)->nullable();

            // Password must be stored as a hash
            $table->string('password');

            $table->string('user_key', 255);

            $table->unsignedTinyInteger('check_flag')->default(0);
            $table->unsignedTinyInteger('user_privilege_id');

            $table->string('switch_branch_permission', 50)->nullable();

            $table->boolean('is_active')->default(true);
            $table->boolean('login_status')->default(false);

            $table->unsignedBigInteger('college_id')->nullable();;

            $table->timestamps();
            $table->softDeletes();

            $table->index('registration_id');
            $table->index('user_privilege_id');
            $table->index(['college_id', 'mobile_no']);

            $table->foreign('college_id')
                ->references('id')
                ->on('all_colleges')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('college_app_users');
    }
};
