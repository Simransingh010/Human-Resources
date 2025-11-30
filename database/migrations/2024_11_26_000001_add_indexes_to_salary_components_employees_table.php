<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salary_components_employees', function (Blueprint $table) {
            // Add composite index for the most common query pattern
            $table->index(['employee_id', 'firm_id', 'effective_to'], 'idx_employee_firm_effective');
            
            // Add index for salary_component_id lookups
            $table->index(['salary_component_id', 'firm_id'], 'idx_component_firm');
            
            // Add index for firm_id + effective_to (for active components query)
            $table->index(['firm_id', 'effective_to'], 'idx_firm_effective');
        });
    }

    public function down(): void
    {
        Schema::table('salary_components_employees', function (Blueprint $table) {
            $table->dropIndex('idx_employee_firm_effective');
            $table->dropIndex('idx_component_firm');
            $table->dropIndex('idx_firm_effective');
        });
    }
};
