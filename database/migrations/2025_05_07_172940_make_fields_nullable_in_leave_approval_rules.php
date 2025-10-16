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
        Schema::table('leave_approval_rules', function (Blueprint $table) {
            // set these columns to nullable
            $table->string('department_scope')->nullable()->change();
            $table->string('employee_scope')->nullable()->change();
            $table->unsignedBigInteger('approver_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leave_approval_rules', function (Blueprint $table) {
            // revert back to not nullable
            $table->string('department_scope')->nullable(false)->change();
            $table->string('employee_scope')->nullable(false)->change();
            $table->unsignedBigInteger('approver_id')->nullable(false)->change();
        });
    }
};
