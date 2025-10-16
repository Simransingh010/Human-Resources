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
        Schema::table('holidays', function (Blueprint $table) {
            $table->string('day_status_main')
                ->default('2')
                ->after('is_inactive')
                ->comment('1-Full Working,2-Holiday,3-Week Off,4-Partial Working,5-Suspended');
        });
    }

    public function down()
    {
        Schema::table('holidays', function (Blueprint $table) {
            $table->dropColumn('day_status_main');
        });
    }
};
