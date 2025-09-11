<?php

namespace App\Livewire\Settings\RoleBasedAccess;

use App\Models\Batch;
use App\Models\BatchItem;
use App\Models\Hrms\Employee;
use App\Models\Settings\Department;
use App\Models\Saas\Panel;
use App\Models\User;
use App\Models\Saas\PanelUser;
use App\Services\BulkOperationService;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Flux;

class PanelBulkAssign extends Component
{
    use WithPagination;

    public $perPage = 10;
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $showItemsModal = false;
    public $selectedBatchId = null;
    public $isEditing = false;
    public $selectedEmployees = [];
    public $panels = [];
    public $departmentsWithEmployees = [];
    public $filteredDepartmentsWithEmployees = [];
    public $selectedPanel = null;
    public $employeeSearch = '';
    public $allDepartmentsWithEmployees = [];
    public $employeePanelsMap = [];
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
        'panel_id' => null,
    ];

    protected function rules()
    {
        return [
            'formData.panel_id' => 'required|exists:panels,id',
            'selectedEmployees' => 'required|array|min:1',
            'selectedEmployees.*' => 'exists:employees,id',
        ];
    }

    public function mount()
    {
        $this->initListsForFields();
        $this->formData['firm_id'] = session('firm_id');
        $this->loadPanels();
        $this->loadDepartmentsWithEmployees();
        $this->loadEmploymentTypes();
        $this->fetchEmployeePanelsMap();

        // Set default visible fields
        $this->visibleFields = ['title', 'modulecomponent', 'action', 'created_at', 'items_count'];
        $this->visibleFilterFields = ['title', 'modulecomponent', 'action'];

        // Initialize filters
        $this->filters = array_fill_keys(array_keys($this->filterFields), '');

        // Initialize filtered departments
        $this->filteredDepartmentsWithEmployees = $this->departmentsWithEmployees;
    }

    protected function fetchEmployeePanelsMap()
    {
        $firmId = session('firm_id');
        $cacheKey = "employee_panels_map_{$firmId}";
        
        $this->employeePanelsMap = Cache::remember($cacheKey, 300, function () use ($firmId) {
            $employees = Employee::with(['user.panels' => function($q) use ($firmId) {
                $q->where('panel_user.firm_id', $firmId);
            }])->where('is_inactive', false)->get();
            
            $map = [];
            foreach ($employees as $employee) {
                $panelIds = $employee->user ? $employee->user->panels->pluck('id')->map(fn($id) => (string)$id)->toArray() : [];
                $map[(string)$employee->id] = $panelIds;
            }
            return $map;
        });
    }

    public function updatedEmployeeSearch()
    {
        $this->filterEmployees();
    }

    protected function initListsForFields(): void
    {
        $this->listsForFields['modules'] = [
            'panel_assignment' => 'Panel Assignment',
            'access_control' => 'Access Control',
            'user_management' => 'User Management'
        ];

        $this->listsForFields['actions'] = [
            'assigned' => 'Assigned',
            'revoked' => 'Revoked',
            'updated' => 'Updated'
        ];
    }

    protected function loadPanels()
    {
        $firmId = session('firm_id');
        $cacheKey = "firm_panels_{$firmId}";
        
        $this->panels = Cache::remember($cacheKey, 300, function () use ($firmId) {
            return Panel::whereHas('firms', function($q) use ($firmId) {
                $q->where('firm_id', $firmId);
            })->where('is_inactive', false)->get();
        });
    }

    public function updatedFormDataPanelId($value)
    {
        if ($value) {
            $this->selectedPanel = $this->panels->find($value);
            if (!$this->selectedPanel) {
                Flux::toast(
                    variant: 'error',
                    heading: 'Error',
                    text: 'Selected panel not found.',
                );
                return;
            }
        } else {
            $this->selectedPanel = null;
        }
        $this->filterEmployees();
    }

    protected function loadDepartmentsWithEmployees()
    {
        $firmId = session('firm_id');
        $cacheKey = "departments_with_employees_{$firmId}";
        
        // Load all data once with necessary relationships
        $departments = Cache::remember($cacheKey, 300, function () use ($firmId) {
            return Department::with([
                'employees' => function ($query) {
                    $query->where('is_inactive', false)
                        ->with(['emp_job_profile.employment_type']);
                }
            ])
                ->where('firm_id', $firmId)
                ->where('is_inactive', false)
                ->get();
        });

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

                // Exclude employees who already have the selected panel
                $doesNotHavePanel = true;
                if ($this->formData['panel_id']) {
                    $panels = $this->employeePanelsMap[(string)$employee['id']] ?? [];
                    $doesNotHavePanel = !in_array((string)$this->formData['panel_id'], $panels);
                }

                return $matchesEmploymentType && $matchesSearch && $doesNotHavePanel;
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
                        'panel' => $this->selectedPanel?->name
                    ];
                })
                ->toArray();

            DB::beginTransaction();
            try {
                // Start the batch operation
                $batchTitle = "Panel Assignment - Panel: {$this->selectedPanel->name}";

                $batch = BulkOperationService::start(
                    'panel_assignment',
                    'bulk_assignment',
                    $batchTitle
                );

                // Process each selected employee
                foreach ($actualSelectedEmployeeIds as $employeeId) {
                    $this->assignPanelToEmployee($batch, $employeeId, $this->formData['panel_id']);
                }

                DB::commit();
                $this->selectedBatchId = $batch->id;

                // Refresh caches and data after save
                $this->refreshCaches();
                $this->fetchEmployeePanelsMap();
                $this->loadDepartmentsWithEmployees();

                Flux::toast(
                    heading: 'Success',
                    text: 'Panel assignments saved successfully.',
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
                text: 'Failed to assign panel: ' . $e->getMessage(),
            );
        }
    }

    protected function assignPanelToEmployee($batch, $employeeId, $panelId)
    {
        DB::beginTransaction();
        try {
            $firmId = session('firm_id');
            $employee = Employee::with('user')->find($employeeId);
            if (!$employee || !$employee->user) {
                throw new \Exception("Employee or associated user not found for ID {$employeeId}");
            }
            $user = $employee->user;
            
            // Check if panel assignment already exists
            $existingPanelUser = PanelUser::where([
                'user_id' => $user->id,
                'panel_id' => $panelId,
                'firm_id' => $firmId,
            ])->first();

            if ($existingPanelUser) {
                $oldData = [
                    'panel_id' => $panelId,
                    'user_id' => $user->id,
                    'firm_id' => $firmId
                ];
                
                BatchItem::create([
                    'batch_id' => $batch->id,
                    'operation' => 'update',
                    'model_type' => 'panel_user',
                    'model_id' => $existingPanelUser->id,
                    'original_data' => json_encode($oldData),
                    'new_data' => json_encode($oldData)
                ]);
                
                Flux::toast(
                    variant: 'info',
                    heading: 'Panel Updated',
                    text: "Panel assignment updated for employee ID {$employeeId}.",
                );
            } else {
                // Create new panel assignment
                $panelUser = PanelUser::create([
                    'user_id' => $user->id,
                    'panel_id' => $panelId,
                    'firm_id' => $firmId,
                ]);
                
                BatchItem::create([
                    'batch_id' => $batch->id,
                    'operation' => 'insert',
                    'model_type' => 'panel_user',
                    'model_id' => $panelUser->id,
                    'new_data' => json_encode([
                        'user_id' => $user->id,
                        'panel_id' => $panelId,
                        'firm_id' => $firmId
                    ])
                ]);
            }
            
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception(
                "Error processing panel assignment for employee ID {$employeeId}: " . $e->getMessage()
            );
        }
    }

    protected function refreshCaches()
    {
        $firmId = session('firm_id');
        Cache::forget("employee_panels_map_{$firmId}");
        Cache::forget("firm_panels_{$firmId}");
        Cache::forget("departments_with_employees_{$firmId}");
    }

    public function rollbackBatch($batchId)
    {
        try {
            DB::transaction(function () use ($batchId) {
                $batch = Batch::with('items')->findOrFail($batchId);

                // Remove all panel assignments
                $panelAssignmentItems = $batch->items()
                    ->where('model_type', 'panel_user')
                    ->get();

                foreach ($panelAssignmentItems as $item) {
                    $newData = json_decode($item->new_data, true);
                    if (isset($newData['user_id']) && isset($newData['panel_id']) && isset($newData['firm_id'])) {
                        PanelUser::where([
                            'user_id' => $newData['user_id'],
                            'panel_id' => $newData['panel_id'],
                            'firm_id' => $newData['firm_id']
                        ])->delete();
                    }
                }

                // Force delete the batch itself
                $batch->forceDelete();
            });

            // Refresh caches after rollback
            $this->refreshCaches();

            Flux::toast(
                heading: 'Success',
                text: 'Panel assignments rolled back and permanently deleted.',
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
            'panel_id' => null,
        ];
        $this->selectedEmployees = [];
        $this->selectedPanel = null;
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
            ->where('modulecomponent', 'panel_assignment')
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
        // Load batch items with employee and panel relationships
        $this->batchItems = BatchItem::where('batch_id', $batchId)
            ->where('model_type', 'panel_user')
            ->with(['panelUser.user.employee', 'panelUser.panel'])
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
                $batchItems = BatchItem::where('batch_id', $batchId)
                    ->where('model_type', 'panel_user')
                    ->whereHas('panelUser', function($q) use ($employeeId) {
                        $q->where('user_id', $employeeId);
                    })
                    ->get();
                    
                if ($batchItems->isEmpty()) {
                    throw new \Exception("No panel assignment items found for this employee.");
                }
                
                foreach ($batchItems as $item) {
                    // Remove the panel assignment
                    if ($item->panelUser) {
                        $item->panelUser->delete();
                    }
                    $item->forceDelete();
                }
                
                // Reload batch items to refresh the view
                $this->viewBatchItems($batchId);
                
                // If there are no more items in the batch, close the modal and potentially delete the batch
                if ($this->batchItems->isEmpty()) {
                    $this->showItemsModal = false;
                    $batch = Batch::find($batchId);
                    if ($batch && $batch->items()->count() === 0) {
                        $batch->forceDelete();
                    }
                    $this->selectedBatchId = null;
                }
                
                $employee = User::find($employeeId);
                $employeeName = $employee ? $employee->name : 'Employee';
                Flux::toast(
                    heading: 'Success',
                    text: 'Panel assignment for ' . $employeeName . ' rolled back successfully.',
                );
            });
            
            // Refresh caches and data after rollback
            $this->refreshCaches();
            $this->fetchEmployeePanelsMap();
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
        return view()->file(app_path('Livewire/Settings/RoleBasedAccess/blades/panel-bulk-assign.blade.php'));
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
