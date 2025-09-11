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
        Schema::table('emp_attendances', function (Blueprint $table) {
            if (!Schema::hasColumn('emp_attendances', 'is_overnight')) {
                $table->boolean('is_overnight')->default(0)->after('attend_remarks');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('emp_attendances', function (Blueprint $table) {
            if (Schema::hasColumn('emp_attendances', 'is_overnight')) {
                $table->dropColumn('is_overnight');
            }
        });
    }
};
