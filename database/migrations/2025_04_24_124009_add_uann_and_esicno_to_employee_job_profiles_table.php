<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_job_profiles', function (Blueprint $table) {
            $table->string('uanno')->nullable()->after('doe');
            $table->string('esicno')->nullable()->after('uanno');
            $table->unsignedBigInteger('joblocation_id')->nullable()->after('esicno');

            $table->foreign('joblocation_id')
                ->references('id')->on('joblocations')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('employee_job_profiles', function (Blueprint $table) {
            $table->dropForeign(['joblocation_id']);
            $table->dropColumn(['uanno', 'esicno', 'joblocation_id']);
        });
    }
};
