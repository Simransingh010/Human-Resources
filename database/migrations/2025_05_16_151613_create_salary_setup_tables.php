<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        //
        // 1) salary_template_groups
        //
        Schema::create('salary_template_groups', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('firm_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('parent_salary_template_group_id')->nullable();
            $table->string('cycle_unit');
            $table->string('cycle_value');
            $table->string('cycle_start_unit')->nullable();
            $table->string('cycle_start_value')->nullable();
            $table->boolean('is_inactive')->default(false);

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('firm_id')
                ->references('id')->on('firms')
                ->onDelete('cascade');
            $table->foreign('parent_salary_template_group_id')
                ->references('id')->on('salary_template_groups')
                ->onDelete('set null');
        });

        //
        // 2) salary_templates
        //
        Schema::create('salary_templates', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('firm_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('salary_template_group_id');
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->boolean('is_inactive')->default(false);

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('firm_id')
                ->references('id')->on('firms')
                ->onDelete('cascade');
            $table->foreign('salary_template_group_id')
                ->references('id')->on('salary_template_groups')
                ->onDelete('cascade');
        });

        //
        // 3) salary_component_groups
        //
        Schema::create('salary_component_groups', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('firm_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('parent_salary_component_group_id')->nullable();
            $table->boolean('is_inactive')->default(false);

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('firm_id')
                ->references('id')->on('firms')
                ->onDelete('cascade');
            $table->foreign('parent_salary_component_group_id')
                ->references('id')->on('salary_component_groups')
                ->onDelete('set null');
        });

        //
        // 4) salary_components
        //
        Schema::create('salary_components', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('firm_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('salary_component_group_id')->nullable();
            $table->string('nature');            // e.g. earning|deduction|no_impact
            $table->string('component_type');    // e.g. fixed|variable|reimbursement|…
            $table->string('amount_type')->nullable();
            $table->boolean('taxable')->default(false);
            $table->json('calculation_json')->nullable();
            $table->boolean('document_required')->default(false);

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('firm_id')
                ->references('id')->on('firms')
                ->onDelete('cascade');
            $table->foreign('salary_component_group_id')
                ->references('id')->on('salary_component_groups')
                ->onDelete('set null');
        });

        //
        // 5) salary_templates_components
        //
        Schema::create('salary_templates_components', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('firm_id');
            $table->unsignedBigInteger('salary_template_id');
            $table->unsignedBigInteger('salary_component_id');
            $table->unsignedBigInteger('salary_component_group_id')->nullable();
            $table->integer('sequence')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('firm_id')
                ->references('id')->on('firms')
                ->onDelete('cascade');
            $table->foreign('salary_template_id')
                ->references('id')->on('salary_templates')
                ->onDelete('cascade');
            $table->foreign('salary_component_id')
                ->references('id')->on('salary_components')
                ->onDelete('cascade');
            $table->foreign('salary_component_group_id')
                ->references('id')->on('salary_component_groups')
                ->onDelete('set null');
        });

        //
        // 6) salary_components_employees
        //
        Schema::create('salary_components_employees', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('firm_id');
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('salary_template_id');
            $table->unsignedBigInteger('salary_component_group_id')->nullable();
            $table->unsignedBigInteger('salary_component_id');
            $table->integer('sequence')->default(0);
            $table->string('nature');
            $table->string('component_type');
            $table->string('amount_type')->nullable();
            $table->decimal('amount', 15, 2)->default(0);
            $table->boolean('taxable')->default(false);
            $table->json('calculation_json')->nullable();
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('firm_id')
                ->references('id')->on('firms')
                ->onDelete('cascade');
            $table->foreign('employee_id')
                ->references('id')->on('employees')
                ->onDelete('cascade');
            $table->foreign('salary_template_id')
                ->references('id')->on('salary_templates')
                ->onDelete('cascade');
            $table->foreign('salary_component_group_id')
                ->references('id')->on('salary_component_groups')
                ->onDelete('set null');
            $table->foreign('salary_component_id')
                ->references('id')->on('salary_components')
                ->onDelete('cascade');
            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('salary_components_employees');
        Schema::dropIfExists('salary_templates_components');
        Schema::dropIfExists('salary_components');
        Schema::dropIfExists('salary_component_groups');
        Schema::dropIfExists('salary_templates');
        Schema::dropIfExists('salary_template_groups');
    }
};
