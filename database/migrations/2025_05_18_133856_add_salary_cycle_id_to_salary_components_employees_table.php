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
        Schema::table('salary_components_employees', function (Blueprint $table) {
            // 1) add the new column (nullable so existing records won’t break)
            $table->unsignedBigInteger('salary_cycle_id')
                ->nullable()
                ->after('salary_template_id');

            // 2) add foreign key constraint
            $table->foreign('salary_cycle_id')
                ->references('id')
                ->on('salary_cycles')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('salary_components_employees', function (Blueprint $table) {
            // 1) drop the FK
            $table->dropForeign(['salary_cycle_id']);

            // 2) drop the column
            $table->dropColumn('salary_cycle_id');
        });
    }
};
