<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('emp_leave_transactions', function (Blueprint $table) {
            // when you allocate or lapse, capture notes
            $table->text('remarks')
                ->nullable()
                ->after('amount');

            // tells you which table the reference_id refers to
            // e.g. 'emp_leave_request', 'carry_forward_job', etc.
            $table->string('reference_type', 100)
                ->nullable()
                ->after('reference_id');
        });
    }

    public function down()
    {
        Schema::table('emp_leave_transactions', function (Blueprint $table) {
            $table->dropColumn(['remarks','reference_type']);
        });
    }
};
