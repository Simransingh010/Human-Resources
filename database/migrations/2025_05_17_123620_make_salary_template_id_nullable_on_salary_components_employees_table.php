<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('salary_components_employees', function (Blueprint $table) {
            // Ensure you have doctrine/dbal installed if you get an error running change()
            $table->unsignedBigInteger('salary_template_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('salary_components_employees', function (Blueprint $table) {
            $table->unsignedBigInteger('salary_template_id')->nullable(false)->change();
        });
    }
};
