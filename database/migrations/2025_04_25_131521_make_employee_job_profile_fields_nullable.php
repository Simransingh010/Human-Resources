<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_job_profiles', function (Blueprint $table) {
            $table->string('employee_code')->nullable()->change();
            $table->unsignedBigInteger('department_id')->nullable()->change();
            $table->unsignedBigInteger('designation_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('employee_job_profiles', function (Blueprint $table) {
            $table->string('employee_code')->nullable(false)->change();
            $table->unsignedBigInteger('department_id')->nullable(false)->change();
            $table->unsignedBigInteger('designation_id')->nullable(false)->change();
        });
    }
};
