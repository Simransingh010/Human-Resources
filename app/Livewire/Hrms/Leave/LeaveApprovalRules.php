<?php

namespace App\Livewire\Hrms\Leave;

use App\Models\Hrms\LeaveApprovalRule;
use App\Models\Hrms\LeaveType;
use App\Models\Settings\Department;
use App\Models\Hrms\Employee;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Session;
use Flux;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LeaveApprovalRules extends Component
{
    use WithPagination;

    public $perPage = 10;
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $isEditing = false;
    public $statuses = [];
    public $selectedRuleId = null;
    public $departmentsWithEmployees = [];
    public $filteredDepartmentsWithEmployees = [];
    public $selectedEmployees = [];
    public $employeeSearch = '';

    // Field configuration for form and table
    public array $fieldConfig = [
        'leave_type_id' => ['label' => 'Leave Type', 'type' => 'select', 'listKey' => 'leave_types_list'],
        'approver_id' => ['label' => 'Approver', 'type' => 'select', 'listKey' => 'approvers_list'],
        'approval_level' => ['label' => 'Approval Level', 'type' => 'number'],
        'approval_mode' => ['label' => 'Approval Mode', 'type' => 'select', 'listKey' => 'approval_modes'],
        'auto_approve' => ['label' => 'Auto Approve', 'type' => 'switch'],
        'min_days' => ['label' => 'Min Days', 'type' => 'number'],
        'max_days' => ['label' => 'Max Days', 'type' => 'number'],
        'period_start' => ['label' => 'Period Start', 'type' => 'date'],
        'period_end' => ['label' => 'Period End', 'type' => 'date'],
        'is_inactive' => ['label' => 'Status', 'type' => 'switch'],
    ];

    // Filter fields configuration
    public array $filterFields = [
        'leave_type_id' => ['label' => 'Leave Type', 'type' => 'select', 'listKey' => 'leave_types_list'],
        'approver_id' => ['label' => 'Approver', 'type' => 'select', 'listKey' => 'approvers_list'],
        'approval_mode' => ['label' => 'Approval Mode', 'type' => 'select', 'listKey' => 'approval_modes'],
        'is_inactive' => ['label' => 'Status', 'type' => 'select', 'listKey' => 'status_list'],
    ];

    public array $listsForFields = [];
    public array $filters = [];
    public array $visibleFields = [];
    public array $visibleFilterFields = [];

    public $formData = [
        'id' => null,
        'firm_id' => null,
        'leave_type_id' => '',
        'approver_id' => '',
        'approval_level' => 1,
        'approval_mode' => '',
        'auto_approve' => false,
        'min_days' => null,
        'max_days' => null,
        'period_start' => null,
        'period_end' => null,
        'is_inactive' => false,
    ];

    protected $rules = [
        'formData.leave_type_id' => 'required|exists:leave_types,id',
        'formData.approver_id' => 'nullable|exists:users,id',
        'formData.approval_level' => 'nullable|integer|min:1',
        'formData.approval_mode' => 'nullable|string',
        'formData.auto_approve' => 'boolean',
        'formData.min_days' => 'nullable|numeric|min:0',
        'formData.max_days' => 'nullable|numeric|min:0',
        'formData.period_start' => 'required|date',
        'formData.period_end' => 'required|date|after:formData.period_start',
        'formData.is_inactive' => 'boolean',
        'selectedEmployees' => 'array',
        'selectedEmployees.*' => 'exists:employees,id',
    ];

    protected $messages = [
        'formData.leave_type_id.required' => 'Please select a leave type',
        'formData.period_start.required' => 'Start date is required',
        'formData.period_end.required' => 'End date is required',
        'formData.period_end.after' => 'End date must be after start date',
    ];

    public function mount()
    {
        $this->initListsForFields();

        // Set default visible fields
        $this->visibleFields = ['leave_type_id', 'approver_id', 'approval_level', 'approval_mode', 'auto_approve', 'is_inactive'];
        $this->visibleFilterFields = ['leave_type_id', 'approver_id', 'approval_mode', 'is_inactive'];

        // Initialize filters
        $this->filters = array_fill_keys(array_keys($this->filterFields), '');

        // Initialize statuses
        $this->refreshStatuses();

        // Load departments and employees
        $this->loadDepartmentsWithEmployees();

        // Set default dates
        $this->formData['period_start'] = now()->format('Y-m-d');
        $this->formData['period_end'] = now()->addYear()->format('Y-m-d');
    }

    protected function initListsForFields(): void
    {
        $this->listsForFields['leave_types_list'] = LeaveType::where('firm_id', session('firm_id'))
            ->pluck('leave_title', 'id')
            ->toArray();

        $this->listsForFields['approvers_list'] = User::whereHas('firms', function ($query) {
            $query->where('firms.id', session('firm_id'));
        })
            ->pluck('name', 'id')
            ->toArray();

        $this->listsForFields['approval_modes'] = [
            'sequential' => 'Sequential',
            'parallel' => 'Parallel',
            'any' => 'Any',
            'view_only' => 'View Only'
        ];

        $this->listsForFields['status_list'] = [
            '0' => 'Active',
            '1' => 'Inactive'
        ];
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
        return LeaveApprovalRule::query()
            ->with(['leave_type', 'user'])
            ->where('firm_id', Session::get('firm_id'))
            ->when($this->filters['leave_type_id'], fn($query, $value) =>
                $query->where('leave_type_id', $value))
            ->when($this->filters['approver_id'], fn($query, $value) =>
                $query->where('approver_id', $value))
            ->when($this->filters['approval_mode'], fn($query, $value) =>
                $query->where('approval_mode', $value))
            ->when($this->filters['is_inactive'] !== '', fn($query, $value) =>
                $query->where('is_inactive', $value))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);
    }

    public function refreshStatuses()
    {
        $this->statuses = LeaveApprovalRule::where('firm_id', session('firm_id'))
            ->pluck('is_inactive', 'id')
            ->mapWithKeys(fn($val, $key) => [$key => !(bool) $val])
            ->toArray();
    }

    public function toggleStatus($id)
    {
        $rule = LeaveApprovalRule::find($id);
        $rule->is_inactive = !$rule->is_inactive;
        $rule->save();

        $this->statuses[$id] = !$rule->is_inactive;
        $this->refreshStatuses();
    }

    protected function loadDepartmentsWithEmployees()
    {
        $departments = Department::with([
            'employees' => function ($query) {
                $query->where('is_inactive', false);
            }
        ])
            ->where('firm_id', session('firm_id'))
            ->where('is_inactive', false)
            ->get();

        $this->departmentsWithEmployees = $departments->map(function ($department) {
            return [
                'id' => $department->id,
                'title' => $department->title,
                'employees' => $department->employees->map(function ($employee) {
                    return [
                        'id' => (int) $employee->id, // Ensure ID is integer
                        'fname' => $employee->fname,
                        'lname' => $employee->lname,
                        'email' => $employee->email,
                        'phone' => $employee->phone,
                    ];
                })->toArray()
            ];
        })->toArray();

        $this->filteredDepartmentsWithEmployees = $this->departmentsWithEmployees;
    }

    public function selectAllEmployeesGlobal()
    {
        $allEmployeeIds = collect($this->departmentsWithEmployees)
            ->pluck('employees')
            ->flatten(1)
            ->pluck('id')
            ->map(function ($id) {
                return (string) $id; // Convert to string for consistency with wire:model
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
                    return (string) $id; // Convert to string for consistency with wire:model
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
                    return (string) $id; // Convert to string for consistency
                })
                ->toArray();
            $this->selectedEmployees = array_values(array_diff($this->selectedEmployees, $employeeIds));
        }
    }

    public function updatedEmployeeSearch()
    {
        if (empty($this->employeeSearch)) {
            $this->filteredDepartmentsWithEmployees = $this->departmentsWithEmployees;
            return;
        }

        $search = strtolower($this->employeeSearch);

        $this->filteredDepartmentsWithEmployees = collect($this->departmentsWithEmployees)
            ->map(function ($department) use ($search) {
                $filteredEmployees = collect($department['employees'])
                    ->filter(function ($employee) use ($search) {
                        return str_contains(strtolower($employee['fname'] . ' ' . $employee['lname']), $search) ||
                            str_contains(strtolower($employee['email']), $search) ||
                            str_contains(strtolower($employee['phone']), $search);
                    })
                    ->values()
                    ->all();

                return [
                    'id' => $department['id'],
                    'title' => $department['title'],
                    'employees' => $filteredEmployees
                ];
            })
            ->filter(fn($dept) => !empty($dept['employees']))
            ->values()
            ->all();
    }

    public function toggleEmployee($employeeId)
    {
        if (in_array($employeeId, $this->selectedEmployees)) {
            $this->selectedEmployees = array_values(array_diff($this->selectedEmployees, [$employeeId]));
        } else {
            $this->selectedEmployees[] = $employeeId;
        }
    }

    public function store()
    {
        $validatedData = $this->validate();

        try {
            DB::beginTransaction();

            // If auto_approve is true, ensure approval-related fields are null
            if ($validatedData['formData']['auto_approve']) {
                $validatedData['formData']['approver_id'] = null;
                $validatedData['formData']['approval_level'] = null;
                $validatedData['formData']['approval_mode'] = null;
            }

            $validatedData['formData'] = collect($validatedData['formData'])
                ->map(fn($val) => $val === '' ? null : $val)
                ->toArray();

            $validatedData['formData']['firm_id'] = session('firm_id');

            if ($this->isEditing) {
                $rule = LeaveApprovalRule::findOrFail($this->formData['id']);
                $rule->update($validatedData['formData']);
            } else {
                $rule = LeaveApprovalRule::create($validatedData['formData']);
            }

            // Clear existing employee relationships
            $rule->employees()->detach();

            // Convert selected employee IDs to integers and prepare data for sync
            if (!empty($this->selectedEmployees)) {
                $employeeData = collect($this->selectedEmployees)
                    ->mapWithKeys(function ($employeeId) {
                        return [intval($employeeId) => ['firm_id' => session('firm_id')]];
                    })
                    ->toArray();

                $rule->employees()->sync($employeeData);
            }

            DB::commit();

            $this->resetForm();
            $this->refreshStatuses();
            $this->modal('mdl-approval-rule')->close();

            Flux::toast(
                variant: 'success',
                heading: 'Changes saved.',
                text: $this->isEditing ? 'Approval rule updated successfully' : 'Approval rule added successfully',
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Flux::toast(
                variant: 'error',
                heading: 'Error',
                text: 'Failed to save approval rule: ' . $e->getMessage(),
            );
        }
    }

    public function edit($id)
    {
        $this->isEditing = true;
        $rule = LeaveApprovalRule::with(['employees'])->findOrFail($id);
        $this->formData = $rule->toArray();

        // Format dates properly for the date inputs
        if (isset($this->formData['period_start'])) {
            $this->formData['period_start'] = Carbon::parse($this->formData['period_start'])->format('Y-m-d');
        }

        if (isset($this->formData['period_end'])) {
            $this->formData['period_end'] = Carbon::parse($this->formData['period_end'])->format('Y-m-d');
        }

        // Load selected employees
        $this->selectedEmployees = $rule->employees()->pluck('employee_id')->toArray();

        $this->modal('mdl-approval-rule')->show();
    }

    public function resetForm()
    {
        $this->reset(['formData', 'selectedEmployees', 'employeeSearch']);
        $this->formData['approval_level'] = 1;
        $this->formData['auto_approve'] = false;
        $this->formData['is_inactive'] = false;
        $this->formData['period_start'] = now()->format('Y-m-d');
        $this->formData['period_end'] = now()->addYear()->format('Y-m-d');
        $this->isEditing = false;
        $this->filteredDepartmentsWithEmployees = $this->departmentsWithEmployees;
        $this->resetErrorBag();
    }

    public function delete($id)
    {
        try {
            $rule = LeaveApprovalRule::findOrFail($id);

            // Begin transaction to ensure all deletes succeed or none do
            DB::beginTransaction();

            try {
                // Delete all related employee rules
                $rule->employees()->detach();

                // Delete the main rule
                $rule->forceDelete();

                DB::commit();

                Flux::toast(
                    variant: 'success',
                    heading: 'Record Deleted.',
                    text: 'Approval rule and all related records have been deleted successfully',
                );
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            Flux::toast(
                variant: 'error',
                heading: 'Error',
                text: 'Failed to delete approval rule: ' . $e->getMessage(),
            );
        }
    }

    public function updatedFormDataPeriodStart($value)
    {
        if (!$value) {
            return;
        }

        try {
            $startDate = Carbon::parse($value);

            // If we have a period start date but no period end date, set default end date to 1 year later
            if (!$this->formData['period_end']) {
                $this->formData['period_end'] = $startDate->copy()->addYear()->format('Y-m-d');
            } else {
                // If period_end exists and is earlier than period_start, adjust it
                $endDate = Carbon::parse($this->formData['period_end']);
                if ($endDate->lte($startDate)) {
                    $this->formData['period_end'] = $startDate->copy()->addYear()->format('Y-m-d');
                }
            }
        } catch (\Exception $e) {
            // Handle invalid date format
            $this->addError('formData.period_start', 'Invalid date format');
        }
    }

    public function updatedFormDataAutoApprove($value)
    {
        if ($value) {
            // Reset approval-related fields when auto approve is enabled
            $this->formData['approver_id'] = null;
            $this->formData['approval_level'] = null;
            $this->formData['approval_mode'] = null;
        }
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Leave/blades/leave-approval-rules.blade.php'));
    }
}