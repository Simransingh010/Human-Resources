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
        Schema::create('final_settlements_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('firm_id');
            $table->unsignedBigInteger('exit_id');
            $table->unsignedBigInteger('final_settlement_id');
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('salary_component_id');
            $table->string('nature'); // earning or deduction
            $table->decimal('amount', 12, 2)->default(0);
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('final_settlements_items');
    }
};
