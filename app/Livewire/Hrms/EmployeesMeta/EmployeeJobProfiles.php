<?php

namespace App\Livewire\Hrms\EmployeesMeta;

use App\Models\Settings\Joblocation;
use Livewire\Component;
use App\Models\Hrms\EmployeeJobProfile;
use App\Models\Hrms\Employee;
use App\Models\Settings\Department;
use App\Models\Settings\Designation;
use App\Models\Settings\EmploymentType;
use Flux;

class EmployeeJobProfiles extends Component
{
    use \Livewire\WithPagination;
    public Employee $employee;
    public $profileData = [
        'id' => null,
        'employee_id' => '',
        'employee_code' => '',
        'doh' => '',
        'department_id' => '',
        'designation_id' => '',
        'reporting_manager' => '',
        'employment_type' => '',
        'joblocation_id' => '',
        'doe' => '', 
    ];

    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $isEditing = false;

    public function mount($employeeId)
    {
        session()->put('firm_id', session('firm_id'));
        $this->employee = Employee::findOrFail($employeeId);
        $this->jobProfilesList();
    }

    public function sort($column)
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
    }

    #[\Livewire\Attributes\Computed]
    public function jobProfilesList()
    {
        return EmployeeJobProfile::query()
            ->with(['department', 'designation', 'employment_type', 'employee', 'manager', 'joblocation'])
            ->where('employee_id', $this->employee->id)
            ->where('firm_id', session('firm_id'))
            ->get();
    }

    #[\Livewire\Attributes\Computed]
    public function departmentsList()
    {
        return Department::where('firm_id', session('firm_id'))
            ->where('is_inactive', false)
            ->get();
    }

    #[\Livewire\Attributes\Computed]
    public function designationsList()
    {
        return Designation::where('firm_id', session('firm_id'))
            ->where('is_inactive', false)
            ->get();
    }

    #[\Livewire\Attributes\Computed]
    public function employmentTypesList()
    {
        return EmploymentType::where('firm_id', session('firm_id'))
            ->where('is_inactive', false)
            ->get();
    }
    #[\Livewire\Attributes\Computed]
    public function jobLocationsList()
    {
        return Joblocation::where('firm_id', session('firm_id'))
            ->where('is_inactive', false)
            ->get();
    }

    #[\Livewire\Attributes\Computed]
    public function managersList()
    {
        return Employee::where('firm_id', session('firm_id'))
            ->where('is_inactive', false)
            ->where('id', '!=', $this->employee->id)
            ->get()
            ->map(function($employee) {
                return [
                    'id' => $employee->id,
                    'name' => $employee->fname . ' ' . $employee->lname,
                ];
            });
    }

    public function fetchProfile($id)
    {
        $profile = EmployeeJobProfile::findOrFail($id);
        $this->profileData = $profile->toArray();
        $this->isEditing = true;
        $this->modal('mdl-profile')->show();
    }

    public function saveProfile()
    {
        $validatedData = $this->validate([
            'profileData.employee_code' => 'required|string|max:50',
            'profileData.doh' => 'required|date',
            'profileData.department_id' => 'nullable|exists:departments,id',
            'profileData.designation_id' => 'nullable|exists:designations,id',
            'profileData.reporting_manager' => 'nullable|exists:employees,id',
            'profileData.employment_type_id' => 'nullable|exists:employment_types,id',
            'profileData.joblocation_id' => 'nullable|exists:joblocations,id',
            'profileData.doe' => 'nullable|date|after:profileData.doh',
        ]);

        // Convert empty string dates to null
        if (empty($validatedData['profileData']['doe'])) {
            $validatedData['profileData']['doe'] = null;
        }

        // Convert empty string integers to null for nullable fields
        if (empty($validatedData['profileData']['reporting_manager'])) {
            $validatedData['profileData']['reporting_manager'] = null;
        }
        if (empty($validatedData['profileData']['employment_type_id'])) {
            $validatedData['profileData']['employment_type_id'] = null;
        }
        if (empty($validatedData['profileData']['joblocation_id'])) {
            $validatedData['profileData']['joblocation_id'] = null;
        }
        if (empty($validatedData['profileData']['department_id'])) {
            $validatedData['profileData']['department_id'] = null;
        }
        if (empty($validatedData['profileData']['designation_id'])) {
            $validatedData['profileData']['designation_id'] = null;
        }

        $validatedData['profileData']['employee_id'] = $this->employee->id;
        if ($this->isEditing) {
            $profile = EmployeeJobProfile::findOrFail($this->profileData['id']);
            $profile->update($validatedData['profileData']);
            session()->flash('message', 'Job profile updated successfully.');
        } else {
            $validatedData['profileData']['firm_id'] = session('firm_id');
            EmployeeJobProfile::create($validatedData['profileData']);
            session()->flash('message', 'Job profile added successfully.');
        }

        $this->resetForm();
        $this->modal('mdl-profile')->close();
        Flux::toast(
            heading: 'Changes saved.',
            text: 'Job profile details have been updated successfully.',
        );

        // Emit step completion event
        $this->dispatch('stepCompleted', step: 5);
        $this->render();
    }

    public function deleteProfile($profileId)
    {
        $profile = EmployeeJobProfile::findOrFail($profileId);
        $employeeName = $profile->employee->fname . ' ' . $profile->employee->lname;

        // Delete the profile
        $profile->delete();

        // Check if there are any remaining profiles
        $remainingProfiles = EmployeeJobProfile::where('employee_id', $this->employee->id)->count();
        if ($remainingProfiles === 0) {
            // If no profiles remain, emit step uncompletion event
            $this->dispatch('stepUncompleted', step: 5);
        }

        // Show toast notification
        Flux::toast(
            heading: 'Job Profile Deleted',
            text: "Job profile for {$employeeName} has been deleted successfully."
        );
    }

    public function resetForm()
    {
        $this->profileData = [
            'id' => null,
//            'employee_id' => '',
            'employee_code' => '',
            'doh' => '',
            'department_id' => '',
            'designation_id' => '',
            'reporting_manager' => '',
            'employment_type_id' => '',
            'joblocation_id' => '',
            'doe' => '',
        ];
        $this->isEditing = false;
    }

    public function render()
    {
        return view('livewire.hrms.employees-meta.employee-job-profiles');
    }

}