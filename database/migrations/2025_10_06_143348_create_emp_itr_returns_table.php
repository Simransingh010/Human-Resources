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
        Schema::create('emp_itr_returns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('firm_id');
            $table->unsignedBigInteger('emp_id');
            $table->unsignedBigInteger('financial_year_id');
            $table->string('itr_type');
            $table->date('date_filed')->nullable();
            $table->string('acknowledgement_no')->nullable();
            $table->json('filling_json')->nullable();
            $table->string('status')->default('submitted');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('emp_itr_returns');
    }
};
