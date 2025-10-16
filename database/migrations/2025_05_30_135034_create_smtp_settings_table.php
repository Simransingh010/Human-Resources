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
        Schema::create('smtp_settings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('firm_id')->constrained()->cascadeOnDelete();
            $table->string('host');
            $table->integer('port');
            $table->string('encryption')->default('tls');
            $table->string('username');
            $table->text('password');          // encrypt before saving
            $table->string('from_address');
            $table->string('from_name');
            $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('smtp_settings');
    }
};
