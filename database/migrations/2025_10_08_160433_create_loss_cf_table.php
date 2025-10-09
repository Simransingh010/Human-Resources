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
        Schema::create('loss_cf', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('firm_id');
            $table->unsignedBigInteger('emp_id')->nullable();
            $table->unsignedBigInteger('financial_year_id');

            $table->decimal('original_loss_amount', 15, 2)->default(0);
            $table->decimal('setoff_in_current_year', 15, 2)->default(0);
            $table->decimal('carry_forward_amount', 15, 2)->default(0);

            $table->integer('forward_upto_year'); // e.g. 2032 (last FY eligible for CF)

            $table->unsignedBigInteger('declaration_id')->nullable();
            $table->unsignedBigInteger('itr_id')->nullable();

            $table->string('remarks', 255)->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['firm_id', 'financial_year_id'], 'idx_firm_year');
            $table->index(['emp_id', 'financial_year_id'], 'idx_emp_year');
            $table->index('itr_id', 'idx_itr');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loss_cf');
    }
};
