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
        Schema::table('salary_templates_components', function (Blueprint $table) {
         $table->date('effective_from')->nullable()->after('sequence');
         $table->date('effective_to')->nullable()->after('effective_from');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('salary_templates_components', function (Blueprint $table) {
            $table->dropColumn('effective_from');
            $table->dropColumn('effective_to');
        });
    }
};
