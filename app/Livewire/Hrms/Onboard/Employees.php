<?php

namespace App\Livewire\Hrms\Onboard;

use App\Models\User;
use Livewire\Component;
use App\Models\Hrms\Employee;
use Flux;
use Illuminate\Validation\Rule;

class Employees extends Component
{
    use \Livewire\WithPagination;
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
    public $filters = [
        'search' => '',
        'status' => ''
    ];

    public function mount()
    {
        $this->loadEmployeeStatuses();
        $this->initListsForFields();
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

    #[\Livewire\Attributes\Computed]
    public function employeeslist()
    {
        return Employee::query()
            ->when($this->sortBy, fn($query) => $query->orderBy($this->sortBy, $this->sortDirection))
            ->when($this->filters['search'], function($query) {
                $query->where(function($q) {
                    $search = '%' . $this->filters['search'] . '%';
                    $q->where('fname', 'like', $search)
                        ->orWhere('lname', 'like', $search)
                        ->orWhere('email', 'like', $search);
                });
            })
            ->when(!empty($this->filters['employees']), function($query) {
                $query->whereIn('id', $this->filters['employees']);
            })
            ->when(!empty($this->filters['phone']), function($query) {
                $search = '%' . $this->filters['phone'] . '%';
                $query->where('phone','like',  $search);
            })
            ->when(!empty($this->filters['email']), function($query) {
                $search = '%' . $this->filters['email'] . '%';
                $query->where('email','like',  $search);
            })
            ->when($this->filters['status'] !== '', function($query) {
                $query->where('is_inactive', $this->filters['status'] === 'inactive');
            })
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
    public function saveEmployee()
    {

        $employee = Employee::findOrFail($this->employeeData['id']);

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



            $employee->update($validatedData['employeeData']);

            $user = User::findOrFail($employee->user_id);
            $user->update($validatedDataUsr['employeeData']);

            $toast= 'Employee updated successfully.';
        } else {

            $validatedData['employeeData']['firm_id'] = session('firm_id');
            Employee::create($validatedData['employeeData']);
            $toast= 'Employee added successfully.';
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
        // Optional: log or track something
        $this->filters = $this->filters; // triggers reactivity
        $this->resetPage(); // ensure pagination resets after filter
    }
    public function clearFilters()
    {
        $this->reset('filters');
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
}
