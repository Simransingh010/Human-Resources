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
        Schema::create('salary_changes_employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firm_id')->constrained('firms');
            $table->foreignId('employee_id')->constrained('employees');
            $table->foreignId('old_salary_components_employee_id')->constrained('salary_components_employees');
            $table->foreignId('new_salary_components_employee_id')->constrained('salary_components_employees');
            $table->date('old_effective_to');
            $table->text('remarks')->nullable();
            $table->json('changes_details_json');
            $table->timestamps();
            
            // Indexes for better performance
            $table->index(['firm_id', 'employee_id']);
            $table->index('old_effective_to');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salary_changes_employees');
    }
};