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
        // 1) drop the foreign key & column from the old table
        Schema::table('salary_template_groups', function (Blueprint $table) {
            // adjust the constraint name if you used a custom one
            $table->dropForeign(['parent_salary_template_group_id']);
            $table->dropColumn('parent_salary_template_group_id');
        });

        // 2) rename the table
        Schema::rename('salary_template_groups', 'salary_cycles');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1) rename it back
        Schema::rename('salary_cycles', 'salary_template_groups');

        // 2) re-add the column & its FK
        Schema::table('salary_template_groups', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_salary_template_group_id')
                ->nullable()
                ->after('description');

            $table->foreign('parent_salary_template_group_id')
                ->references('id')
                ->on('salary_template_groups')
                ->onDelete('cascade');
        });
    }
};
