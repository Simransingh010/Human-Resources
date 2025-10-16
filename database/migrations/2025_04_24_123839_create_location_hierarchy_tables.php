<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('firm_id')->nullable();
            $table->string('name');
            $table->string('code')->nullable();
            $table->boolean('is_inactive')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('firm_id')->references('id')->on('firms')->onDelete('restrict');
        });

        Schema::create('states', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('firm_id')->nullable();
            $table->string('name');
            $table->string('code')->nullable();
            $table->unsignedBigInteger('country_id');
            $table->boolean('is_inactive')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('firm_id')->references('id')->on('firms')->onDelete('restrict');
            $table->foreign('country_id')->references('id')->on('countries')->onDelete('restrict');
        });

        Schema::create('districts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('firm_id')->nullable();
            $table->string('name');
            $table->string('code')->nullable();
            $table->unsignedBigInteger('state_id');
            $table->boolean('is_inactive')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('firm_id')->references('id')->on('firms')->onDelete('restrict');
            $table->foreign('state_id')->references('id')->on('states')->onDelete('restrict');
        });

        Schema::create('subdivisions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('firm_id')->nullable();
            $table->string('name');
            $table->string('code')->nullable();
            $table->string('type')->nullable();
            $table->unsignedBigInteger('district_id');
            $table->boolean('is_inactive')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('firm_id')->references('id')->on('firms')->onDelete('restrict');
            $table->foreign('district_id')->references('id')->on('districts')->onDelete('restrict');
        });

        Schema::create('cities_or_villages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('firm_id')->nullable();
            $table->string('name');
            $table->string('code')->nullable();
            $table->string('type')->nullable();
            $table->unsignedBigInteger('subdivision_id')->nullable();
            $table->unsignedBigInteger('district_id')->nullable();
            $table->boolean('is_inactive')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('firm_id')->references('id')->on('firms')->onDelete('restrict');
            $table->foreign('subdivision_id')->references('id')->on('subdivisions')->onDelete('restrict');
            $table->foreign('district_id')->references('id')->on('districts')->onDelete('restrict');
        });

        Schema::create('postoffices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('firm_id')->nullable();
            $table->string('name');
            $table->string('code')->nullable();
            $table->unsignedBigInteger('city_or_village_id');
            $table->string('pincode')->nullable();
            $table->boolean('is_inactive')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('firm_id')->references('id')->on('firms')->onDelete('restrict');
            $table->foreign('city_or_village_id')->references('id')->on('cities_or_villages')->onDelete('restrict');
        });

        Schema::create('joblocations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('firm_id')->nullable();
            $table->string('name');
            $table->string('code')->nullable();
            $table->string('description')->nullable();
            $table->unsignedBigInteger('parent_joblocation_id')->nullable();
            $table->unsignedBigInteger('country_id')->nullable();
            $table->unsignedBigInteger('state_id')->nullable();
            $table->unsignedBigInteger('district_id')->nullable();
            $table->unsignedBigInteger('subdivision_id')->nullable();
            $table->unsignedBigInteger('city_or_village_id')->nullable();
            $table->unsignedBigInteger('postoffice_id')->nullable();
            $table->boolean('is_inactive')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('firm_id')->references('id')->on('firms')->onDelete('restrict');
            $table->foreign('parent_joblocation_id')->references('id')->on('joblocations')->onDelete('restrict');
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
        Schema::dropIfExists('joblocations');
        Schema::dropIfExists('postoffices');
        Schema::dropIfExists('cities_or_villages');
        Schema::dropIfExists('subdivisions');
        Schema::dropIfExists('districts');
        Schema::dropIfExists('states');
        Schema::dropIfExists('countries');
    }
};
