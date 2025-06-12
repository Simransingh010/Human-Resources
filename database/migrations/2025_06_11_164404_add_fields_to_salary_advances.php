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
        Schema::table('salary_advances', function (Blueprint $table) {
            $table->string('disburse_salary_component')->nullable()->after('advance_status');
            $table->string('recovery_salary_component')->nullable()->after('disburse_salary_component');
            $table->foreignId('disburse_payroll_slot_id')->nullable()->constrained('payroll_slots')->onDelete('set null')->after('recovery_salary_component');
            $table->foreignId('recovery_wef_payroll_slot_id')->nullable()->constrained('payroll_slots')->onDelete('set null')->after('disburse_payroll_slot_id');
            $table->text('additional_rule_remarks')->nullable()->after('recovery_wef_payroll_slot_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('salary_advances', function (Blueprint $table) {
            $table->dropForeign(['disburse_payroll_slot_id']);
            $table->dropColumn('disburse_payroll_slot_id');
            $table->dropForeign(['recovery_wef_payroll_slot_id']);
            $table->dropColumn('recovery_wef_payroll_slot_id');
            $table->dropColumn('disburse_salary_component');
            $table->dropColumn('recovery_salary_component');
            $table->dropColumn('additional_rule_remarks');
        });
    }
};
