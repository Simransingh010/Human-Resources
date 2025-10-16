<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // 1) DROP obsolete tables
        Schema::dropIfExists('emp_leave_allocations');
        Schema::dropIfExists('emp_leave_request_logs');

        // 2) MODIFY existing tables


        // a) leaves_quota_template_setups → is_inactive + soft deletes
        Schema::table('leaves_quota_template_setups', function (Blueprint $table) {
            $table->boolean('is_inactive')->default(false)->after('alloc_period_value');
        });



        // 3) NEW tables

        // a) leave_approval_rules
        Schema::create('leave_approval_rules', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('firm_id')->constrained()->cascadeOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->foreignId('leave_type_id')
                ->nullable()
                ->constrained('leave_types')
                ->nullOnDelete();
            $table->string('department_scope')->default('all');        // all | specified
            $table->string('employee_scope')->default('all');        // all | specified
            $table->tinyInteger('approval_level');
            $table->string('approval_mode')->default('any');         // any | all
            $table->boolean('auto_approve')->default(false);         // if no human intervention rquired in approval
            $table->foreignId('approver_id')
                ->constrained('users')
                ->nullable()
                ->cascadeOnDelete();
            $table->decimal('min_days', 8, 2)->nullable();
            $table->decimal('max_days', 8, 2)->nullable();
            $table->boolean('is_inactive')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['firm_id','period_start'], 'idx_leave_rules_firm_period');
        });

        // b) pivot: employee_leave_approval_rule
        Schema::create('employee_leave_approval_rule', function (Blueprint $table) {
            $table->foreignId('rule_id')
                ->constrained('leave_approval_rules')
                ->cascadeOnDelete();
            $table->foreignId('employee_id')
                ->constrained('employees')
                ->cascadeOnDelete();
            $table->primary(['rule_id','employee_id']);
        });

        Schema::create('department_leave_approval_rule', function (Blueprint $table) {
            $table->foreignId('rule_id')
                ->constrained('leave_approval_rules')
                ->cascadeOnDelete();
            $table->foreignId('department_id')
                ->constrained('departments')
                ->cascadeOnDelete();
            $table->primary(['rule_id','department_id']);
        });

        // c) emp_leave_request_approvals
        Schema::create('emp_leave_request_approvals', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('emp_leave_request_id')
                ->constrained('emp_leave_requests')
                ->cascadeOnDelete();
            $table->tinyInteger('approval_level');
            $table->foreignId('approver_id')
                ->constrained('users')
                ->nullable()
                ->cascadeOnDelete();
            $table->string('status')->default('applied');            // pending|approved|rejected|needs_info
            $table->text('remarks')->nullable();
            $table->timestamp('acted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['emp_leave_request_id','approval_level'], 'idx_ela_req_level');
            $table->index('approver_id');
            $table->index('status');
        });

        // d) emp_leave_balance
        Schema::create('emp_leave_balance', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('firm_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('leave_type_id')
                ->constrained('leave_types')
                ->cascadeOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('allocated_days', 8, 2)->default(0);
            $table->decimal('consumed_days', 8, 2)->default(0);
            $table->decimal('carry_forwarded_days', 8, 2)->default(0);
            $table->decimal('lapsed_days', 8, 2)->default(0);
            $table->decimal('balance', 8, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(
                ['firm_id','employee_id','leave_type_id','period_start'],
                'u_emp_leave_balance'
            );
        });

        // e) emp_leave_transactions
        Schema::create('emp_leave_transactions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('leave_balance_id')
                ->constrained('emp_leave_balance')
                ->cascadeOnDelete();
            $table->string('transaction_type');   // allocation|consumption|carry_forward|lapse
            $table->date('transaction_date');
            $table->decimal('amount', 8, 2);
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->foreignId('created_by')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->timestamps();
            // no softDeletes here—transactions are immutable
            $table->index(['leave_balance_id','transaction_type'], 'idx_elt_balance_type');
        });



        Schema::create('leave_request_events', function (Blueprint $table) {
            $table->bigIncrements('id');

            // Which leave request
            $table->foreignId('emp_leave_request_id')
                ->constrained('emp_leave_requests')
                ->cascadeOnDelete();

            // Who did it (null = system-generated)
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // What kind of event
            // e.g. status_change, clarification_requested, clarification_provided
            $table->string('event_type');

            // Only used when event_type = 'status_change'
            $table->string('from_status')->nullable();
            $table->string('to_status')->nullable();

            // Free-form message:
            // - for approvals you can store “Level 1 approved”
            // - for clarifications you store the question or answer
            $table->text('remarks')->nullable();

            $table->timestamp('created_at')
                ->useCurrent();

            // soft-delete in case you ever need to hide an event
            $table->softDeletes();

            // Indexes for querying by request or type
            $table->index('emp_leave_request_id');
            $table->index('event_type');
        });



    }

    public function down()
    {
        // drop child tables first:
        Schema::dropIfExists('leave_request_events');
        Schema::dropIfExists('emp_leave_transactions');
        Schema::dropIfExists('emp_leave_balance');
        Schema::dropIfExists('emp_leave_request_approvals');
        Schema::dropIfExists('department_leave_approval_rule'); // ← add this
        Schema::dropIfExists('employee_leave_approval_rule');
        Schema::dropIfExists('leave_approval_rules');
        // drop new tables


        // revert modifications


        // leaves_quota_template_setups
        Schema::table('leaves_quota_template_setups', function (Blueprint $table) {
            $table->dropColumn('is_inactive');
        });






    }
};
