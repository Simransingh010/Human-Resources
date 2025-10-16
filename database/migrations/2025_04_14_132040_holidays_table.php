<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('firm_id');
            $table->unsignedBigInteger('holiday_calendar_id');
            $table->string('holiday_title');
            $table->text('holiday_desc')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->boolean('repeat_annually')->default(false);
            $table->boolean('is_inactive')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('firm_id')->references('id')->on('firms')->onDelete('cascade');
            $table->foreign('holiday_calendar_id')->references('id')->on('holiday_calendars')->onDelete('cascade');

            // Composite unique index
            $table->unique(['holiday_calendar_id', 'holiday_title', 'start_date', 'end_date'], 'unique_holiday_calendar_title_dates');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('holidays');
    }
};
