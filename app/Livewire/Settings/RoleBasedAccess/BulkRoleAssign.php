<?php

namespace App\Livewire\Settings\RoleBasedAccess;

use App\Models\Batch;
use App\Models\BatchItem;
use App\Models\Hrms\Employee;
use App\Models\Settings\Department;
use App\Models\Saas\Role;
use App\Models\User;
use App\Models\Saas\ActionRole;
use App\Models\Saas\ActionUser;
use App\Services\BulkOperationService;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Flux;

class BulkRoleAssign extends Component
{
    use WithPagination;

    public $perPage = 10;
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $showItemsModal = false;
    public $selectedBatchId = null;
    public $isEditing = false;
    public $selectedEmployees = [];
    public $roles = [];
    public $departmentsWithEmployees = [];
    public $filteredDepartmentsWithEmployees = [];
    public $selectedRole = null;
    public $employeeSearch = '';
    public $allDepartmentsWithEmployees = [];
    public $employeeRolesMap = [];
    public $batchItems;

    // Properties for employment type filtering
    public $employmentTypes = [];
    public $selectedEmploymentType = null;

    // Field configuration for form and table
    public array $fieldConfig = [
        'title' => ['label' => 'Batch Title', 'type' => 'text'],
        'modulecomponent' => ['label' => 'Module', 'type' => 'select', 'listKey' => 'modules'],
        'action' => ['label' => 'Action', 'type' => 'select', 'listKey' => 'actions'],
        'created_at' => ['label' => 'Created Date', 'type' => 'date'],
        'items_count' => ['label' => 'Items Count', 'type' => 'badge'],
    ];

    // Filter fields configuration
    public array $filterFields = [
        'title' => ['label' => 'Batch Title', 'type' => 'text'],
        'modulecomponent' => ['label' => 'Module', 'type' => 'select', 'listKey' => 'modules'],
        'action' => ['label' => 'Action', 'type' => 'select', 'listKey' => 'actions'],
    ];

    public array $listsForFields = [];
    public array $filters = [];
    public array $visibleFields = [];
    public array $visibleFilterFields = [];

    public $formData = [
        'id' => null,
        'firm_id' => null,
        'role_id' => null,
    ];

    protected function rules()
    {
        return [
            'formData.role_id' => 'required|exists:roles,id',
            'selectedEmployees' => 'required|array|min:1',
            'selectedEmployees.*' => 'exists:employees,id',
        ];
    }

    public function mount()
    {
        $this->initListsForFields();
        $this->formData['firm_id'] = session('firm_id');
        $this->loadRoles();
        $this->loadDepartmentsWithEmployees();
        $this->loadEmploymentTypes();
        $this->fetchEmployeeRolesMap();

        // Set default visible fields
        $this->visibleFields = ['title', 'modulecomponent', 'action', 'created_at', 'items_count'];
        $this->visibleFilterFields = ['title', 'modulecomponent', 'action'];

        // Initialize filters
        $this->filters = array_fill_keys(array_keys($this->filterFields), '');

        // Initialize filtered departments
        $this->filteredDepartmentsWithEmployees = $this->departmentsWithEmployees;
    }

    protected function fetchEmployeeRolesMap()
    {
        $firmId = session('firm_id');
        $employees = Employee::with(['user.roles' => function($q) use ($firmId) {
            $q->where('role_user.firm_id', $firmId); // Specify the table!
        }])->where('is_inactive', false)->get();
        $map = [];
        foreach ($employees as $employee) {
            $roleIds = $employee->user ? $employee->user->roles->pluck('id')->map(fn($id) => (string)$id)->toArray() : [];
            $map[(string)$employee->id] = $roleIds;
        }
        $this->employeeRolesMap = $map;
    }

    public function updatedEmployeeSearch()
    {
        $this->filterEmployees();
    }

    protected function initListsForFields(): void
    {
        $this->listsForFields['modules'] = [
            'role_assignment' => 'Role Assignment',
            'permissions' => 'Permissions',
            'access_control' => 'Access Control'
        ];

        $this->listsForFields['actions'] = [
            'assigned' => 'Assigned',
            'revoked' => 'Revoked',
            'updated' => 'Updated'
        ];
    }

    protected function loadRoles()
    {
        $this->roles = Role::where('firm_id', session('firm_id'))
            ->where('is_inactive', false)
            ->get();
    }

    public function updatedFormDataRoleId($value)
    {
        if ($value) {
            $this->selectedRole = $this->roles->find($value);
            if (!$this->selectedRole) {
                Flux::toast(
                    variant: 'error',
                    heading: 'Error',
                    text: 'Selected role not found.',
                );
                return;
            }
        } else {
            $this->selectedRole = null;
        }
        $this->filterEmployees();
    }

    protected function loadDepartmentsWithEmployees()
    {
        // Load all data once with necessary relationships
        $departments = Department::with([
            'employees' => function ($query) {
                $query->where('is_inactive', false)
                    ->with(['emp_job_profile.employment_type']);
            }
        ])
            ->where('firm_id', session('firm_id'))
            ->where('is_inactive', false)
            ->get();

        // Store the complete data
        $this->allDepartmentsWithEmployees = $departments->map(function ($department) {
            return [
                'id' => $department->id,
                'title' => $department->title,
                'employees' => $department->employees->map(function ($employee) {
                    return [
                        'id' => (int) $employee->id,
                        'fname' => $employee->fname,
                        'lname' => $employee->lname,
                        'email' => $employee->email,
                        'phone' => $employee->phone,
                        'employment_type_id' => $employee->emp_job_profile?->employment_type_id,
                        'employment_type' => $employee->emp_job_profile?->employment_type?->title ?? 'N/A',
                    ];
                })->toArray()
            ];
        })->toArray();

        $this->filterEmployees();
    }

    protected function filterEmployees()
    {
        // Start with the original unfiltered data
        $departments = collect($this->allDepartmentsWithEmployees);

        // Apply filters while preserving original employee data
        $filteredDepartments = $departments->map(function ($department) {
            $filteredEmployees = collect($department['employees'])->filter(function ($employee) {
                // Employment type filter
                $matchesEmploymentType = !$this->selectedEmploymentType ||
                    $employee['employment_type_id'] == $this->selectedEmploymentType;

                // Search filter - case insensitive
                $searchTerm = strtolower($this->employeeSearch);
                $employeeName = strtolower($employee['fname'] . ' ' . $employee['lname']);
                $employeeEmail = strtolower($employee['email'] ?? '');
                $employeePhone = strtolower($employee['phone'] ?? '');

                $matchesSearch = empty($this->employeeSearch) ||
                    str_contains($employeeName, $searchTerm) ||
                    str_contains($employeeEmail, $searchTerm) ||
                    str_contains($employeePhone, $searchTerm);

                // Exclude employees who already have the selected role
                $doesNotHaveRole = true;
                if ($this->formData['role_id']) {
                    $roles = $this->employeeRolesMap[(string)$employee['id']] ?? [];
                    $doesNotHaveRole = !in_array((string)$this->formData['role_id'], $roles);
                }

                return $matchesEmploymentType && $matchesSearch && $doesNotHaveRole;
            });

            // Preserve the original employee data structure
            return [
                'id' => $department['id'],
                'title' => $department['title'],
                'employees' => $filteredEmployees->values()->all()
            ];
        })->filter(function ($department) {
            return !empty($department['employees']);
        })->values()->all();

        $this->departmentsWithEmployees = $filteredDepartments;
        $this->filteredDepartmentsWithEmployees = $filteredDepartments;
    }

    public function updatedSelectedEmploymentType()
    {
        $this->filterEmployees();
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

    public function store()
    {
        try {
            $this->validate();

            // Get the actual selected employee IDs and ensure they're integers
            $actualSelectedEmployeeIds = array_map('intval', $this->selectedEmployees);

            // Get employee details for the actually selected IDs
            $selectedEmployeeDetails = Employee::with(['emp_job_profile.employment_type'])
                ->whereIn('id', $actualSelectedEmployeeIds)
                ->get()
                ->map(function ($employee) {
                    return [
                        'id' => $employee->id,
                        'name' => $employee->fname . ' ' . $employee->lname,
                        'employment_type' => $employee->emp_job_profile?->employment_type?->title ?? 'N/A',
                        'email' => $employee->email,
                        'role' => $this->selectedRole?->name
                    ];
                })
                ->toArray();

            DB::beginTransaction();
            try {
                // Start the batch operation
                $batchTitle = "Role Assignment - Role: {$this->selectedRole->name}";

                $batch = BulkOperationService::start(
                    'role_assignment',
                    'bulk_assignment',
                    $batchTitle
                );

                // Process each selected employee
                foreach ($actualSelectedEmployeeIds as $employeeId) {
                    $this->assignRoleToEmployee($batch, $employeeId, $this->formData['role_id']);
                }

                DB::commit();
                $this->selectedBatchId = $batch->id;

                // Refresh employee roles map and employee list after save
                $this->fetchEmployeeRolesMap();
                $this->loadDepartmentsWithEmployees();

                Flux::toast(
                    heading: 'Success',
                    text: 'Role assignments saved successfully.',
                );

                $this->resetForm();
                $this->modal('mdl-batch')->close();

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            Flux::toast(
                variant: 'error',
                heading: 'Error',
                text: 'Failed to assign role: ' . $e->getMessage(),
            );
        }
    }

    protected function assignRoleToEmployee($batch, $employeeId, $roleId)
    {
        DB::beginTransaction();
        try {
            $firmId = session('firm_id');
            $employee = Employee::with('user')->find($employeeId);
            if (!$employee || !$employee->user) {
                throw new \Exception("Employee or associated user not found for ID {$employeeId}");
            }
            $user = $employee->user;
            $existingRole = $user->roles()->where('role_id', $roleId)->wherePivot('firm_id', $firmId)->first();
            if ($existingRole) {
                $oldData = [
                    'role_id' => $roleId,
                    'user_id' => $user->id
                ];
                $user->roles()->updateExistingPivot($roleId, []);
                // Fetch the RoleUser pivot row
                $roleUser = \App\Models\Saas\RoleUser::where([
                    'user_id' => $user->id,
                    'role_id' => $roleId,
                    'firm_id' => $firmId,
                ])->first();
                BatchItem::create([
                    'batch_id' => $batch->id,
                    'operation' => 'update',
                    'model_type' => 'role_user',
                    'model_id' => $roleUser ? $roleUser->id : null,
                    'old_data' => json_encode($oldData),
                    'new_data' => json_encode([])
                ]);
                Flux::toast(
                    variant: 'info',
                    heading: 'Role Updated',
                    text: "Role assignment updated for employee ID {$employeeId}.",
                );
            } else {
                $user->roles()->attach($roleId, ['firm_id' => $firmId]);
                // Fetch the RoleUser pivot row
                $roleUser = \App\Models\Saas\RoleUser::where([
                    'user_id' => $user->id,
                    'role_id' => $roleId,
                    'firm_id' => $firmId,
                ])->first();
                BatchItem::create([
                    'batch_id' => $batch->id,
                    'operation' => 'insert',
                    'model_type' => 'role_user',
                    'model_id' => $roleUser ? $roleUser->id : null,
                    'new_data' => json_encode([
                        'user_id' => $user->id,
                        'role_id' => $roleId,
                        'firm_id' => $firmId
                    ])
                ]);
            }
            $this->syncUserActionsWithRole($user, $firmId);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception(
                "Error processing role assignment for employee ID {$employeeId}: " . $e->getMessage()
            );
        }
    }

    /**
     * Sync actions for a user based on all their roles in the firm (like FirmUsers.php)
     */
    protected function syncUserActionsWithRole($user, $firmId)
    {
        // Get all roles for this user/firm
        $roleIds = $user->roles()->pluck('roles.id')->toArray();
        // Get all ActionRole for these roles/firm
        $actionRoles = ActionRole::whereIn('role_id', $roleIds)->where('firm_id', $firmId)->get();
        $actionMap = [];
        foreach ($actionRoles as $ar) {
            $actionMap[$ar->action_id] = $ar->records_scope;
        }
        // Remove ActionUser not in this set
        ActionUser::where('user_id', $user->id)->where('firm_id', $firmId)
            ->whereNotIn('action_id', array_keys($actionMap))->delete();
        // Add/update ActionUser for each action
        foreach ($actionMap as $actionId => $recordsScope) {
            ActionUser::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'firm_id' => $firmId,
                    'action_id' => $actionId,
                ],
                [
                    'records_scope' => $recordsScope,
                ]
            );
        }
    }

    public function rollbackBatch($batchId)
    {
        try {
            DB::transaction(function () use ($batchId) {
                $batch = Batch::with('items')->findOrFail($batchId);

                // Remove all role assignments from pivot table
                $roleAssignmentItems = $batch->items()
                    ->where('model_type', 'role_user')
                    ->get();

                foreach ($roleAssignmentItems as $item) {
                    $newData = json_decode($item->new_data, true);
                    if (isset($newData['user_id']) && isset($newData['role_id'])) {
                        $user = User::find($newData['user_id']);
                        if ($user) {
                            $user->roles()->detach($newData['role_id']);
                        }
                    }
                }

                // Force delete the batch itself
                $batch->forceDelete();
            });

            Flux::toast(
                heading: 'Success',
                text: 'Role assignments rolled back and permanently deleted.',
            );
        } catch (\Exception $e) {
            Flux::toast(
                variant: 'error',
                heading: 'Error',
                text: 'Failed to rollback: ' . $e->getMessage(),
            );
        }
    }

    public function edit($id)
    {
        $batch = Batch::findOrFail($id);
        $this->formData = $batch->toArray();
        $this->isEditing = true;
        $this->modal('mdl-batch')->show();
    }

    public function resetForm()
    {
        $this->formData = [
            'id' => null,
            'firm_id' => session('firm_id'),
            'role_id' => null,
        ];
        $this->selectedEmployees = [];
        $this->selectedRole = null;
        $this->isEditing = false;
        $this->employeeSearch = '';
        $this->selectedEmploymentType = null;

        // Reset the filtered departments to show all employees
        $this->loadDepartmentsWithEmployees();
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
        return Batch::query()
            ->where('modulecomponent', 'role_assignment')
            ->when($this->filters['title'] ?? null, fn($query, $value) =>
                $query->where('title', 'like', "%{$value}%"))
            ->withCount('items as items_count')
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);
    }

    public function showBatchItems($batchId)
    {
        $this->viewBatchItems($batchId);
        $this->selectedBatchId = $batchId;
        $this->showItemsModal = true;
    }

    public function viewBatchItems($batchId)
    {
        // Load batch items with employee and role relationships (nested)
        $this->batchItems = \App\Models\BatchItem::where('batch_id', $batchId)
            ->with(['employee', 'role.role'])
            ->get();
    }

    public function rollbackEmployeeAssignment($employeeId)
    {
        try {
            $batchId = $this->selectedBatchId;
            if (!$batchId || !$employeeId) {
                throw new \Exception("Invalid batch or employee ID.");
            }
            DB::transaction(function () use ($batchId, $employeeId) {
                // Find all batch items for this employee in the current batch
                $batchItems = \App\Models\BatchItem::where('batch_id', $batchId)
                    ->where('model_type', 'role_user')
                    ->whereHas('role', function($q) use ($employeeId) {
                        $q->where('user_id', $employeeId);
                    })
                    ->get();
                if ($batchItems->isEmpty()) {
                    throw new \Exception("No role assignment items found for this employee.");
                }
                foreach ($batchItems as $item) {
                    // Remove the role assignment from the pivot table
                    if ($item->role) {
                        $item->role->delete();
                    }
                    $item->forceDelete();
                }
                // Reload batch items to refresh the view
                $this->viewBatchItems($batchId);
                // If there are no more items in the batch, close the modal and potentially delete the batch
                if ($this->batchItems->isEmpty()) {
                    $this->showItemsModal = false;
                    $batch = \App\Models\Batch::find($batchId);
                    if ($batch && $batch->items()->count() === 0) {
                        $batch->forceDelete();
                    }
                    $this->selectedBatchId = null;
                }
                $employee = \App\Models\User::find($employeeId);
                $employeeName = $employee ? $employee->name : 'Employee';
                Flux::toast(
                    heading: 'Success',
                    text: 'Role assignment for ' . $employeeName . ' rolled back successfully.',
                );
            });
            // Refresh employee roles map and employee list after rollback
            $this->fetchEmployeeRolesMap();
            $this->loadDepartmentsWithEmployees();
        } catch (\Exception $e) {
            Flux::toast(
                variant: 'error',
                heading: 'Error',
                text: 'Failed to rollback assignment: ' . $e->getMessage(),
            );
        }
    }

    public function closeItemsModal()
    {
        $this->showItemsModal = false;
        $this->selectedBatchId = null;
    }

    public function delete($id)
    {
        $batch = Batch::findOrFail($id);

        if ($batch->items()->count() > 0) {
            $batch->items()->delete();
        }

        $batch->delete();

        Flux::toast(
            variant: 'success',
            heading: 'Record Deleted.',
            text: 'Batch has been deleted successfully',
        );
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Settings/RoleBasedAccess/blades/bulk-role-assign.blade.php'));
    }

    protected function loadEmploymentTypes()
    {
        $this->employmentTypes = \App\Models\Settings\EmploymentType::where('firm_id', session('firm_id'))
            ->where('is_inactive', false)
            ->get();
    }

    public function clearEmployeeSearch()
    {
        $this->employeeSearch = '';
        $this->selectedEmploymentType = null;
        $this->filterEmployees();
    }
}
