<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        //
        // 1) DROP the old composite pivots if they exist
        //
        Schema::dropIfExists('employee_leave_approval_rule');
        Schema::dropIfExists('department_leave_approval_rule');

        //
        // 2) RECREATE employee_leave_approval_rule with id + firm_id
        //
        Schema::create('employee_leave_approval_rule', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('firm_id')
                ->constrained('firms')
                ->cascadeOnDelete();
            $table->foreignId('rule_id')
                ->constrained('leave_approval_rules')
                ->cascadeOnDelete();
            $table->foreignId('employee_id')
                ->constrained('employees')
                ->cascadeOnDelete();
            $table->boolean('is_inactive')->default(false);
            $table->timestamps();
            $table->softDeletes();

            // preserve the one‐rule/one‐employee constraint
            $table->unique(['rule_id','employee_id'], 'u_rule_employee');
        });

        //
        // 3) RECREATE department_leave_approval_rule with id + firm_id
        //
        Schema::create('department_leave_approval_rule', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('firm_id')
                ->constrained('firms')
                ->cascadeOnDelete();
            $table->foreignId('rule_id')
                ->constrained('leave_approval_rules')
                ->cascadeOnDelete();
            $table->foreignId('department_id')
                ->constrained('departments')
                ->cascadeOnDelete();
            $table->boolean('is_inactive')->default(false);
            $table->timestamps();
            $table->softDeletes();

            // preserve the one‐rule/one‐department constraint
            $table->unique(['rule_id','department_id'], 'u_rule_department');
        });

        //
        // 4) Add firm_id to emp_leave_request_approvals
        //
        Schema::table('emp_leave_request_approvals', function (Blueprint $table) {
            $table->foreignId('firm_id')
                ->constrained('firms')
                ->cascadeOnDelete()
                ->after('id');
        });

        //
        // 5) Add firm_id to emp_leave_transactions
        //
        Schema::table('emp_leave_transactions', function (Blueprint $table) {
            $table->foreignId('firm_id')
                ->constrained('firms')
                ->cascadeOnDelete()
                ->after('id');
        });

        //
        // 6) Add firm_id to leave_request_events
        //
        Schema::table('leave_request_events', function (Blueprint $table) {
            $table->foreignId('firm_id')
                ->constrained('firms')
                ->cascadeOnDelete()
                ->after('id');
        });
    }

    public function down()
    {
        //
        // 6) Remove firm_id from leave_request_events
        //
        Schema::table('leave_request_events', function (Blueprint $table) {
            $table->dropForeign(['firm_id']);
            $table->dropColumn('firm_id');
        });

        //
        // 5) Remove firm_id from emp_leave_transactions
        //
        Schema::table('emp_leave_transactions', function (Blueprint $table) {
            $table->dropForeign(['firm_id']);
            $table->dropColumn('firm_id');
        });

        //
        // 4) Remove firm_id from emp_leave_request_approvals
        //
        Schema::table('emp_leave_request_approvals', function (Blueprint $table) {
            $table->dropForeign(['firm_id']);
            $table->dropColumn('firm_id');
        });

        //
        // 3) Drop and restore department_leave_approval_rule
        //
        Schema::dropIfExists('department_leave_approval_rule');
        Schema::create('department_leave_approval_rule', function (Blueprint $table) {
            $table->foreignId('rule_id')
                ->constrained('leave_approval_rules')
                ->cascadeOnDelete();
            $table->foreignId('department_id')
                ->constrained('departments')
                ->cascadeOnDelete();
            $table->primary(['rule_id','department_id']);
        });

        //
        // 2) Drop and restore employee_leave_approval_rule
        //
        Schema::dropIfExists('employee_leave_approval_rule');
        Schema::create('employee_leave_approval_rule', function (Blueprint $table) {
            $table->foreignId('rule_id')
                ->constrained('leave_approval_rules')
                ->cascadeOnDelete();
            $table->foreignId('employee_id')
                ->constrained('employees')
                ->cascadeOnDelete();
            $table->primary(['rule_id','employee_id']);
        });
    }
};
