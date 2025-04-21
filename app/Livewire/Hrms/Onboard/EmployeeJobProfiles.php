<?php

namespace App\Livewire\Hrms\Onboard;

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
    
    public $profileData = [
        'id' => null,
        'employee_id' => '',
        'employee_code' => '',
        'doh' => '', // date of hire
        'department_id' => '',
        'designation_id' => '',
        'reporting_manager' => '',
        'employment_type' => '',
        'doe' => '', // date of exit
    ];

    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $isEditing = false;

    public function mount()
    {
        session()->put('firm_id', 3);
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
    public function profilesList()
    {
        return EmployeeJobProfile::query()
            ->with(['employee', 'department', 'designation', 'employment_type'])
            ->when($this->sortBy, fn($query) => $query->orderBy($this->sortBy, $this->sortDirection))
            ->paginate(5);
    }

    #[\Livewire\Attributes\Computed]
    public function employeesList()
    {
        return Employee::where('firm_id', session('firm_id'))
            ->where('is_inactive', false)
            ->get()
            ->map(function($employee) {
                return [
                    'id' => $employee->id,
                    'name' => $employee->fname . ' ' . $employee->lname . ' (' . $employee->email . ')'
                ];
            });
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
    public function managersList()
    {
        return Employee::where('firm_id', session('firm_id'))
            ->where('is_inactive', false)
            ->get()
            ->map(function($employee) {
                return [
                    'id' => $employee->id,
                    'name' => $employee->fname . ' ' . $employee->lname . ' (Manager)'
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
            'profileData.employee_id' => 'required|exists:employees,id',
            'profileData.employee_code' => 'required|string|max:50',
            'profileData.doh' => 'required|date',
            'profileData.department_id' => 'required|exists:departments,id',
            'profileData.designation_id' => 'required|exists:designations,id',
            'profileData.reporting_manager' => 'nullable|exists:employees,id',
            'profileData.employment_type' => 'required|exists:employment_types,id',
            'profileData.doe' => 'nullable|date|after:profileData.doh',
        ]);

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
    }

    public function deleteProfile($profileId)
    {
        $profile = EmployeeJobProfile::findOrFail($profileId);
        $employeeName = $profile->employee->fname . ' ' . $profile->employee->lname;
        
        // Delete the profile
        $profile->delete();
        
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
            'employee_id' => '',
            'employee_code' => '',
            'doh' => '',
            'department_id' => '',
            'designation_id' => '',
            'reporting_manager' => '',
            'employment_type' => '',
            'doe' => '',
        ];
        $this->isEditing = false;
    }

    public function render()
    {
        return view('livewire.hrms.onboard.employee-job-profiles', [
            'employees' => $this->employeesList,
            'departments' => $this->departmentsList,
            'designations' => $this->designationsList,
            'employment_types' => $this->employmentTypesList,
            'managers' => $this->managersList,
        ]);
    }
} 