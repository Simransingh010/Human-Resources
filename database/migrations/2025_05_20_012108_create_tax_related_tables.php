<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Tax regimes (Old / New / etc.)
        Schema::create('tax_regimes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('firm_id')->index();
            $table->string('code', 20);    // e.g. 'OLD', 'NEW'
            $table->string('name', 100);             // e.g. 'Old Tax Regime'
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(
                ['firm_id','code'],
                'regime_code_uniq'
            );

            $table->foreign('firm_id')
                ->references('id')->on('firms')
                ->onDelete('cascade');
        });

        // 2) Combined tax brackets (slabs / surcharge / cess)
        Schema::create('tax_brackets', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('firm_id')->index();
            $table->unsignedBigInteger('regime_id')->index();
            $table->string('type', 50); // slab,surchage,cess
            $table->decimal('income_from', 15, 2)->default(0);
            $table->decimal('income_to',   15, 2)->nullable();   // NULL = “and above”
            $table->decimal('rate',         5, 2)->default(0);   // percent rate
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('firm_id')
                ->references('id')->on('firms')
                ->onDelete('cascade');
            $table->foreign('regime_id')
                ->references('id')->on('tax_regimes')
                ->onDelete('cascade');

            $table->index(
                ['regime_id','type','income_from','income_to'],
                'tbrkt_lookup'
            );
        });

        // 3) Map each employee to a tax regime
        Schema::create('employee_tax_regimes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('firm_id')->index();
            $table->unsignedBigInteger('employee_id')->index();
            $table->unsignedBigInteger('regime_id')->index();
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(
                ['firm_id','employee_id','regime_id'],
                'etr_uniq'
            );

            $table->foreign('firm_id')
                ->references('id')->on('firms')
                ->onDelete('cascade');
            $table->foreign('employee_id')
                ->references('id')->on('employees')
                ->onDelete('cascade');
            $table->foreign('regime_id')
                ->references('id')->on('tax_regimes')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_tax_regimes');
        Schema::dropIfExists('tax_brackets');
        Schema::dropIfExists('tax_regimes');
    }
};
