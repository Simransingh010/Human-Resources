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
        // 1. moduleclusters
        Schema::create('moduleclusters', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->string('color')->nullable();
            $table->string('tooltip')->nullable();
            $table->integer('order')->nullable();
            $table->string('badge')->nullable();
            $table->string('custom_css')->nullable();
            $table->foreignId('parent_modulecluster_id')->nullable()->constrained('moduleclusters');
            $table->boolean('is_inactive')->default(false);
            $table->softDeletes();
            $table->timestamps();

        });

        // 2. componentclusters
        Schema::create('componentclusters', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->string('color')->nullable();
            $table->string('tooltip')->nullable();
            $table->integer('order')->nullable();
            $table->string('badge')->nullable();
            $table->string('custom_css')->nullable();
            $table->foreignId('parent_componentcluster_id')->nullable()->constrained('componentclusters');
            $table->boolean('is_inactive')->default(false);
            $table->softDeletes();
            $table->timestamps();

        });

        // 3. actionclusters
        Schema::create('actionclusters', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->string('color')->nullable();
            $table->string('tooltip')->nullable();
            $table->integer('order')->nullable();
            $table->string('badge')->nullable();
            $table->string('custom_css')->nullable();
            $table->foreignId('parent_actioncluster_id')->nullable()->constrained('actionclusters');
            $table->boolean('is_inactive')->default(false);
            $table->softDeletes();
            $table->timestamps();

        });

        // 4. modules
        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->string('route')->nullable();
            $table->string('color')->nullable();
            $table->string('tooltip')->nullable();
            $table->integer('order')->nullable();
            $table->string('badge')->nullable();
            $table->string('custom_css')->nullable();
            $table->boolean('is_inactive')->default(false);
            $table->softDeletes();
            $table->timestamps();

        });

        // 5. components
        Schema::create('components', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->string('route')->nullable();
            $table->string('color')->nullable();
            $table->string('tooltip')->nullable();
            $table->integer('order')->nullable();
            $table->string('badge')->nullable();
            $table->string('custom_css')->nullable();
            $table->boolean('is_inactive')->default(false);
            $table->softDeletes();
            $table->timestamps();

        });

        // 6. actions
        Schema::create('actions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->string('color')->nullable();
            $table->string('tooltip')->nullable();
            $table->integer('order')->nullable();
            $table->string('badge')->nullable();
            $table->string('custom_css')->nullable();
            $table->foreignId('component_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actioncluster_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_inactive')->default(false);
            $table->softDeletes();
            $table->timestamps();

        });

        // 7. app_module (pivot)
        Schema::create('app_module', function (Blueprint $table) {
            $table->id();
            $table->foreignId('app_id')->constrained()->cascadeOnDelete();
            $table->foreignId('module_id')->constrained()->cascadeOnDelete();
        });

        // 8. component_module (pivot)
        Schema::create('component_module', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained()->cascadeOnDelete();
            $table->foreignId('component_id')->constrained()->cascadeOnDelete();
        });

        // 9. component_panel (pivot)
        Schema::create('component_panel', function (Blueprint $table) {
            $table->id();
            $table->foreignId('panel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('component_id')->constrained()->cascadeOnDelete();
        });

        // 10. modulecluster_module (pivot)
        Schema::create('modulecluster_module', function (Blueprint $table) {
            $table->id();
            $table->foreignId('modulecluster_id')->constrained()->cascadeOnDelete();
            $table->foreignId('module_id')->constrained()->cascadeOnDelete();
        });

        // 11. component_componentcluster (pivot)
        Schema::create('component_componentcluster', function (Blueprint $table) {
            $table->id();
            $table->foreignId('componentcluster_id')->constrained()->cascadeOnDelete();
            $table->foreignId('component_id')->constrained()->cascadeOnDelete();
        });

        // 12. roles
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firm_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_inactive')->default(false);
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['firm_id', 'name']);
        });

        // 13. role_user (pivot)
        Schema::create('role_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firm_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
        });

        // 14. action_role (pivot)
        Schema::create('action_role', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firm_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('action_id')->constrained()->cascadeOnDelete();
            $table->string('records_scope')->nullable();
        });

        // 15. action_user (pivot)
        Schema::create('action_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firm_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('action_id')->constrained()->cascadeOnDelete();
            $table->string('records_scope')->nullable();
        });

        // 16. firm_panel (pivot)
        Schema::create('firm_panel', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firm_id')->constrained()->cascadeOnDelete();
            $table->foreignId('panel_id')->constrained()->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('firm_panel');
        Schema::dropIfExists('action_user');
        Schema::dropIfExists('action_role');
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('component_componentcluster');
        Schema::dropIfExists('modulecluster_module');
        Schema::dropIfExists('component_panel');
        Schema::dropIfExists('component_module');
        Schema::dropIfExists('app_module');
        Schema::dropIfExists('actions');
        Schema::dropIfExists('components');
        Schema::dropIfExists('modules');
        Schema::dropIfExists('actionclusters');
        Schema::dropIfExists('componentclusters');
        Schema::dropIfExists('moduleclusters');
    }
};
