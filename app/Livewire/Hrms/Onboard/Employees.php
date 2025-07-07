<?php

namespace App\Livewire\Hrms\Onboard;

use App\Models\Saas\Role;
use App\Models\User;
use Livewire\Component;
use App\Models\Hrms\Employee;
use Flux;
use Illuminate\Validation\Rule;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;

class Employees extends Component
{
    use WithPagination;
    public array $employeeStatuses = [];
    public array $listsForFields = [];


    public $employeeData = [
        'id' => null,
        'fname' => '',
        'mname' => '',
        'lname' => '',
        'email' => '',
        'phone' => '',
        'gender' => '',
    ];

    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $isEditing = false;
    public $selectedEmpId = null;
    public $viewMode = 'card'; // default to card view

    // Field configuration for form and table
    public array $fieldConfig = [
        'fname' => ['label' => 'First Name', 'type' => 'text'],
        'mname' => ['label' => 'Middle Name', 'type' => 'text'],
        'lname' => ['label' => 'Last Name', 'type' => 'text'],
        'email' => ['label' => 'Email', 'type' => 'email'],
        'phone' => ['label' => 'Phone', 'type' => 'text'],
        'gender' => ['label' => 'Gender', 'type' => 'select', 'listKey' => 'gender']
    ];

    // Filter fields configuration
    public array $filterFields = [
        'fname' => ['label' => 'First Name', 'type' => 'text'],
        'lname' => ['label' => 'Last Name', 'type' => 'text'],
        'email' => ['label' => 'Email', 'type' => 'text'],
        'phone' => ['label' => 'Phone', 'type' => 'text'],
        'gender' => ['label' => 'Gender', 'type' => 'select', 'listKey' => 'gender']
    ];

    public array $filters = [];
    public array $visibleFields = [];
    public array $visibleFilterFields = [];

    protected $listeners = [
        'employee-updated' => '$refresh',
        'employee-saved' => '$refresh',
        'close-modal' => 'closeModal'
    ];

    public function mount()
    {
        $this->loadEmployeeStatuses();
        $this->resetPage();
        $this->initListsForFields();
        $this->visibleFields = ['fname', 'lname', 'email', 'phone'];
        $this->visibleFilterFields = ['fname', 'lname', 'email', 'phone'];
        $this->filters = array_fill_keys(array_merge(array_keys($this->filterFields), ['employees']), '');
     
        $this->viewMode = session('employees_view_mode', $this->viewMode);
    }

    private function loadEmployeeStatuses()
    {
        $this->employeeStatuses = Employee::pluck('is_inactive', 'id')
            ->map(function ($status) {
                return !$status;
            })
            ->toArray();
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

    #[Computed]
    public function employeeslist()
    {
        return Employee::query()
            ->with([
                'emp_personal_detail',
                'emp_job_profile.department',
                'emp_job_profile.designation',
            ])
            ->when($this->sortBy, fn($query) => $query->orderBy($this->sortBy, $this->sortDirection))
            ->when($this->filters['employees'], function($query, $value) {
                $query->where(function($q) use ($value) {
                    $q->where('fname', 'like', "%{$value}%")
                      ->orWhere('mname', 'like', "%{$value}%")
                      ->orWhere('lname', 'like', "%{$value}%");
                });
            })
            ->when($this->filters['fname'], fn($query, $value) => $query->where('fname', 'like', "%{$value}%"))
            ->when($this->filters['lname'], fn($query, $value) => $query->where('lname', 'like', "%{$value}%"))
            ->when($this->filters['email'], fn($query, $value) => $query->where('email', 'like', "%{$value}%"))
            ->when($this->filters['phone'], fn($query, $value) => $query->where('phone', 'like', "%{$value}%"))
            ->when($this->filters['gender'], fn($query, $value) => $query->where('gender', $value))
            ->where('firm_id', session('firm_id'))
            ->paginate(12);
    }

    /**
     * Get employee profile image URL (from Spatie Media Library)
     */
    public function getEmployeeImageUrl($employee)
    {
        if ($employee->emp_personal_detail) {
            $media = $employee->emp_personal_detail->getMedia('employee_images')->first();
            if ($media) {
                return $media->getUrl();
            }
        }
        // Return a default avatar if not found
    }

    /**
     * Get department name for employee
     */
    public function getEmployeeDepartment($employee)
    {
        return $employee->emp_job_profile && $employee->emp_job_profile->department
            ? $employee->emp_job_profile->department->title
            : '-';
    }

    /**
     * Get designation name for employee
     */
    public function getEmployeeDesignation($employee)
    {
        return $employee->emp_job_profile && $employee->emp_job_profile->designation
            ? $employee->emp_job_profile->designation->title
            : '-';
    }

    public function fetchEmployee($id)
    {
        $employee = Employee::findOrFail($id);
        $this->employeeData = $employee->toArray();
        $this->isEditing = true;
        $this->modal('mdl-employee')->show();
    }

    // Save or update the employee record based on whether we're editing or adding new.
    public function saveEmployee_old()
    {

        // If the ID is null, it's a new employee
        if ($this->employeeData['id']) {
            // If employee ID exists, find the employee
            $employee = Employee::findOrFail($this->employeeData['id']);
        } else {
            // If no ID, create a new Employee instance
            $employee = new Employee();
        }

//        dd($this->employeeData);

        $validatedData = $this->validate([
            'employeeData.fname' => 'required|string|max:255',
            'employeeData.mname' => 'nullable|string|max:255',
            'employeeData.lname' => 'nullable|string|max:255',
            'employeeData.email' => [
                'required', 'string', 'email', 'max:255',
                Rule::unique('employees', 'email')->ignore($this->employeeData['id']),
            ],
            'employeeData.phone' => [
                'required',
                Rule::unique('employees', 'phone')->ignore($this->employeeData['id']),
            ],
            'employeeData.gender' => 'required|in:1,2,3',
        ]);

        $validatedDataUsr = $this->validate([
            'employeeData.email' => [
                'required', 'string', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($employee->user_id),
            ],
            'employeeData.phone' => [
                'required',
                Rule::unique('users', 'phone')->ignore($employee->user_id),
            ],
        ]);


        if ($this->isEditing) {
            // Update existing employee and user
            $employee->update($validatedData['employeeData']);
            $user = User::findOrFail($employee->user_id);
            $user->update($validatedDataUsr['employeeData']);

            // Assign the employee role (or update it)
            $role = Role::where('name', 'employee')->first();
            if ($role) {
                $user->roles()->sync([$role->id]); // Sync the 'employee' role
            }

            $toast = 'Employee updated successfully.';
        } else {
            // Add new employee and user
            $validatedData['employeeData']['firm_id'] = session('firm_id');
            $employee = Employee::create($validatedData['employeeData']);

            // Create new user
            $user = new User();
            $user->name = $validatedData['employeeData']['fname']." ".$validatedData['employeeData']['lname'];
            $user->password='iqwing@1947';
            $user->passcode='1111';
            $user->email = $validatedDataUsr['employeeData']['email'];
            $user->phone = $validatedDataUsr['employeeData']['phone'];
            $user->role_main = 'L0_emp';
            // Add other user attributes as needed
            $user->save();

            // Assign the employee role to the newly created user
            $role = Role::where('name', 'employee')->first();
            if ($role) {
                $user->roles()->sync([
                    $role->id => ['firm_id' => session('firm_id')] // Include firm_id as pivot data
                ]);
            }

            // Link user to the employee
            $employee->user_id = $user->id;
            $employee->save();

            $toast = 'Employee added successfully.';
        }

        $this->resetForm();
        $this->modal('mdl-employee')->close();
        Flux::toast(
            heading: 'Changes saved.',
            text: $toast,
        );
    }

//    public function saveEmployee()
//    {
//        // If the ID is null, it's a new employee
//        if ($this->employeeData['id']) {
//            // If employee ID exists, find the employee
//            $employee = Employee::findOrFail($this->employeeData['id']);
//        } else {
//            // If no ID, create a new Employee instance
//            $employee = new Employee();
//        }
//
//        $validatedData = $this->validate([
//            'employeeData.fname' => 'required|string|max:255',
//            'employeeData.mname' => 'nullable|string|max:255',
//            'employeeData.lname' => 'nullable|string|max:255',
//            'employeeData.email' => [
//                'required', 'string', 'email', 'max:255',
//                Rule::unique('employees', 'email')->ignore($this->employeeData['id']),
//            ],
//            'employeeData.phone' => [
//                'required',
//                Rule::unique('employees', 'phone')->ignore($this->employeeData['id']),
//            ],
//            'employeeData.gender' => 'required|in:1,2,3',
//        ]);
//
//        $validatedDataUsr = $this->validate([
//            'employeeData.email' => [
//                'required', 'string', 'email', 'max:255',
//                Rule::unique('users', 'email')->ignore($employee->user_id),
//            ],
//            'employeeData.phone' => [
//                'required',
//                Rule::unique('users', 'phone')->ignore($employee->user_id),
//            ],
//        ]);
//
//        if ($this->isEditing) {
//            // Update existing employee and user
//            $employee->update($validatedData['employeeData']);
//            $user = User::findOrFail($employee->user_id);
//            $user->update($validatedDataUsr['employeeData']);
//
//            // Assign the employee role (or update it)
//            $role = Role::where('name', 'employee')->first();
//            if ($role) {
//                $user->roles()->sync([
//                    $role->id => ['firm_id' => session('firm_id')] // Include firm_id as pivot data
//                ]);
//            }
//
//
////            // Sync firm_user pivot table
////            $firm_user_data = [
////                'firm_id' => session('firm_id'),
////                'is_default' => true, // You can adjust this based on your requirements
////            ];
////            $user->firms()->syncWithoutDetaching([$firm_user_data]);
//
//            $firmId = session('firm_id');
//
//            if (! $user->firms()->where('firm_id', $firmId)->exists()) {
//                // only attach if not present
//                $user->firms()->attach($firmId, [
//                    'is_default' => true,
//                ]);
//            }
//
//
//
//            // Sync panel_user pivot table (assumes a panel ID exists, change as necessary)
//            $panel_id = 1; // Employee panel for App
//            $user->panels()->syncWithoutDetaching([$panel_id]);
//
//            $toast = 'Employee updated successfully.';
//        } else {
//            // Add new employee and user
//            $validatedData['employeeData']['firm_id'] = session('firm_id');
//            $employee = Employee::create($validatedData['employeeData']);
//
//            // Create new user
//            $user = new User();
//            $user->name = $validatedData['employeeData']['fname']." ".$validatedData['employeeData']['lname'];
//            $user->password = 'iqwing@1947';
//            $user->passcode = '1111';
//            $user->email = $validatedDataUsr['employeeData']['email'];
//            $user->phone = $validatedDataUsr['employeeData']['phone'];
//            $user->save();
//
//            // Assign the employee role to the newly created user
//            $role = Role::where('name', 'employee')->first();
//            if ($role) {
//                $user->roles()->sync([
//                    $role->id => ['firm_id' => session('firm_id')] // Include firm_id as pivot data
//                ]);
//            }
//
//            // Link user to the employee
//            $employee->user_id = $user->id;
//            $employee->save();
//
//            // Sync firm_user pivot table for the new user
//            $firm_user_data = [
//                'firm_id' => session('firm_id'),
//                'is_default' => true, // You can adjust this based on your requirements
//            ];
//            $user->firms()->syncWithoutDetaching([$firm_user_data]);
//
//            // Sync panel_user pivot table (assumes a panel ID exists, change as necessary)
//            $panel_id = 1; // Employee panel for App
//            $user->panels()->syncWithoutDetaching([$panel_id]);
//
//            $toast = 'Employee added successfully.';
//        }
//
//        $this->resetForm();
//        $this->modal('mdl-employee')->close();
//        Flux::toast(
//            heading: 'Changes saved.',
//            text: $toast,
//        );
//    }


    protected function initListsForFields(): void
    {
        $this->listsForFields['employeelist'] = Employee::where('firm_id',session('firm_id'))->pluck('fname','id');
        $this->listsForFields['gender'] = [
            '1' => 'Male',
            '2' => 'Female',
            '3' => 'Others'
        ];
    }

    public function resetForm()
    {
        $this->employeeData = [
            'id' => null,
            'fname' => '',
            'mname' => '',
            'lname' => '',
            'email' => '',
            'phone' => '',
            'gender' => '',
        ];
        $this->isEditing = false;
    }
    public function applyFilters()
    {
        $this->resetPage();
    }
    public function clearFilters()
    {
        $this->filters = array_fill_keys(array_merge(array_keys($this->filterFields), ['employees']), '');
        $this->resetPage();
    }
    public function toggleStatus($employeeId)
    {
        $employee = Employee::findOrFail($employeeId);
        $employee->is_inactive = !$employee->is_inactive;
        $employee->save();


        $this->employeeStatuses[$employeeId] = !$employee->is_inactive;


        Flux::toast(
            heading: 'Status Updated',
            text: $employee->is_inactive ? 'Employee has been deactivated.' : 'Employee has been activated.'
        );
    }

    /**
     * Delete an employee record
     *
     * @param int $employeeId
     * @return void
     */
    public function deleteEmployee($employeeId)
    {
        $employee = Employee::findOrFail($employeeId);
        $employeeName = $employee->fname . ' ' . $employee->lname;

        // Delete the employee
        $employee->delete();

        // Show toast notification
        Flux::toast(
            heading: 'Employee Deleted',
            text: "Employee {$employeeName} has been deleted successfully."
        );
    }

    public function showemployeeModal($employeeId = null)
    {
        $this->selectedEmpId = $employeeId;
        $this->modal('edit-employee')->show();
    }

    public function showmodal_addresses($employeeId)
    {
        $this->selectedEmpId = $employeeId;
        $this->modal('add-addresses')->show();
    }
    public function showmodal_employeebankaccounts($employeeId)
    {
        $this->selectedEmpId = $employeeId;
        $this->modal('add-bank-account')->show();
    }
    public function showmodal_contacts($employeeId)
    {
        $this->selectedEmpId = $employeeId;
        $this->modal('add-contacts')->show();
    }
    public function showmodal_jobprofile($employeeId)
    {
        $this->selectedEmpId = $employeeId;
        $this->modal('add-job-profile')->show();
    }
    public function showmodal_addprofile($employeeId)
    {
        $this->selectedEmpId = $employeeId;
        $this->modal('add-personal-details')->show();
    }
    public function showmodal_adddoc($employeeId)
    {
        $this->selectedEmpId = $employeeId;
        $this->modal('add-documents')->show();
    }
    public function showmodal_addrelatons($employeeId)
    {
        $this->selectedEmpId = $employeeId;
        $this->modal('add-relations')->show();
    }
    public function showmodal_attendance_policy($employeeId)
    {
        $this->selectedEmpId = $employeeId;
        $this->modal('add-attendance-policy')->show();
    }
    public function showmodal_work_shift($employeeId)
    {
        $this->selectedEmpId = $employeeId;
        $this->modal('add-work-shift')->show();
    }
    public function showmodal_leave_allocations($employeeId)
    {
        $this->selectedEmpId = $employeeId;
        $this->modal('add-leave-allocations')->show();
    }
    public function showmodal_leave_requests($employeeId)
    {
        $this->selectedEmpId = $employeeId;
        $this->modal('add-leave-requests')->show();
    }

    public function toggleColumn(string $field)
    {
        if (in_array($field, $this->visibleFields)) {
            $this->visibleFields = array_filter(
                $this->visibleFields,
                fn($f) => $f !== $field
            );
        } else {
            $this->visibleFields[] = $field;
        }
    }

    public function toggleFilterColumn(string $field)
    {
        if (in_array($field, $this->visibleFilterFields)) {
            $this->visibleFilterFields = array_filter(
                $this->visibleFilterFields,
                fn($f) => $f !== $field
            );
        } else {
            $this->visibleFilterFields[] = $field;
        }
    }

    public function getProfileCompletionPercentage($employeeId)
    {
        $employee = Employee::find($employeeId);
        if (!$employee) return 0;

        $totalSteps = 10; // Total number of steps in onboarding
        $completedSteps = 0;

        // Check each step's completion
        if ($employee->fname && $employee->email) $completedSteps++; // Basic Info
        if ($employee->emp_address()->exists()) $completedSteps++; // Address
        if ($employee->bank_account()->exists()) $completedSteps++; // Bank Accounts
        if ($employee->emp_emergency_contact()->exists()) $completedSteps++; // Contacts
        if ($employee->emp_job_profile()->exists()) $completedSteps++; // Job Profile
        if ($employee->emp_personal_detail()->exists()) $completedSteps++; // Personal Details
        if ($employee->documents()->exists()) $completedSteps++; // Documents
        if ($employee->relations()->exists()) $completedSteps++; // Relations
        if ($employee->emp_work_shifts()->exists()) $completedSteps++; // Work Shift
        if ($employee->attendance_policy()->exists()) $completedSteps++; // Attendance Policy

        return ($completedSteps / $totalSteps) * 100;
    }

    public function getProfileCompletionData($employeeId)
    {
        $employee = Employee::find($employeeId);
        if (!$employee) return [];

        $data = [];
        
        // Basic Info
        $data[] = $employee->fname && $employee->email ? 100 : 0;
        // Address
        $data[] = $employee->emp_address()->exists() ? 100 : 0;
        // Bank Accounts
        $data[] = $employee->bank_account()->exists() ? 100 : 0;
        // Contacts
        $data[] = $employee->emp_emergency_contact()->exists() ? 100 : 0;
        // Job Profile
        $data[] = $employee->emp_job_profile()->exists() ? 100 : 0;
        // Personal Details
        $data[] = $employee->emp_personal_detail()->exists() ? 100 : 0;
        // Documents
        $data[] = $employee->documents()->exists() ? 100 : 0;
        // Relations
        $data[] = $employee->relations()->exists() ? 100 : 0;
        // Work Shift
        $data[] = $employee->emp_work_shifts()->exists() ? 100 : 0;
        // Attendance Policy
        $data[] = $employee->attendance_policy()->exists() ? 100 : 0;

        return $data;
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Onboard/blades/employees.blade.php'));
    }

    public function closeModal($modalName)
    {
        $this->modal($modalName)->close();
    }

    public function setViewMode($mode)
    {
        $this->viewMode = $mode;
        session(['employees_view_mode' => $mode]);
    }
}
