<?php

namespace App\Livewire\Hrms\Payroll;

use App\Models\Hrms\EmployeesSalaryExecutionGroup;
use App\Models\Hrms\Employee;
use App\Models\Hrms\SalaryExecutionGroup;
use App\Models\Settings\Department;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Session;
use Flux;

class EmployeesSalaryExecutionGroups extends Component
{
    use WithPagination;

    public $perPage = 10;
    public $sortBy = 'id';
    public $sortDirection = 'desc';
    public $isEditing = false;
    public $selectedRecordId = null;
    public $departmentsWithEmployees = [];
    public $filteredDepartmentsWithEmployees = [];
    public $selectedEmployees = [];
    public $employeeSearch = '';
    public $selectedGroup = null;
    public $groupId = null;

    // Field configuration for form and table
    public array $fieldConfig = [
        'employee_id' => ['label' => 'Employee', 'type' => 'select', 'listKey' => 'employees'],
        'salary_execution_group_id' => ['label' => 'Salary Execution Group', 'type' => 'select', 'listKey' => 'execution_groups'],
    ];

    // Filter fields configuration
    public array $filterFields = [
        'employee_id' => ['label' => 'Employee', 'type' => 'select', 'listKey' => 'employees'],
        'salary_execution_group_id' => ['label' => 'Salary Execution Group', 'type' => 'select', 'listKey' => 'execution_groups'],
    ];

    public array $listsForFields = [];
    public array $filters = [];
    public array $visibleFields = [];
    public array $visibleFilterFields = [];

    public $formData = [
        'id' => null,
        'firm_id' => null,
        'employee_id' => null,
        'salary_execution_group_id' => null,
    ];

    public function mount($groupId = null)
    {
        $this->groupId = $groupId;
        $this->initListsForFields();
        $this->loadDepartmentsWithEmployees();

        // Set default visible fields
        $this->visibleFields = ['employee_id'];
        $this->visibleFilterFields = ['employee_id'];

        // Initialize filters
        $this->filters = array_fill_keys(array_keys($this->filterFields), '');

        // If groupId is provided, set it as the selected group
        if ($this->groupId) {
            $this->selectedGroup = $this->groupId;
        }
    }

    protected function loadDepartmentsWithEmployees()
    {
        $departments = Department::with([
            'employees' => function ($query) {
                $query->where('is_inactive', false);
            }
        ])
            ->where('firm_id', Session::get('firm_id'))
            ->where('is_inactive', false)
            ->get();

        $this->departmentsWithEmployees = $departments->map(function ($department) {
            return [
                'id' => $department->id,
                'title' => $department->title,
                'employees' => $department->employees->map(function ($employee) {
                    return [
                        'id' => $employee->id,
                        'fname' => $employee->fname,
                        'lname' => $employee->lname,
                        'email' => $employee->email,
                        'phone' => $employee->phone,
                    ];
                })->toArray()
            ];
        })->toArray();

        $this->filterEmployees();
    }

    public function updatedEmployeeSearch()
    {
        $this->filterEmployees();
    }

    protected function filterEmployees()
    {
        $departments = collect($this->departmentsWithEmployees);

        // Get existing employee IDs for this group
        $existingEmployeeIds = EmployeesSalaryExecutionGroup::pluck('employee_id')->toArray();



        $filteredDepartments = $departments->map(function ($department) use ($existingEmployeeIds) {
            $filteredEmployees = collect($department['employees'])->filter(function ($employee) use ($existingEmployeeIds) {
                // Skip if employee is already assigned
                if (in_array($employee['id'], $existingEmployeeIds)) {
                    return false;
                }

                $searchTerm = strtolower($this->employeeSearch);
                $employeeName = strtolower($employee['fname'] . ' ' . $employee['lname']);
                $employeeEmail = strtolower($employee['email'] ?? '');
                $employeePhone = strtolower($employee['phone'] ?? '');

                return empty($this->employeeSearch) ||
                    str_contains($employeeName, $searchTerm) ||
                    str_contains($employeeEmail, $searchTerm) ||
                    str_contains($employeePhone, $searchTerm);
            });

            return [
                'id' => $department['id'],
                'title' => $department['title'],
                'employees' => $filteredEmployees->values()->all()
            ];
        })->filter(function ($department) {
            return !empty($department['employees']);
        })->values()->all();

        $this->filteredDepartmentsWithEmployees = $filteredDepartments;
    }

    public function selectAllEmployeesGlobal()
    {
        $allEmployeeIds = collect($this->departmentsWithEmployees)
            ->pluck('employees')
            ->flatten(1)
            ->pluck('id')
            ->map(function ($id) {
                return (string) $id;
            })
            ->toArray();

        $this->selectedEmployees = array_unique($allEmployeeIds);
    }

    public function deselectAllEmployeesGlobal()
    {
        $this->selectedEmployees = [];
    }

    public function selectAllEmployees($departmentId)
    {
        $department = collect($this->departmentsWithEmployees)->firstWhere('id', $departmentId);
        if ($department) {
            $employeeIds = collect($department['employees'])
                ->pluck('id')
                ->map(function ($id) {
                    return (string) $id;
                })
                ->toArray();
            $this->selectedEmployees = array_unique(array_merge($this->selectedEmployees, $employeeIds));
        }
    }

    public function deselectAllEmployees($departmentId)
    {
        $department = collect($this->departmentsWithEmployees)->firstWhere('id', $departmentId);
        if ($department) {
            $employeeIds = collect($department['employees'])
                ->pluck('id')
                ->map(function ($id) {
                    return (string) $id;
                })
                ->toArray();
            $this->selectedEmployees = array_values(array_diff($this->selectedEmployees, $employeeIds));
        }
    }

    protected function initListsForFields(): void
    {
        // Get employees for dropdown
        $this->listsForFields['employees'] = Employee::where('firm_id', Session::get('firm_id'))
            ->where('is_inactive', false)
            ->get()
            ->mapWithKeys(function ($employee) {
                return [$employee->id => $employee->fname . ' ' . $employee->lname];
            })
            ->toArray();

        // Get salary execution groups for dropdown
        $this->listsForFields['execution_groups'] = SalaryExecutionGroup::where('firm_id', Session::get('firm_id'))
            ->where('is_inactive', false)
            ->pluck('title', 'id')
            ->toArray();
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

    #[Computed]
    public function list()
    {
        return EmployeesSalaryExecutionGroup::query()
            ->where('firm_id', Session::get('firm_id'))
            ->when($this->groupId, fn($query) =>
                $query->where('salary_execution_group_id', $this->groupId))
            ->when($this->filters['employee_id'], fn($query, $value) =>
                $query->where('employee_id', $value))
            ->when($this->filters['salary_execution_group_id'], fn($query, $value) =>
                $query->where('salary_execution_group_id', $value))
            ->with(['employee', 'salary_execution_group'])
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);
    }

    protected function rules()
    {
        return [
            'selectedEmployees' => 'required|array|min:1',
            'selectedEmployees.*' => 'exists:employees,id',
            'selectedGroup' => 'required|exists:salary_execution_groups,id',
        ];
    }

    public function store()
    {
        $validatedData = $this->validate();

        // Always use groupId if set
        $groupIdToUse = $this->groupId ?: $validatedData['selectedGroup'];

        // Check for existing assignments
        $existingAssignments = EmployeesSalaryExecutionGroup::where('firm_id', session('firm_id'))
            ->whereIn('employee_id', $validatedData['selectedEmployees'])
            ->where('salary_execution_group_id', $groupIdToUse)
            ->get();

        if ($existingAssignments->isNotEmpty()) {
            $existingEmployeeIds = $existingAssignments->pluck('employee_id');
            $existingEmployees = Employee::whereIn('id', $existingEmployeeIds)
                ->get()
                ->map(fn($emp) => $emp->fname . ' ' . $emp->lname)
                ->implode(', ');

            Flux::toast(
                variant: 'error',
                heading: 'Error',
                text: "The following employees are already assigned to this group: {$existingEmployees}",
            );
            return;
        }

        // Create assignments for each selected employee
        foreach ($validatedData['selectedEmployees'] as $employeeId) {
            EmployeesSalaryExecutionGroup::create([
                'firm_id' => session('firm_id'),
                'employee_id' => $employeeId,
                'salary_execution_group_id' => $groupIdToUse,
            ]);
        }

        $this->resetForm();
        $this->modal('mdl-employee-assignment')->close();

//

        Flux::toast(
            variant: 'success',
            heading: 'Success',
            text: 'Employees assigned successfully to the salary execution group.',
        );
    }

    public function resetForm()
    {
        $this->reset(['formData', 'selectedEmployees', 'selectedGroup', 'employeeSearch']);
        $this->isEditing = false;
        $this->loadDepartmentsWithEmployees();
    }

    public function edit($id)
    {
        $this->isEditing = true;
        $record = EmployeesSalaryExecutionGroup::findOrFail($id);
        $this->formData = array_merge($this->formData, $record->toArray());
        $this->modal('mdl-employee-assignment')->show();
    }

    public function delete($id)
    {
        $record = EmployeesSalaryExecutionGroup::findOrFail($id);
        $record->delete();

        Flux::toast(
            variant: 'success',
            heading: 'Record Deleted.',
            text: 'Employee assignment has been removed successfully',
        );
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Payroll/blades/employees-salary-execution-groups.blade.php'));
    }
}