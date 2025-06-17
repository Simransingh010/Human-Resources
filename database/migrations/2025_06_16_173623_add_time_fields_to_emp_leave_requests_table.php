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
        Schema::table('emp_leave_requests', function (Blueprint $table) {
            $table->boolean('time_from')->nullable()->after('apply_days');
            $table->boolean('time_to')->nullable()->after('time_from');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('emp_leave_requests', function (Blueprint $table) {
            $table->dropColumn('time_from');
            $table->dropColumn('time_to');
        });
    }
};
