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

    public function mount()
    {
        $this->loadEmployeeStatuses();
        $this->resetPage();
        $this->initListsForFields();
        $this->visibleFields = ['fname', 'lname', 'email', 'phone'];
        $this->visibleFilterFields = ['fname', 'lname', 'email', 'phone'];
        $this->filters = array_fill_keys(array_keys($this->filterFields), '');
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
            ->when($this->sortBy, fn($query) => $query->orderBy($this->sortBy, $this->sortDirection))
            ->when($this->filters['fname'], fn($query, $value) => $query->where('fname', 'like', "%{$value}%"))
            ->when($this->filters['lname'], fn($query, $value) => $query->where('lname', 'like', "%{$value}%"))
            ->when($this->filters['email'], fn($query, $value) => $query->where('email', 'like', "%{$value}%"))
            ->when($this->filters['phone'], fn($query, $value) => $query->where('phone', 'like', "%{$value}%"))
            ->when($this->filters['gender'], fn($query, $value) => $query->where('gender', $value))
            ->where('firm_id', session('firm_id'))
            ->paginate(10);
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

    public function saveEmployee()
    {
        // If the ID is null, it's a new employee
        if ($this->employeeData['id']) {
            // If employee ID exists, find the employee
            $employee = Employee::findOrFail($this->employeeData['id']);
        } else {
            // If no ID, create a new Employee instance
            $employee = new Employee();
        }

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
                $user->roles()->sync([
                    $role->id => ['firm_id' => session('firm_id')] // Include firm_id as pivot data
                ]);
            }


//            // Sync firm_user pivot table
//            $firm_user_data = [
//                'firm_id' => session('firm_id'),
//                'is_default' => true, // You can adjust this based on your requirements
//            ];
//            $user->firms()->syncWithoutDetaching([$firm_user_data]);

            $firmId = session('firm_id');

            if (! $user->firms()->where('firm_id', $firmId)->exists()) {
                // only attach if not present
                $user->firms()->attach($firmId, [
                    'is_default' => true,
                ]);
            }
            


            // Sync panel_user pivot table (assumes a panel ID exists, change as necessary)
            $panel_id = 1; // Employee panel for App
            $user->panels()->syncWithoutDetaching([$panel_id]);

            $toast = 'Employee updated successfully.';
        } else {
            // Add new employee and user
            $validatedData['employeeData']['firm_id'] = session('firm_id');
            $employee = Employee::create($validatedData['employeeData']);

            // Create new user
            $user = new User();
            $user->name = $validatedData['employeeData']['fname']." ".$validatedData['employeeData']['lname'];
            $user->password = 'iqwing@1947';
            $user->passcode = '1111';
            $user->email = $validatedDataUsr['employeeData']['email'];
            $user->phone = $validatedDataUsr['employeeData']['phone'];
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

            // Sync firm_user pivot table for the new user
            $firm_user_data = [
                'firm_id' => session('firm_id'),
                'is_default' => true, // You can adjust this based on your requirements
            ];
            $user->firms()->syncWithoutDetaching([$firm_user_data]);

            // Sync panel_user pivot table (assumes a panel ID exists, change as necessary)
            $panel_id = 1; // Employee panel for App
            $user->panels()->syncWithoutDetaching([$panel_id]);

            $toast = 'Employee added successfully.';
        }

        $this->resetForm();
        $this->modal('mdl-employee')->close();
        Flux::toast(
            heading: 'Changes saved.',
            text: $toast,
        );
    }


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
        $this->filters = array_fill_keys(array_keys($this->filterFields), '');
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

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Onboard/blades/employees.blade.php'));
    }
}
