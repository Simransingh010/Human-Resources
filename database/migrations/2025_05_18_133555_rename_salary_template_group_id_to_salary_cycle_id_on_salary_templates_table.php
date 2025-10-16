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
        Schema::table('salary_templates', function (Blueprint $table) {
            // 1) drop existing FK (if it exists)
            $table->dropForeign(['salary_template_group_id']);

            // 2) rename the column
            $table->renameColumn('salary_template_group_id', 'salary_cycle_id');

            // 3) re-add FK to salary_cycles.id
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
        Schema::table('salary_templates', function (Blueprint $table) {
            // 1) drop the new FK
            $table->dropForeign(['salary_cycle_id']);

            // 2) rename the column back
            $table->renameColumn('salary_cycle_id', 'salary_template_group_id');

            // 3) re-add FK to the old table
            $table->foreign('salary_template_group_id')
                ->references('id')
                ->on('salary_template_groups')
                ->onDelete('cascade');
        });
    }
};
