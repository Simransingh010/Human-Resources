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
        Schema::create('tax_bracket_breakdowns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firm_id')->constrained('firms')->onDelete('cascade');
            $table->foreignId('tax_bracket_id')->constrained('tax_brackets')->onDelete('cascade');
            $table->decimal('breakdown_amount_from', 10, 2)->nullable();
            $table->decimal('breakdown_amount_to', 10, 2)->nullable();
            $table->decimal('rate', 5, 2)->nullable();
            $table->boolean('is_inactive')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_bracket_breakdowns');
    }
};
