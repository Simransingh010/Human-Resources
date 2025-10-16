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
        // Update apps table
        Schema::table('apps', function (Blueprint $table) {
            $table->string('wire')->nullable()->after('code');
        });

        // Update modules table
        Schema::table('modules', function (Blueprint $table) {
            $table->string('wire')->nullable()->after('code');
        });

        // Update components table
        Schema::table('components', function (Blueprint $table) {
            $table->string('wire')->nullable()->after('code');
        });

        // Update actions table
        Schema::table('actions', function (Blueprint $table) {
            $table->string('wire')->nullable()->after('code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop wire column from apps table
        Schema::table('apps', function (Blueprint $table) {
            $table->dropColumn('wire');
        });

        // Drop wire column from modules table
        Schema::table('modules', function (Blueprint $table) {
            $table->dropColumn('wire');
        });

        // Drop wire column from components table
        Schema::table('components', function (Blueprint $table) {
            $table->dropColumn('wire');
        });

        // Drop wire column from actions table
        Schema::table('actions', function (Blueprint $table) {
            $table->dropColumn('wire');
        });
    }
};
