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
		Schema::create('study_groups', function (Blueprint $table) {
			$table->id();
			$table->unsignedBigInteger('firm_id');
			$table->unsignedBigInteger('study_centre_id');
			$table->string('name');
			$table->unsignedBigInteger('coach_id')->nullable();
			$table->boolean('is_active')->default(true);
			$table->timestamps();

			$table->foreign('firm_id')
				->references('id')
				->on('firms')
				->onDelete('cascade');

			$table->foreign('study_centre_id')
				->references('id')
				->on('study_centres')
				->onDelete('cascade');

			$table->foreign('coach_id')
				->references('id')
				->on('employees')
				->nullOnDelete();
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::dropIfExists('study_groups');
	}
};


