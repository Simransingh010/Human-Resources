<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_shift_day_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firm_id')->constrained()->onDelete('cascade');
            $table->foreignId('work_shift_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('day_status_code', 50);
            $table->string('day_status_label', 100);
            $table->text('day_status_desc')->nullable();
            $table->decimal('paid_percent', 5, 2)->default(100.00); // e.g. 100.00, 50.00
            $table->boolean('count_as_working_day')->default(true);
            $table->boolean('is_inactive')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['work_shift_id', 'day_status_code'], 'unique_day_status_per_shift');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_shift_day_statuses');
    }
};
