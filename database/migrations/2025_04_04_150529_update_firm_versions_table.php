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
        Schema::table('firm_versions', function (Blueprint $table) {
            $table->string('device_type',10)->after('type')->nullable();
            // Ensure unique combination per firm and type.
            $table->unique(['firm_id', 'version_id', 'type','device_type'], 'firm_versions_type_unique');
//            Android (android)
//            Ios (ios)
//            Web (web)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('firm_versions', function (Blueprint $table) {
            $table->dropColumn(['device_type']);
        });
    }
};
