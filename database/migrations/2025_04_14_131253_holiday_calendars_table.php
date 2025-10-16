<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('holiday_calendars', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('firm_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->boolean('is_inactive')->default(false);
            $table->timestamps();
            $table->softDeletes(); // Adds deleted_at column

            $table->foreign('firm_id')->references('id')->on('firms')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('holiday_calendars');
    }
};
