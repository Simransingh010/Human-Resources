<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('study_centres', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('firm_id');
            $table->string('name');
            $table->string('code')->nullable();
            $table->year('established_year')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->boolean('is_inactive')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('firm_id')->references('id')->on('firms')->onDelete('cascade');
        });

        // Add foreign key constraints to tables that reference study_centres if they exist
        if (Schema::hasTable('students')) {
            Schema::table('students', function (Blueprint $table) {
                $table->foreign('study_centre_id')->references('id')->on('study_centres')->onDelete('set null');
            });
        }

        if (Schema::hasTable('student_education_details')) {
            Schema::table('student_education_details', function (Blueprint $table) {
                $table->foreign('study_centre_id')->references('id')->on('study_centres')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop foreign keys from tables that reference study_centres if they exist
        if (Schema::hasTable('students') && Schema::hasColumn('students', 'study_centre_id')) {
            Schema::table('students', function (Blueprint $table) {
                $table->dropForeign(['study_centre_id']);
            });
        }

        if (Schema::hasTable('student_education_details') && Schema::hasColumn('student_education_details', 'study_centre_id')) {
            Schema::table('student_education_details', function (Blueprint $table) {
                $table->dropForeign(['study_centre_id']);
            });
        }

        Schema::dropIfExists('study_centres');
    }
};
