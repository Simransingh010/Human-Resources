<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('payroll_components_employees_tracks', function (Blueprint $table) {
            $table->text('remarks')->nullable()->after('salary_cycle_id');
        });
    }

    public function down()
    {
        Schema::table('payroll_components_employees_tracks', function (Blueprint $table) {
            $table->dropColumn('remarks');
        });
    }
};
