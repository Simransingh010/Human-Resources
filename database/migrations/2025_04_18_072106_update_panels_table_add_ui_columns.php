<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('panels', function (Blueprint $table) {
            $table->string('icon')->nullable()->after('description');
            $table->string('color')->nullable()->after('icon');
            $table->string('tooltip')->nullable()->after('color');
            $table->integer('order')->nullable()->after('tooltip');
            $table->string('badge')->nullable()->after('order');
            $table->text('custom_css')->nullable()->after('badge');
        });
    }

    public function down(): void
    {
        Schema::table('panels', function (Blueprint $table) {
            $table->dropColumn(['icon', 'color', 'tooltip', 'order', 'badge', 'custom_css']);
        });
    }
};
