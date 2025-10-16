<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('payroll_components_employees_tracks', function (Blueprint $table) {
            $table->unsignedBigInteger('salary_component_group_id')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('payroll_components_employees_tracks', function (Blueprint $table) {
            $table->unsignedBigInteger('salary_component_group_id')->nullable(false)->change();
        });
    }
};
