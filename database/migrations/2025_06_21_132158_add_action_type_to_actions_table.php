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
        Schema::table('actions', function (Blueprint $table) {
            $table->string('action_type')->nullable()->after('actioncluster_id');
            $table->string('parent_action_id')->nullable()->after('action_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('actions', function (Blueprint $table) {
            $table->dropColumn('action_type');
            $table->dropColumn('parent_action_id');
            //
        });
    }
};
