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
        Schema::table('versions', function (Blueprint $table) {
            $table->string('device_type',10)->after('description')->nullable();
            $table->unique(['device_type', 'code'], 'versions_device_type_code_unique');

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
        Schema::table('versions', function (Blueprint $table) {
            $table->dropColumn(['device_type']);
        });
    }
};
