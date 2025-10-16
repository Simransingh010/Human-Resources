<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        // 1) agencies
        Schema::create('agencies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });

        // 2) firms
        Schema::create('firms', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('short_name')->nullable();
            $table->string('firm_type')->nullable();

            // Foreign key to agencies
            $table->foreignId('agency_id')
                ->nullable()
                ->constrained('agencies')
                ->onDelete('set null');

            // Self-referencing
            $table->foreignId('parent_firm_id')
                ->nullable()
                ->constrained('firms')
                ->onDelete('cascade');

            $table->boolean('is_master_firm')->default(false);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('firms');
        Schema::dropIfExists('agencies');
    }
};
