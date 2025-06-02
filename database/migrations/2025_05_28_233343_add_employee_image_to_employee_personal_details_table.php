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
        Schema::table('employee_personal_details', function (Blueprint $table) {
            $table->string('employee_image')->nullable()->after('panno');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_personal_details', function (Blueprint $table) {
            $table->dropColumn('employee_image');
        });
    }
};
