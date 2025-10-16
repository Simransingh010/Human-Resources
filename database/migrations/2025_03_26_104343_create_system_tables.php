<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Order of creation:
     * 1. versions
     * 2. system_settings
     * 3. device_tokens
     * 4. maintenance_modes
     * 5. firm_versions
     * 6. system_usages
     *
     * @return void
     */
    public function up()
    {
        // 1. Versions Table: Stores all launched system versions.
        Schema::create('versions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable();
            $table->text('description')->nullable();
            $table->string('major_version')->nullable();
            $table->string('minor_version')->nullable();
            $table->boolean('is_inactive')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        // 2. System Settings Table: Global key/value settings per firm.
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('firm_id')->nullable(); // Multi-tenancy support; null means global
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // Validation handled at model level
            $table->boolean('is_editable')->default(true);
            $table->boolean('is_inactive')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['firm_id', 'key'], 'firm_key_unique');

            $table->foreign('firm_id')->references('id')->on('firms')->onDelete('cascade');
        });

        // 3. Device Tokens Table: For push notifications and device tracking.
        Schema::create('device_tokens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('firm_id')->index(); // Multi-tenancy support
            $table->unsignedBigInteger('user_id');
            $table->string('token')->unique();
            $table->string('device_type'); // e.g., "ios", "android", "web" (handled in model)
            $table->string('device_name')->nullable();
            $table->string('os_version')->nullable();
            $table->boolean('is_inactive')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['firm_id', 'user_id','token'], 'firm_user_token');
            $table->foreign('firm_id')->references('id')->on('firms')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        // 4. Maintenance Modes Table: Define maintenance periods per firm & platform.
        Schema::create('maintenance_modes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('firm_id')->nullable(); // Multi-tenancy; null means global maintenance
            $table->string('platform')->index(); // e.g., "web", "mobile" (validation in model)
            $table->boolean('is_maintenance')->default(false);
            $table->text('message')->nullable();
            $table->timestamp('start_time')->nullable();
            $table->timestamp('end_time')->nullable();
            $table->boolean('is_inactive')->default(false);
            $table->timestamps();
            $table->softDeletes();

            // Unique constraint to prevent duplicate maintenance entries for the same firm & platform/time range.
            $table->unique(['firm_id', 'platform', 'start_time', 'end_time'], 'maintenance_unique');
            $table->foreign('firm_id')->references('id')->on('firms')->onDelete('cascade');
        });

        // 5. Firm Versions Table: Define which versions a firm should use.
        Schema::create('firm_versions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('firm_id');
            $table->unsignedBigInteger('version_id');
            // Use a simple string for type (e.g., "mandatory" or "latest"); validated at model level.
            $table->string('type')->default('latest');
            $table->boolean('is_inactive')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('firm_id')->references('id')->on('firms')->onDelete('cascade');
            $table->foreign('version_id')->references('id')->on('versions')->onDelete('cascade');
           
        });

        // 6. System Usages Table: Track which version a user is using and last access.
        Schema::create('system_usages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('firm_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('version_id');
            $table->timestamp('last_accessed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['firm_id', 'version_id', 'user_id'], 'firm_versions_user_unique');

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('version_id')->references('id')->on('versions')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * Drop in reverse order to satisfy foreign key constraints.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('system_usages');
        Schema::dropIfExists('firm_versions');
        Schema::dropIfExists('maintenance_modes');
        Schema::dropIfExists('device_tokens');
        Schema::dropIfExists('system_settings');
        Schema::dropIfExists('versions');
    }
};
