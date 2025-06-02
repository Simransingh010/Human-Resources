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
        Schema::table('employee_job_profiles', function (Blueprint $table) {
            $table->string('pran_number')->nullable()->after('joblocation_id');
            $table->string('paylevel')->nullable()->after('pran_number');
            $table->string('rf_id')->nullable()->after('paylevel');
            $table->string('biometric_emp_code')->nullable()->after('rf_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_job_profiles', function (Blueprint $table) {
            $table->dropColumn('pran_number');
            $table->dropColumn('paylevel');
            $table->dropColumn('rf_id');
            $table->dropColumn('biometric_emp_code');
        });
    }
};
