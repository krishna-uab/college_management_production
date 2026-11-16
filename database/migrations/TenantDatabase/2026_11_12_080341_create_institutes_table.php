<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('institutes', function (Blueprint $table) {

            $table->id();

            $table->unsignedInteger('college_id')->nullable();
            $table->string('name', 255);
            $table->string('college_code', 30)->unique()->nullable();
            $table->string('app_code', 10)->nullable();
            $table->string('college_type', 50)->nullable();
            $table->string('institution_level', 30)->nullable();
            $table->string('institution_category', 50)->nullable();
            $table->year('establishment_year')->nullable();
            $table->string('aishe_code', 30)->nullable();


            $table->string('university_name', 255)->nullable();
            $table->string('university_code', 50)->nullable();
            $table->string('affiliation_number', 100)->nullable();
            $table->string('affiliation_type', 50)->nullable();
            $table->date('affiliation_valid_from')->nullable();
            $table->date('affiliation_valid_to')->nullable();
            $table->string('recognition_status', 50)->nullable();
            $table->boolean('ugc_recognition')->default(0);
            $table->string('naac_grade', 20)->nullable();
            $table->date('naac_valid_upto')->nullable();
            $table->integer('nirf_rank')->nullable();
            $table->string('professional_council', 255)->nullable();
            $table->string('professional_approval_number', 100)->nullable();

            $table->string('place', 150)->nullable();
            $table->text('address')->nullable();
            $table->string('district', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('postal_code', 15)->nullable();
            $table->string('country', 100)->default('India')->nullable();
            $table->string('latitude', 100)->nullable();
            $table->string('longitude', 100)->nullable();
            $table->string('phone_num1', 20)->nullable();
            $table->string('phone_num2', 20)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('website', 255)->nullable();

            $table->string('principal_name', 150)->nullable();
            $table->string('principal_mobile', 20)->nullable();
            $table->string('principal_email', 150)->nullable();
            $table->string('director_name', 150)->nullable();
            $table->string('registrar_name', 150)->nullable();
            $table->string('manager_name', 150)->nullable();


            // 5. Academic Settings
            $table->string('academic_year', 20)->nullable();
            $table->date('starting_date')->nullable();
            $table->tinyInteger('start_month')->nullable();
            $table->boolean('semester_system')->default(0);
            $table->boolean('credit_system')->default(0);
            $table->string('grading_system', 50)->nullable();
            $table->decimal('default_course_duration_years', 3, 1)->nullable();
            $table->boolean('is_attendance')->default(0);
            $table->boolean('display_result')->default(0);
            $table->boolean('examination_secure')->default(0);
            $table->boolean('promotion_enable')->default(0);


            // 6. College Branding and Documents
            $table->string('college_logo', 255)->nullable();
            $table->string('college_photo', 255)->nullable();
            $table->string('college_stamp', 255)->nullable();
            $table->string('principal_signature', 255)->nullable();
            $table->string('director_signature', 255)->nullable();
            $table->string('header_image', 255)->nullable();
            $table->string('report_card_header_image', 255)->nullable();
            $table->string('college_tag_line', 255)->nullable();


            $table->dateTime('online_adm_from_date')->nullable();
            $table->dateTime('online_adm_to_date')->nullable();
            $table->integer('form_limit')->nullable();
            $table->text('study_material_url')->nullable();
            $table->text('app_andr_url')->nullable();
            $table->text('app_ios_url')->nullable();
            $table->string('facebook_page', 255)->nullable();
            $table->string('instagram_page', 255)->nullable();
            $table->string('linkedin_page', 255)->nullable();
            $table->string('youtube_page', 255)->nullable();
            $table->string('x_page', 255)->nullable();
            $table->boolean('status')->default(1);
            $table->string('ip_address', 45)->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('institutes');
    }
};
