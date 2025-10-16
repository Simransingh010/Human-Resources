<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('emp_punches', function (Blueprint $table) {
            $table->string('punch_geo_location')->nullable()->after('in_out');
            $table->string('source_ip_address')->nullable()->after('punch_geo_location');
            $table->json('punch_details')->nullable()->after('source_ip_address');
        });
    }

    public function down()
    {
        Schema::table('emp_punches', function (Blueprint $table) {
            $table->dropColumn(['punch_geo_location', 'source_ip_address', 'punch_details']);
        });
    }
};
