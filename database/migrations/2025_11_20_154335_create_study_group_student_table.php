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
        Schema::create('study_group_student', function (Blueprint $table) {
            $table->id();
			$table->unsignedBigInteger('study_group_id');
			$table->unsignedBigInteger('student_id');
			$table->timestamp('joined_at')->nullable();
			$table->timestamp('left_at')->nullable();
            $table->timestamps();

			$table->foreign('study_group_id')
				->references('id')
				->on('study_groups')
				->cascadeOnDelete();

			$table->foreign('student_id')
				->references('id')
				->on('students')
				->cascadeOnDelete();

			$table->unique(['study_group_id', 'student_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('study_group_student');
    }
};
