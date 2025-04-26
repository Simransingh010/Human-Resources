<?php

namespace App\Livewire\Hrms\Onboard;

use App\Models\Hrms\Employee;
use App\Models\Hrms\EmployeeJobProfile;
use App\Models\Settings\Department;
use App\Models\Settings\Designation;
use App\Models\Settings\EmploymentType;
use Livewire\Component;
use Livewire\WithPagination;

class BulkEmployeeJobProfiles extends Component
{
    use WithPagination;

    public $perPage = 10;
    public $departments = [];
    public $designations = [];
    public $employmentTypes = [];

    public $bulkupdatetest;

    public $bulkupdate = [];
    public $employees;

    public function mount()
    {
        $this->departments = Department::where('firm_id', session('firm_id'))->pluck('title', 'id')->toArray();
        $this->designations = Designation::where('firm_id', session('firm_id'))->pluck('title', 'id')->toArray();
        $this->employmentTypes = EmploymentType::where('firm_id', session('firm_id'))->pluck('title', 'id')->toArray();



    }
    #[\Livewire\Attributes\Computed]
    public function list()
    {
        $employees = Employee::with('emp_job_profile')
            ->where('firm_id', session('firm_id'))
            ->paginate($this->perPage);

        // Pre-fill the bulkupdate array
        foreach ($employees as $employee) {
            $profile = $employee->emp_job_profile;

            if ($profile) {
                $this->bulkupdate[$employee->id]['department_id'] = $profile->department_id;
                $this->bulkupdate[$employee->id]['designation_id'] = $profile->designation_id;
                $this->bulkupdate[$employee->id]['employment_type'] = $profile->employment_type;
            }
        }
        return $employees;
    }


    public function triggerUpdate($employeeId, $field)
    {
        $value = $this->bulkupdate[$employeeId][$field] ?? null;

        $profile = EmployeeJobProfile::firstOrCreate(
            ['employee_id' => $employeeId],
            ['firm_id' => session('firm_id')]
        );

        $profile->$field = $value;
        $profile->save();
    }
    public function render()
    {
        return view()->file( app_path('Livewire/Hrms/Onboard/blades/bulk-employee-job-profiles.blade.php'));
//        return view()->file(
//            app_path('Livewire/Hrms/Onboard/blades/bulk-employee-job-profiles.blade.php')
//        )->with([
//            'employees' => $this->employees,
//        ]);
    }
}
