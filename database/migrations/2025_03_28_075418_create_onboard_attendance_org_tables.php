<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // settings - onboard - document_types
        Schema::create('document_types', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('firm_id');
            $table->string('title');
            $table->string('code');
            $table->text('description')->nullable();
            $table->boolean('is_inactive')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('firm_id')->references('id')->on('firms')->onDelete('cascade');

            $table->unique(['firm_id', 'code'], 'doc_type_code_unique');
        });

        // settings - onboard - departments
        Schema::create('departments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('firm_id');
            $table->string('title');
            $table->string('code');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('parent_department_id')->nullable();
            $table->boolean('is_inactive')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('firm_id')->references('id')->on('firms')->onDelete('cascade');
            $table->foreign('parent_department_id')->references('id')->on('departments')->onDelete('set null');
            $table->unique(['firm_id', 'code'], 'dept_code_unique');
        });

        // settings - onboard - designations
        Schema::create('designations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('firm_id');
            $table->string('title');
            $table->string('code')->unique('desg_code_unique'); // Shortened unique index name
            $table->text('description')->nullable();
            $table->boolean('is_inactive')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('firm_id')->references('id')->on('firms')->onDelete('cascade');
        });

        // settings - onboard - departments_designations
        Schema::create('departments_designations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('firm_id');
            $table->unsignedBigInteger('designation_id');
            $table->unsignedBigInteger('department_id');
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['firm_id', 'designation_id', 'department_id'], 'dept_desg_unique'); // Shortened unique index name
            $table->foreign('firm_id')->references('id')->on('firms')->onDelete('cascade');
            $table->foreign('designation_id')->references('id')->on('designations')->onDelete('cascade');
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('cascade');
        });

        // settings - onboard - employment_types
        Schema::create('employment_types', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('firm_id');
            $table->string('title');
            $table->string('code');
            $table->text('description')->nullable();
            $table->boolean('is_inactive')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('firm_id')->references('id')->on('firms')->onDelete('cascade');
            $table->unique(['firm_id', 'code'], 'emp_type_code_unique');
        });

        // settings - organization - firm_brandings
        Schema::create('firm_brandings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('firm_id');
            $table->string('brand_name');
            $table->string('brand_slogan')->nullable();
            $table->string('website')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('facebook')->nullable();
            $table->string('linkedin')->nullable();
            $table->string('instagram')->nullable();
            $table->string('youtube')->nullable();
            $table->json('color_scheme')->nullable();
            $table->string('logo')->nullable();
            $table->string('logo_dark')->nullable();
            $table->string('favicon')->nullable();
            $table->string('legal_entity_type')->nullable();
            $table->string('legal_reg_certificate')->nullable();
            $table->string('legal_certificate_number')->nullable();
            $table->string('tax_reg_certificate')->nullable();
            $table->string('tax_certificate_no')->nullable();
            $table->boolean('is_inactive')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('firm_id')->references('id')->on('firms')->onDelete('cascade');
        });

        // settings - organization - industry_types
        Schema::create('industry_types', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('firm_id');
            $table->string('title');
            $table->string('code')->unique('ind_type_code_unique'); // Shortened unique index name
            $table->text('description')->nullable();
            $table->boolean('is_inactive')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('firm_id')->references('id')->on('firms')->onDelete('cascade');
        });

        // settings - setup - organization_metas
        Schema::create('organization_metas', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('firm_id');
            $table->string('model_name');
            $table->unsignedBigInteger('model_id');
            $table->string('meta_key');
            $table->json('meta_value')->nullable();
            $table->string('meta_type');
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['firm_id', 'model_name', 'model_id', 'meta_key'], 'org_meta_unique'); // Shortened unique index name
            $table->foreign('firm_id')->references('id')->on('firms')->onDelete('cascade');
        });

        // settings - setup - onboard_metas
        Schema::create('onboard_metas', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('firm_id');
            $table->string('model_name');
            $table->unsignedBigInteger('model_id');
            $table->string('meta_key');
            $table->json('meta_value')->nullable();
            $table->string('meta_type');
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['firm_id', 'model_name', 'model_id', 'meta_key'], 'onboard_meta_unique'); // Shortened unique index name
            $table->foreign('firm_id')->references('id')->on('firms')->onDelete('cascade');
        });

        // settings - setup - attendance_metas
        Schema::create('attendance_metas', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('firm_id');
            $table->string('model_name');
            $table->unsignedBigInteger('model_id');
            $table->string('meta_key');
            $table->json('meta_value')->nullable();
            $table->string('meta_type');
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['firm_id', 'model_name', 'model_id', 'meta_key'], 'att_meta_unique'); // Shortened unique index name
            $table->foreign('firm_id')->references('id')->on('firms')->onDelete('cascade');
        });

        // settings - setup - settings_metas
        Schema::create('settings_metas', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('firm_id');
            $table->string('model_name');
            $table->unsignedBigInteger('model_id');
            $table->string('meta_key');
            $table->json('meta_value')->nullable();
            $table->string('meta_type');
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['firm_id', 'model_name', 'model_id', 'meta_key'], 'set_meta_unique'); // Shortened unique index name
            $table->foreign('firm_id')->references('id')->on('firms')->onDelete('cascade');
        });

        // settings - setup - groupings
        Schema::create('groupings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('firm_id');
            $table->string('model_name');
            $table->string('group_name');
            $table->unsignedBigInteger('parent_group_id')->nullable();
            $table->boolean('is_inactive')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('firm_id')->references('id')->on('firms')->onDelete('cascade');
            $table->foreign('parent_group_id')->references('id')->on('groupings')->onDelete('set null');
        });

        // settings - setup - models_groupings
        Schema::create('models_groupings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('firm_id');
            $table->string('model_name');
            $table->unsignedBigInteger('model_id');
            $table->unsignedBigInteger('grouping_id');
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['firm_id', 'model_name', 'model_id', 'grouping_id'], 'model_group_unique'); // Shortened unique index name
            $table->foreign('firm_id')->references('id')->on('firms')->onDelete('cascade');
            $table->foreign('grouping_id')->references('id')->on('groupings')->onDelete('cascade');
        });

        // hrms - onboard - employee_personal_details
        Schema::create('employee_personal_details', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('firm_id');
            $table->unsignedBigInteger('employee_id');
            $table->date('dob')->nullable();
            $table->string('marital_status')->nullable();
            $table->date('doa')->nullable();
            $table->string('nationality')->nullable();
            $table->string('fathername')->nullable();
            $table->string('mothername')->nullable();
            $table->string('adharno')->nullable();
            $table->string('panno')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('firm_id')->references('id')->on('firms')->onDelete('cascade');
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');


        });

        // hrms - onboard - employee_job_profiles
        Schema::create('employee_job_profiles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('firm_id');
            $table->unsignedBigInteger('employee_id');
            $table->string('employee_code');
            $table->date('doh')->nullable();
            $table->unsignedBigInteger('department_id');
            $table->unsignedBigInteger('designation_id');
            $table->unsignedBigInteger('reporting_manager')->nullable();
            $table->unsignedBigInteger('employment_type_id')->nullable();
            $table->date('doe')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('firm_id')->references('id')->on('firms')->onDelete('cascade');
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('cascade');
            $table->foreign('designation_id')->references('id')->on('designations')->onDelete('cascade');
            $table->foreign('reporting_manager')->references('id')->on('employees')->onDelete('set null');
            $table->foreign('employment_type_id')->references('id')->on('employment_types')->onDelete('set null');
            $table->unique(['firm_id', 'employee_code'], 'emp_code_unique');
        });

        // hrms - onboard - employee_relations
        Schema::create('employee_relations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('firm_id');
            $table->unsignedBigInteger('employee_id');
            $table->string('relation');
            $table->string('person_name');
            $table->string('occupation')->nullable();
            $table->date('dob')->nullable();
            $table->string('qualification')->nullable();
            $table->boolean('is_inactive')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('firm_id')->references('id')->on('firms')->onDelete('cascade');
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
        });

        // hrms - onboard - employee_contacts
        Schema::create('employee_contacts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('firm_id');
            $table->unsignedBigInteger('employee_id');
            $table->string('contact_type');
            $table->string('contact_value');
            $table->string('contact_person')->nullable();
            $table->string('relation')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_for_emergency')->default(false);
            $table->boolean('is_inactive')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('firm_id')->references('id')->on('firms')->onDelete('cascade');
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
        });

        // hrms - onboard - employee_addresses
        Schema::create('employee_addresses', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('firm_id');
            $table->unsignedBigInteger('employee_id');
            $table->string('country');
            $table->string('state');
            $table->string('city');
            $table->string('town')->nullable();
            $table->string('postoffice')->nullable();
            $table->string('village')->nullable();
            $table->string('pincode');
            $table->text('address');
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_permanent')->default(false);
            $table->boolean('is_inactive')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('firm_id')->references('id')->on('firms')->onDelete('cascade');
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
        });

        // hrms - onboard - employee_bank_accounts
        Schema::create('employee_bank_accounts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('firm_id');
            $table->unsignedBigInteger('employee_id');
            $table->string('bank_name');
            $table->string('branch_name');
            $table->text('address')->nullable();
            $table->string('ifsc');
            $table->string('bankaccount')->unique('bank_acc_unique'); // Shortened unique index name
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_inactive')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('firm_id')->references('id')->on('firms')->onDelete('cascade');
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');

            $table->unique(['firm_id', 'bankaccount'], 'bank_acc_unique');
        });

        // hrms - onboard - employee_docs
        Schema::create('employee_docs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('firm_id');
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('document_type_id');
            $table->string('document_number')->unique('doc_num_unique'); // Shortened unique index name
            $table->date('issued_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('doc_url')->nullable();
            $table->boolean('is_inactive')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('firm_id')->references('id')->on('firms')->onDelete('cascade');
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('document_type_id')->references('id')->on('document_types')->onDelete('cascade');

            $table->unique(['firm_id', 'document_number'], 'doc_num_unique');

        });


        // hrms - attendance - attendance_violation_logs
        Schema::create('attendance_violation_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('firm_id');
            $table->unsignedBigInteger('employee_id');
            $table->date('work_date');
            $table->timestamp('punch_datetime');
            $table->string('violation_type');
            $table->string('allowed_value')->nullable();
            $table->string('actual_value')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('firm_id')->references('id')->on('firms')->onDelete('cascade');
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('attendance_violation_logs');
        Schema::dropIfExists('employee_docs');
        Schema::dropIfExists('employee_bank_accounts');
        Schema::dropIfExists('employee_addresses');
        Schema::dropIfExists('employee_contacts');
        Schema::dropIfExists('employee_relations');
        Schema::dropIfExists('employee_job_profiles');
        Schema::dropIfExists('employee_personal_details');
        Schema::dropIfExists('models_groupings');
        Schema::dropIfExists('groupings');
        Schema::dropIfExists('settings_metas');
        Schema::dropIfExists('attendance_metas');
        Schema::dropIfExists('onboard_metas');
        Schema::dropIfExists('organization_metas');
        Schema::dropIfExists('industry_types');
        Schema::dropIfExists('firm_brandings');
        Schema::dropIfExists('employment_types');
        Schema::dropIfExists('departments_designations');
        Schema::dropIfExists('designations');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('document_types');
    }
};
