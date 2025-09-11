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
        Schema::create('final_settlements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('firm_id');
            $table->unsignedBigInteger('exit_id');
            $table->unsignedBigInteger('employee_id');
            $table->date('settlement_date');
            $table->unsignedBigInteger('disburse_payroll_slot_id')->nullable();
            $table->decimal('fnf_earning_amount', 12, 2)->default(0);
            $table->decimal('fnf_deduction_amount', 12, 2)->default(0);
            $table->string('full_final_status')->default('pending');
            $table->text('remarks')->nullable();
            $table->string('additional_rule')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('final_settlements');
    }
};
