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
            // Composite index for the main query pattern
            $table->index(['employee_id', 'effective_to', 'firm_id'], 'idx_emp_effective_firm');
            
            // Index for firm_id lookups
            $table->index(['firm_id', 'effective_to'], 'idx_firm_effective');
            
            // Index for component lookups
            $table->index(['salary_component_id', 'firm_id'], 'idx_component_firm');
            
            // Index for active components (where effective_to is null or future)
            $table->index(['firm_id', 'deleted_at', 'effective_to'], 'idx_active_components');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('salary_components_employees', function (Blueprint $table) {
            $table->dropIndex('idx_emp_effective_firm');
            $table->dropIndex('idx_firm_effective');
            $table->dropIndex('idx_component_firm');
            $table->dropIndex('idx_active_components');
        });
    }
}; 