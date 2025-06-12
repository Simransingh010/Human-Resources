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
        Schema::table('tax_brackets', function (Blueprint $table) {
            $table->string('apply_breakdown_rate')->nullable()->after('rate');
            //
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tax_brackets', function (Blueprint $table) {
            //
            $table->dropColumn('apply_breakdown_rate');
        });
    }
};
