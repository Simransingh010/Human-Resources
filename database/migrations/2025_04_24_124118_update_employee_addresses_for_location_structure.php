<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_addresses', function (Blueprint $table) {
            // Drop old string-based location fields
            $table->dropColumn([
                'country',
                'state',
                'city',
                'town',
                'postoffice',
                'village',
                'pincode',
            ]);

            // Add structured foreign key location fields
            $table->unsignedBigInteger('country_id')->nullable()->after('employee_id');
            $table->unsignedBigInteger('state_id')->nullable()->after('country_id');
            $table->unsignedBigInteger('district_id')->nullable()->after('state_id');
            $table->unsignedBigInteger('subdivision_id')->nullable()->after('district_id');
            $table->unsignedBigInteger('city_or_village_id')->nullable()->after('subdivision_id');
            $table->unsignedBigInteger('postoffice_id')->nullable()->after('city_or_village_id');

            // Foreign key constraints
            $table->foreign('country_id')->references('id')->on('countries')->onDelete('restrict');
            $table->foreign('state_id')->references('id')->on('states')->onDelete('restrict');
            $table->foreign('district_id')->references('id')->on('districts')->onDelete('restrict');
            $table->foreign('subdivision_id')->references('id')->on('subdivisions')->onDelete('restrict');
            $table->foreign('city_or_village_id')->references('id')->on('cities_or_villages')->onDelete('restrict');
            $table->foreign('postoffice_id')->references('id')->on('postoffices')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('employee_addresses', function (Blueprint $table) {
            $table->dropForeign(['country_id']);
            $table->dropForeign(['state_id']);
            $table->dropForeign(['district_id']);
            $table->dropForeign(['subdivision_id']);
            $table->dropForeign(['city_or_village_id']);
            $table->dropForeign(['postoffice_id']);

            $table->dropColumn([
                'country_id',
                'state_id',
                'district_id',
                'subdivision_id',
                'city_or_village_id',
                'postoffice_id',
            ]);

            // Restore old columns
            $table->string('country')->nullable();
            $table->string('state')->nullable();
            $table->string('city')->nullable();
            $table->string('town')->nullable();
            $table->string('postoffice')->nullable();
            $table->string('village')->nullable();
            $table->string('pincode')->nullable();
        });
    }
};
