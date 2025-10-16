<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // 1) Add to leaves_quota_templates
        Schema::table('leaves_quota_templates', function (Blueprint $table) {
            // adjust types & nullability as appropriate
            $table->string('alloc_period_unit')->nullable()->after('desc');
            $table->integer('alloc_period_value')->nullable()->after('alloc_period_unit');
        });

        // 2) Drop from leaves_quota_template_setups
        Schema::table('leaves_quota_template_setups', function (Blueprint $table) {
            $table->dropColumn(['alloc_period_unit', 'alloc_period_value']);
        });
    }

    public function down()
    {
        // 1) Re-add to leaves_quota_template_setups
        Schema::table('leaves_quota_template_setups', function (Blueprint $table) {
            $table->string('alloc_period_unit')->nullable()->after('days_assigned');
            $table->integer('alloc_period_value')->nullable()->after('alloc_period_unit');
        });

        // 2) Drop from leaves_quota_templates
        Schema::table('leaves_quota_templates', function (Blueprint $table) {
            $table->dropColumn(['alloc_period_unit', 'alloc_period_value']);
        });
    }
};
