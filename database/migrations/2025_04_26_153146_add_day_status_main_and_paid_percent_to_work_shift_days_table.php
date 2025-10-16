<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('work_shift_days', function (Blueprint $table) {
            $table->string('day_status_main')->nullable()->after('work_shift_day_status_id');
            $table->decimal('paid_percent', 5, 2)->nullable()->after('day_status_main');
        });
    }

    public function down()
    {
        Schema::table('work_shift_days', function (Blueprint $table) {
            $table->dropColumn(['day_status_main', 'paid_percent']);
        });
    }
};
