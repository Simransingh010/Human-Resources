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
        Schema::table('salary_arrears', function (Blueprint $table) {
            $table->foreignId('disburse_wef_payroll_slot_id')->nullable()->constrained('payroll_slots')->onDelete('set null')->after('is_inactive');
            $table->string('additional_rule')->nullable()->after('disburse_wef_payroll_slot_id');
            $table->text('remarks')->nullable()->after('additional_rule');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('salary_arrears', function (Blueprint $table) {
            $table->dropForeign(['disburse_wef_payroll_slot_id']);
            $table->dropColumn('disburse_wef_payroll_slot_id');
            $table->dropColumn('additional_rule');
            $table->dropColumn('remarks');
        });
    }
};
