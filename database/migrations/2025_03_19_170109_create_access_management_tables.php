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
        // 1. Panels Table: Stores available panels.
        Schema::create('panels', function (Blueprint $table) {
            $table->id();
            $table->string('name');               // e.g., "SaaS Panel", "Admin Panel"
            $table->string('code')->nullable()->unique();     // Optional short code
            $table->text('description')->nullable();
            $table->string('panel_type')->default('WebApp');    // e.g., "MobileApp" or "WebApp"
            $table->boolean('is_inactive')->default(false);     // Quickly deactivate a panel
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['name', 'panel_type']);
        });

        // 2. Panel-User Pivot Table: Maps users to panels they can access.
        Schema::create('panel_user', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');    // Assumes "users" table exists.
            $table->unsignedBigInteger('panel_id');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['user_id', 'panel_id','firm_id']);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('panel_id')->references('id')->on('panels')->onDelete('cascade');
        });

        // 3. Firm-User Pivot Table: Maps users to firms with a default flag.
        Schema::create('firm_user', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');    // Assumes "users" table exists.
            $table->unsignedBigInteger('firm_id');      // References existing "firms" table.
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['user_id', 'firm_id']);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('firm_id')->references('id')->on('firms')->onDelete('cascade');
        });

        // 4. Permission Groups Table: Group of permissions for a specific firm.
        Schema::create('permission_groups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('firm_id');    // Firm-specific.
            $table->string('name');                   // e.g., "Payroll Manager Group"
            $table->text('description')->nullable();
            $table->boolean('is_inactive')->default(false);  // To quickly disable the group.
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('firm_id')->references('id')->on('firms')->onDelete('cascade');
            $table->unique(['firm_id', 'name'], 'permission_group_firm_unique');
        });

        // 5. PermissionGroup-User Pivot Table: Assigns permission groups to users.
        Schema::create('permission_group_user', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('permission_group_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['permission_group_id', 'user_id'], 'perm_group_user_unique');
            $table->foreign('permission_group_id')->references('id')->on('permission_groups')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        // 6. Apps Table: Lists available applications.
        Schema::create('apps', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();               // e.g., "Payroll", "HR"
            $table->string('code')->nullable()->unique();     // Optional short code
            $table->text('description')->nullable();
            $table->string('icon')->nullable();               // Icon class or URL
            $table->string('route')->nullable();              // Route name or URL
            $table->string('color')->nullable();              // Color code or CSS class
            $table->string('tooltip')->nullable();            // Tooltip text
            $table->integer('order')->default(0);             // Display order
            $table->string('badge')->nullable();              // Badge text (e.g. "New", "Beta")
            $table->json('custom_css')->nullable();           // JSON for custom CSS properties
            $table->boolean('is_inactive')->default(false);   // Quickly disable an app
            $table->timestamps();
            $table->softDeletes();
        });

        // 7. Module Groups Table: Groups for modules under an app.
        Schema::create('module_groups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('app_id');            // Each group belongs to an app.
            $table->string('name');                          // e.g., "Employee Management"
            $table->text('description')->nullable();
            $table->boolean('is_inactive')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('app_id')->references('id')->on('apps')->onDelete('cascade');
            $table->unique(['app_id', 'name'], 'module_group_app_unique');
        });

        // 8. App Modules Table: Modules within an app, optionally grouped.
        Schema::create('app_modules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('app_id');
            $table->unsignedBigInteger('module_group_id')->nullable(); // Optional grouping.
            $table->string('name');                           // e.g., "Employee List", "Leave Management"
            $table->string('code')->nullable();               // Optional short code
            $table->text('description')->nullable();
            $table->string('icon')->nullable();               // Icon for the module
            $table->string('route')->nullable();              // Route or URL to the module
            $table->string('color')->nullable();              // Color code or class
            $table->string('tooltip')->nullable();            // Tooltip text
            $table->integer('order')->default(0);             // Display order for modules
            $table->string('badge')->nullable();              // Badge text
            $table->json('custom_css')->nullable();           // JSON for custom CSS properties
            $table->boolean('is_inactive')->default(false);   // Quickly disable a module
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('app_id')->references('id')->on('apps')->onDelete('cascade');
            $table->foreign('module_group_id')->references('id')->on('module_groups')->onDelete('set null');
            $table->unique(['app_id', 'name'], 'module_app_unique');
        });

        // 9. Firm App Access Table: Defines which firm has access to which app (and optionally module).
        Schema::create('firm_app_access', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('firm_id');
            $table->unsignedBigInteger('app_id');
            $table->unsignedBigInteger('app_module_id')->nullable();  // Optional module access.
            $table->boolean('is_inactive')->default(false);         // Quickly disable access.
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('firm_id')->references('id')->on('firms')->onDelete('cascade');
            $table->foreign('app_id')->references('id')->on('apps')->onDelete('cascade');
            $table->foreign('app_module_id')->references('id')->on('app_modules')->onDelete('cascade');
            $table->unique(['firm_id', 'app_id', 'app_module_id'], 'firm_app_access_unique');
        });

        // 10. Permissions Table: Predefined permissions linked to a specific app module.
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('app_module_id');  // Permission is defined for a module.
            $table->string('name');                        // e.g., "view reports", "edit employee"
            $table->string('code')->nullable();            // Optional unique code
            $table->text('description')->nullable();
            $table->string('icon')->nullable();            // Icon for the permission
            $table->string('route')->nullable();           // Route or URL for the permission action
            $table->string('color')->nullable();           // Color for UI styling
            $table->string('tooltip')->nullable();         // Tooltip text
            $table->integer('order')->default(0);          // Order for display purposes
            $table->string('badge')->nullable();           // Badge text
            $table->json('custom_css')->nullable();        // JSON for custom CSS properties
            $table->boolean('is_inactive')->default(false); // Quickly disable a permission
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('app_module_id')->references('id')->on('app_modules')->onDelete('cascade');
            $table->unique(['app_module_id', 'name'], 'perm_module_name_unique');
        });

        // 11. PermissionGroup-Permission Pivot Table: Assigns permissions to permission groups.
        Schema::create('permission_group_permission', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('permission_group_id');
            $table->unsignedBigInteger('permission_id');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('permission_group_id')->references('id')->on('permission_groups')->onDelete('cascade');
            $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
            $table->unique(['permission_group_id', 'permission_id'], 'perm_group_permission_unique');
        });

        // 12. User Permission Table: Direct permissions assigned to users.
        //      firm_id is nullable; if null, the permission is global for that user.
        Schema::create('user_permission', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');         // Assumes "users" table exists.
            $table->unsignedBigInteger('permission_id');     // References "permissions" table.
            $table->unsignedBigInteger('firm_id')->nullable(); // Nullable for global permissions.
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
            $table->foreign('firm_id')->references('id')->on('firms')->onDelete('cascade');
            $table->unique(['user_id', 'firm_id', 'permission_id'], 'user_permission_unique');
        });

        // 13. Panel-App Pivot Table: Maps panels to apps (many-to-many).
        Schema::create('panel_app', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('panel_id');  // References panels.
            $table->unsignedBigInteger('app_id');    // References apps.
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('panel_id')->references('id')->on('panels')->onDelete('cascade');
            $table->foreign('app_id')->references('id')->on('apps')->onDelete('cascade');
            $table->unique(['panel_id', 'app_id'], 'panel_app_unique');
        });

        // 14. Panel-AppModule Pivot Table: Maps panels to app modules (many-to-many).
        Schema::create('panel_app_module', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('panel_id');        // References panels.
            $table->unsignedBigInteger('app_module_id');     // References app modules.
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('panel_id')->references('id')->on('panels')->onDelete('cascade');
            $table->foreign('app_module_id')->references('id')->on('app_modules')->onDelete('cascade');
            $table->unique(['panel_id', 'app_module_id'], 'panel_app_module_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        // Drop tables in reverse order to avoid foreign key constraint issues.
        Schema::dropIfExists('panel_app_module');
        Schema::dropIfExists('panel_app');
        Schema::dropIfExists('user_permission');
        Schema::dropIfExists('permission_group_permission');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('firm_app_access');
        Schema::dropIfExists('app_modules');
        Schema::dropIfExists('module_groups');
        Schema::dropIfExists('apps');
        Schema::dropIfExists('permission_group_user');
        Schema::dropIfExists('permission_groups');
        Schema::dropIfExists('firm_user');
        Schema::dropIfExists('panel_user');
        Schema::dropIfExists('panels');
    }
};
