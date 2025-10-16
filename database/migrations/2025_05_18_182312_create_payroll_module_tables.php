<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. salary_execution_groups
        Schema::create('salary_execution_groups', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('firm_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('salary_cycle_id')->nullable();
            $table->boolean('is_inactive')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('firm_id', 'seg_firm')
                ->references('id')->on('firms')->onDelete('cascade');
            $table->foreign('salary_cycle_id', 'seg_cycle')
                ->references('id')->on('salary_cycles')->onDelete('cascade');
        });

        // 2. employees_salary_execution_group
        Schema::create('employees_salary_execution_group', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('firm_id');
            $table->unsignedBigInteger('salary_execution_group_id');
            $table->unsignedBigInteger('employee_id');
            $table->timestamps();

            $table->unique(
                ['firm_id','salary_execution_group_id','employee_id'],
                'eseg_uniq'
            );

            $table->foreign('firm_id', 'eseg_firm')
                ->references('id')->on('firms')->onDelete('cascade');
            $table->foreign('salary_execution_group_id', 'eseg_segrp')
                ->references('id')->on('salary_execution_groups')->onDelete('cascade');
            $table->foreign('employee_id', 'eseg_emp')
                ->references('id')->on('employees')->onDelete('cascade');
        });

        // 3. payroll_steps
        Schema::create('payroll_steps', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('firm_id');
            $table->string('step_code_main');
            $table->string('step_title');
            $table->text('step_desc')->nullable();
            $table->boolean('required')->default(false);
            $table->integer('step_order')->default(0);
            $table->boolean('is_inactive')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['firm_id','step_code_main'], 'ps_steps_uniq');
            $table->foreign('firm_id', 'ps_firm')
                ->references('id')->on('firms')->onDelete('cascade');
        });

        // 4. payroll_slots
        Schema::create('payroll_slots', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('firm_id');
            $table->unsignedBigInteger('salary_cycle_id')->nullable();
            $table->unsignedBigInteger('salary_execution_group_id')->nullable();
            $table->date('from_date');
            $table->date('to_date');
            $table->string('payroll_slot_status');
            $table->string('title');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(
                ['firm_id','salary_cycle_id','salary_execution_group_id','from_date','to_date','deleted_at'],
                'pslot_uniq'
            );
            $table->foreign('firm_id', 'pslot_firm')
                ->references('id')->on('firms')->onDelete('cascade');
            $table->foreign('salary_cycle_id', 'pslot_cycle')
                ->references('id')->on('salary_cycles')->onDelete('cascade');
            $table->foreign('salary_execution_group_id', 'pslot_segrp')
                ->references('id')->on('salary_execution_groups')->onDelete('cascade');
        });

        // 5. payroll_slots_cmds
        Schema::create('payroll_slots_cmds', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('firm_id');
            $table->unsignedBigInteger('payroll_slot_id');
            $table->string('payroll_slot_status');
            $table->text('run_payroll_remarks')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();

            $table->foreign('firm_id', 'pscmd_firm')
                ->references('id')->on('firms')->onDelete('cascade');
            $table->foreign('payroll_slot_id', 'pscmd_slot')
                ->references('id')->on('payroll_slots')->onDelete('cascade');
            $table->foreign('user_id', 'pscmd_user')
                ->references('id')->on('users')->onDelete('set null');
        });

        // 6. payroll_step_payroll_slot
        Schema::create('payroll_step_payroll_slot', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('firm_id');
            $table->unsignedBigInteger('payroll_slot_id');
            $table->string('step_code_main');
            $table->unsignedBigInteger('payroll_step_id');
            $table->string('payroll_step_status');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['firm_id','payroll_slot_id','payroll_step_id'], 'psps_uniq');
            $table->foreign('firm_id', 'psps_firm')
                ->references('id')->on('firms')->onDelete('cascade');
            $table->foreign('payroll_slot_id', 'psps_slot')
                ->references('id')->on('payroll_slots')->onDelete('cascade');
            $table->foreign('payroll_step_id', 'psps_step')
                ->references('id')->on('payroll_steps')->onDelete('cascade');
        });

        // 7. payroll_step_payroll_slot_cmds
        Schema::create('payroll_step_payroll_slot_cmds', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('firm_id');
            $table->unsignedBigInteger('payroll_step_payroll_slot_id');
            $table->string('payroll_step_status');
            $table->text('step_remarks')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();

            $table->foreign('firm_id', 'pspscmd_firm')
                ->references('id')->on('firms')->onDelete('cascade');
            $table->foreign('payroll_step_payroll_slot_id', 'pspscmd_psps')
                ->references('id')->on('payroll_step_payroll_slot')->onDelete('cascade');
            $table->foreign('user_id', 'pspscmd_user')
                ->references('id')->on('users')->onDelete('set null');
        });

        // 8. employees_salary_days
        Schema::create('employees_salary_days', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('firm_id');
            $table->unsignedBigInteger('payroll_slot_id');
            $table->unsignedBigInteger('employee_id');
            $table->integer('cycle_days')->default(0);
            $table->integer('void_days_count')->default(0);
            $table->integer('lop_days_count')->default(0);
            $table->timestamps();

            $table->unique(['firm_id','payroll_slot_id','employee_id'], 'esd_uniq');
            $table->foreign('firm_id', 'esd_firm')
                ->references('id')->on('firms')->onDelete('cascade');
            $table->foreign('payroll_slot_id', 'esd_slot')
                ->references('id')->on('payroll_slots')->onDelete('cascade');
            $table->foreign('employee_id', 'esd_emp')
                ->references('id')->on('employees')->onDelete('cascade');
        });

        // 9. employees_lop_days_logs
        Schema::create('employees_lop_days_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('firm_id');
            $table->unsignedBigInteger('payroll_slot_id');
            $table->unsignedBigInteger('payroll_step_payroll_slot_id');
            $table->integer('lop_days_count')->default(0);
            $table->string('creation_mode');
            $table->text('creation_remarks')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();

            $table->foreign('firm_id', 'elop_firm')
                ->references('id')->on('firms')->onDelete('cascade');
            $table->foreign('payroll_slot_id', 'elop_slot')
                ->references('id')->on('payroll_slots')->onDelete('cascade');
            $table->foreign('payroll_step_payroll_slot_id', 'elop_psps')
                ->references('id')->on('payroll_step_payroll_slot')->onDelete('cascade');
            $table->foreign('user_id', 'elop_user')
                ->references('id')->on('users')->onDelete('set null');
        });

        // 10. salary_advances  ← must come before tracks
        Schema::create('salary_advances', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('firm_id');
            $table->unsignedBigInteger('employee_id');
            $table->date('advance_date');
            $table->decimal('amount', 15, 2)->default(0);
            $table->integer('installments')->default(0);
            $table->decimal('installment_amount', 15, 2)->default(0);
            $table->decimal('recovered_amount', 15, 2)->default(0);
            $table->string('advance_status');
            $table->boolean('is_inactive')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['firm_id','employee_id','advance_date'], 'sa_uniq');
            $table->foreign('firm_id', 'sa_firm')
                ->references('id')->on('firms')->onDelete('cascade');
            $table->foreign('employee_id', 'sa_emp')
                ->references('id')->on('employees')->onDelete('cascade');
        });

        // 11. salary_arrears  ← must come before tracks
        Schema::create('salary_arrears', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('firm_id');
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('salary_component_id');
            $table->date('effective_from');
            $table->date('effective_to');
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->integer('installments')->default(0);
            $table->decimal('installment_amount', 15, 2)->default(0);
            $table->string('arrear_status');
            $table->boolean('is_inactive')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(
                ['firm_id','employee_id','salary_component_id','effective_from','effective_to'],
                'sar_uniq'
            );
            $table->foreign('firm_id', 'sar_firm')
                ->references('id')->on('firms')->onDelete('cascade');
            $table->foreign('employee_id', 'sar_emp')
                ->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('salary_component_id', 'sar_comp')
                ->references('id')->on('salary_components')->onDelete('cascade');
        });

        // 12. payroll_components_employees_tracks
        Schema::create('payroll_components_employees_tracks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('firm_id');
            $table->unsignedBigInteger('payroll_slot_id');
            $table->unsignedBigInteger('payroll_slots_cmd_id');
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('salary_template_id');
            $table->unsignedBigInteger('salary_component_group_id');
            $table->unsignedBigInteger('salary_component_id');
            $table->integer('sequence')->default(0);
            $table->string('nature');
            $table->string('component_type');
            $table->string('amount_type');
            $table->boolean('taxable')->default(false);
            $table->json('calculation_json')->nullable();
            $table->date('salary_period_from');
            $table->date('salary_period_to');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->decimal('amount_full', 15, 2)->default(0);
            $table->decimal('amount_payable', 15, 2)->default(0);
            $table->decimal('amount_paid', 15, 2)->default(0);
            $table->unsignedBigInteger('salary_advance_id')->nullable();
            $table->unsignedBigInteger('salary_arrear_id')->nullable();
            $table->unsignedBigInteger('salary_cycle_id')->nullable();
            $table->timestamps();

            $table->unique(
                ['firm_id','payroll_slot_id','employee_id','salary_component_id'],
                'pcet_uniq'
            );
            $table->foreign('firm_id', 'pcet_firm')
                ->references('id')->on('firms')->onDelete('cascade');
            $table->foreign('payroll_slot_id', 'pcet_slot')
                ->references('id')->on('payroll_slots')->onDelete('cascade');
            $table->foreign('payroll_slots_cmd_id', 'pcet_pscmd')
                ->references('id')->on('payroll_slots_cmds')->onDelete('cascade');
            $table->foreign('employee_id', 'pcet_emp')
                ->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('salary_template_id', 'pcet_temp')
                ->references('id')->on('salary_templates')->onDelete('cascade');
            $table->foreign('salary_component_group_id', 'pcet_cgrp')
                ->references('id')->on('salary_component_groups')->onDelete('cascade');
            $table->foreign('salary_component_id', 'pcet_comp')
                ->references('id')->on('salary_components')->onDelete('cascade');
            $table->foreign('user_id', 'pcet_user')
                ->references('id')->on('users')->onDelete('set null');
            $table->foreign('salary_advance_id', 'pcet_adv')
                ->references('id')->on('salary_advances')->onDelete('cascade');
            $table->foreign('salary_arrear_id', 'pcet_arr')
                ->references('id')->on('salary_arrears')->onDelete('cascade');
            $table->foreign('salary_cycle_id', 'pcet_cycle')
                ->references('id')->on('salary_cycles')->onDelete('cascade');
        });

        // 13. firm_bank_accounts
        Schema::create('firm_bank_accounts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('firm_id');
            $table->string('account_name');
            $table->string('account_number');
            $table->string('bank_name');
            $table->text('bank_address')->nullable();
            $table->string('ifsc_code');
            $table->boolean('is_inactive')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['firm_id','account_number'], 'fba_uniq');
            $table->foreign('firm_id', 'fba_firm')
                ->references('id')->on('firms')->onDelete('cascade');
        });

        // 14. salary_disbursement_batches
        Schema::create('salary_disbursement_batches', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('firm_id');
            $table->date('transaction_date');
            $table->decimal('amount', 15, 2)->default(0);
            $table->text('memo')->nullable();
            $table->string('mode');
            $table->unsignedBigInteger('firm_bank_account_id');
            $table->boolean('is_inactive')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('firm_id', 'sdb_firm')
                ->references('id')->on('firms')->onDelete('cascade');
            $table->foreign('firm_bank_account_id', 'sdb_fba')
                ->references('id')->on('firm_bank_accounts')->onDelete('cascade');
        });

        // 15. employee_paid_salary_components
        Schema::create('employee_paid_salary_components', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('firm_id');
            $table->unsignedBigInteger('salary_disbursement_batch_id');
            $table->unsignedBigInteger('payroll_components_employees_track_id');
            $table->decimal('amount_paid', 15, 2)->default(0);
            $table->timestamps();

            $table->unique(
                ['salary_disbursement_batch_id','payroll_components_employees_track_id'],
                'epsc_uniq'
            );
            $table->foreign('firm_id', 'epsc_firm')
                ->references('id')->on('firms')->onDelete('cascade');
            $table->foreign('salary_disbursement_batch_id', 'epsc_sdb')
                ->references('id')->on('salary_disbursement_batches')->onDelete('cascade');
            $table->foreign('payroll_components_employees_track_id', 'epsc_pcet')
                ->references('id')->on('payroll_components_employees_tracks')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_paid_salary_components');
        Schema::dropIfExists('salary_disbursement_batches');
        Schema::dropIfExists('firm_bank_accounts');
        Schema::dropIfExists('payroll_components_employees_tracks');
        Schema::dropIfExists('salary_arrears');
        Schema::dropIfExists('salary_advances');
        Schema::dropIfExists('employees_lop_days_logs');
        Schema::dropIfExists('employees_salary_days');
        Schema::dropIfExists('payroll_step_payroll_slot_cmds');
        Schema::dropIfExists('payroll_step_payroll_slot');
        Schema::dropIfExists('payroll_slots_cmds');
        Schema::dropIfExists('payroll_slots');
        Schema::dropIfExists('payroll_steps');
        Schema::dropIfExists('employees_salary_execution_group');
        Schema::dropIfExists('salary_execution_groups');
    }
};
