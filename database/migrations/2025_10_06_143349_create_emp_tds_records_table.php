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
        Schema::create('emp_tds_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('firm_id');
            $table->unsignedBigInteger('emp_id');
            $table->unsignedBigInteger('payroll_slot_id');
            $table->decimal('payable_tds', 12, 2)->default(0);
            $table->decimal('paid_tds', 12, 2)->default(0);
            $table->decimal('balance', 12, 2)->default(0);
            $table->string('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('emp_tds_records');
    }
};
