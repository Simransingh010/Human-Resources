<?php

namespace App\Livewire\Hrms\Payroll;

use App\Models\Batch;
use App\Models\BatchItem;
use App\Models\Hrms\Employee;
use App\Models\Settings\Department;
use App\Models\Hrms\SalaryTemplate;
use App\Models\Hrms\SalaryTemplatesComponent;
use App\Models\Hrms\SalaryComponentsEmployee;
use App\Services\BulkOperationService;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Flux;
use App\Models\Hrms\SalaryComponent;

class SalaryTemplateAllocations extends Component
{
    use WithPagination;

    public $perPage = 10;
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $showItemsModal = false;
    public $selectedBatchId = null;
    public $selectedEmployees = [];
    public $departmentsWithEmployees = [];
    public $filteredDepartmentsWithEmployees = [];
    public $selectedTemplate = null;
    public $employeeSearch = '';
    public $templateComponents = [];
    public $salaryCycles = [];
    public $selectedSalaryCycleId = null;

    // New properties for direct allocation
    public $allocationType = '';
    public $selectedComponents = [];
    public $availableComponents = [];
    public $effectiveFrom;
    public $effectiveTo;

    // New properties for batch items modal
    public $batchItems;
    public $batchItemSearch = '';
    public $effectiveFromFilter = '';
    public $effectiveToFilter = '';

    // Field configuration for form and table
    public array $fieldConfig = [
        'title' => ['label' => 'Batch Title', 'type' => 'text'],
        'modulecomponent' => ['label' => 'Module', 'type' => 'select', 'listKey' => 'modules'],
        'created_at' => ['label' => 'Created Date', 'type' => 'date'],
        'items_count' => ['label' => 'Items Count', 'type' => 'badge'],
    ];

    // Filter fields configuration
    public array $filterFields = [
        'title' => ['label' => 'Batch Title', 'type' => 'text'],
        'modulecomponent' => ['label' => 'Module', 'type' => 'select', 'listKey' => 'modules'],
    ];

    public array $listsForFields = [];
    public array $filters = [];
    public array $visibleFields = [];
    public array $visibleFilterFields = [];

    public $formData = [
        'template_id' => null,
    ];

    public function mount()
    {
        $this->batchItems = collect();
        $this->initListsForFields();
        $this->loadDepartmentsWithEmployees();
        $this->loadAvailableComponents();
        $this->loadSalaryCycles();

        // Set default effective from date
        $this->effectiveFrom = now()->format('Y-m-d');

        // Set default visible fields
        $this->visibleFields = ['title', 'modulecomponent', 'created_at',];
        $this->visibleFilterFields = ['title', 'modulecomponent'];

        // Initialize filters
        $this->filters = array_fill_keys(array_keys($this->filterFields), '');

        // Initialize filtered departments
        $this->filteredDepartmentsWithEmployees = $this->departmentsWithEmployees;
    }

    protected function initListsForFields(): void
    {
        $this->listsForFields['modules'] = [
            'salary_template_allocation' => 'Salary Template Allocation',
        ];

        // Get templates for dropdown
        $this->listsForFields['templates'] = SalaryTemplate::where('firm_id', Session::get('firm_id'))
            ->pluck('title', 'id')
            ->toArray();
    }

    public function updatedFormDataTemplateId($value)
    {
        if ($value) {
            $this->selectedTemplate = SalaryTemplate::with([
                'salary_templates_components.salary_component'
            ])
                ->find($value);

            if (!$this->selectedTemplate) {
                Flux::toast(
                    variant: 'error',
                    heading: 'Error',
                    text: 'Selected template not found.',
                );
                return;
            }

            // Set the salary cycle ID from the template
            $this->selectedSalaryCycleId = $this->selectedTemplate->salary_cycle_id;

            $this->templateComponents = $this->selectedTemplate->salary_templates_components;
            if ($this->templateComponents->isEmpty()) {
                Flux::toast(
                    variant: 'warning',
                    heading: 'Warning',
                    text: 'Selected template has no components configured.',
                );
                return;
            }

            // Set effective dates from template
            $this->effectiveFrom = $this->selectedTemplate->effective_from->format('Y-m-d');
            $this->effectiveTo = $this->selectedTemplate->effective_to ? $this->selectedTemplate->effective_to->format('Y-m-d') : null;
        } else {
            $this->selectedTemplate = null;
            $this->templateComponents = collect();
            $this->selectedSalaryCycleId = null;
            $this->effectiveFrom = now()->format('Y-m-d');
            $this->effectiveTo = null;
        }
    }

    protected function loadDepartmentsWithEmployees()
    {
        // Get all salary execution groups with their employees
        $executionGroups = \App\Models\Hrms\SalaryExecutionGroup::with([
            'employees' => function ($query) {
                $query->where('is_inactive', false)
                    ->whereNotExists(function ($subQuery) {
                        $subQuery->select(\DB::raw(1))
                            ->from('salary_components_employees')
                            ->whereRaw('salary_components_employees.employee_id = employees.id')
                            ->where('salary_components_employees.firm_id', Session::get('firm_id'))
                            ->whereNull('salary_components_employees.deleted_at');
                    });
            }
        ])
            ->where('firm_id', Session::get('firm_id'))
            ->where('is_inactive', false)
            ->get();

        $this->departmentsWithEmployees = $executionGroups->map(function ($group) {
            return [
                'id' => $group->id,
                'title' => $group->title,
                'employees' => $group->employees->map(function ($employee) {
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

        $filteredDepartments = $departments->map(function ($department) {
            $filteredEmployees = collect($department['employees'])->filter(function ($employee) {
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

    protected function loadAvailableComponents()
    {
        $this->availableComponents = SalaryComponent::where('firm_id', Session::get('firm_id'))
            ->orderBy('title')
            ->get()
            ->map(function ($component) {
                return [
                    'id' => $component->id,
                    'title' => $component->title,
                    'nature' => $component->nature,
                    'component_type' => $component->component_type,
                    'amount_type' => $component->amount_type,
                    'salary_component_group_id' => $component->salary_component_group_id,
                    'salary_component_group' => $component->salary_component_group ? [
                        'id' => $component->salary_component_group->id,
                        'title' => $component->salary_component_group->title
                    ] : null,
                    'taxable' => $component->taxable,
                    'calculation_json' => $component->calculation_json
                ];
            })
            ->toArray();
    }

    public function updatedAllocationType()
    {
        // Reset related fields when allocation type changes
        $this->formData['template_id'] = null;
        $this->selectedTemplate = null;
        $this->templateComponents = [];
        $this->selectedComponents = [];
        $this->effectiveFrom = now()->format('Y-m-d');
        $this->effectiveTo = null;
    }

    protected function rules()
    {
        $baseRules = [
            'allocationType' => 'required|in:template,direct',
            'selectedEmployees' => 'required|array|min:1',
            'selectedEmployees.*' => 'exists:employees,id',
        ];

        if ($this->allocationType === 'template') {
            return array_merge($baseRules, [
                'formData.template_id' => 'required|exists:salary_templates,id',
                'effectiveFrom' => 'required|date',
                'effectiveTo' => 'nullable|date'
            ]);
        } else {
            return array_merge($baseRules, [
                'selectedComponents' => 'required|array|min:1',
                'selectedComponents.*' => 'exists:salary_components,id',
                'selectedSalaryCycleId' => 'required|exists:salary_cycles,id',
                'effectiveFrom' => 'required|date',
                'effectiveTo' => 'required|date|after:effectiveFrom'
            ]);
        }
    }

    protected function validateExistingAllocations($employeeIds, $effectiveFrom, $effectiveTo, $componentIds = [])
    {
        $query = SalaryComponentsEmployee::whereIn('employee_id', $employeeIds)
            ->where('firm_id', Session::get('firm_id'))
            ->where(function ($query) use ($effectiveFrom, $effectiveTo) {
                $query->where(function ($q) use ($effectiveFrom, $effectiveTo) {
                    $q->where('effective_from', '<=', $effectiveTo)
                        ->where('effective_to', '>=', $effectiveFrom);
                });
            });

        if ($this->allocationType === 'direct' && !empty($componentIds)) {
            $query->whereIn('salary_component_id', $componentIds);
        }

        $existingAllocations = $query->get();

        if ($existingAllocations->isNotEmpty()) {
            $employeeIds = $existingAllocations->pluck('employee_id')->unique();
            $employees = Employee::whereIn('id', $employeeIds)->get()
                ->map(fn($emp) => $emp->fname . ' ' . $emp->lname)
                ->implode(', ');

            throw new \Exception("Cannot allocate: {$employees} have existing allocations that overlap with the new effective date range.");
        }
    }

    public function store()
    {
        try {
            $this->validate();

            if ($this->allocationType === 'template' && empty($this->templateComponents)) {
                throw new \Exception("Selected template has no components configured.");
            }

            if ($this->allocationType === 'direct' && empty($this->selectedComponents)) {
                throw new \Exception("No components selected for allocation.");
            }

            // Get the actual selected employee IDs and ensure they're integers
            $actualSelectedEmployeeIds = array_map('intval', $this->selectedEmployees);

            // Validate existing allocations
            $this->validateExistingAllocations(
                $actualSelectedEmployeeIds,
                $this->effectiveFrom,
                $this->effectiveTo ?? $this->selectedTemplate->effective_to,
                $this->allocationType === 'direct' ? $this->selectedComponents : []
            );

            DB::beginTransaction();
            try {
                // Start the batch operation
                $batchTitle = $this->allocationType === 'template'
                    ? "Salary Template Allocation - Template: {$this->selectedTemplate->title}"
                    : "Direct Component Allocation - Components: " . count($this->selectedComponents);

                $batch = BulkOperationService::start(
                    'salary_template_allocation',
                    'bulk_allocation',
                    $batchTitle
                );

                // Process each selected employee
                foreach ($actualSelectedEmployeeIds as $employeeId) {
                    if ($this->allocationType === 'template') {
                        foreach ($this->templateComponents as $component) {
                            $this->createEmployeeComponent($batch, $employeeId, $component);
                        }
                    } else {
                        // For direct allocation, create components in selection order
                        foreach ($this->selectedComponents as $sequence => $componentId) {
                            // Find component in the available components array
                            $component = collect($this->availableComponents)->firstWhere('id', $componentId);

                            if (!$component) {
                                throw new \Exception("Component with ID {$componentId} not found.");
                            }

                            $this->createDirectComponent($batch, $employeeId, $component, $sequence + 1);
                        }
                    }
                }

                DB::commit();
                $this->selectedBatchId = $batch->id;

                Flux::toast(
                    heading: 'Success',
                    text: 'Salary components allocated successfully.',
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
                text: 'Failed to allocate components: ' . $e->getMessage(),
            );
        }
    }

    protected function createEmployeeComponent($batch, $employeeId, $templateComponent)
    {
        // Get the salary component details
        $salaryComponent = $templateComponent->salary_component;

        $componentData = [
            'firm_id' => Session::get('firm_id'),
            'employee_id' => $employeeId,
            'salary_template_id' => $this->selectedTemplate->id,
            'salary_cycle_id' => $this->selectedTemplate->salary_cycle_id,
            'salary_component_id' => $templateComponent->salary_component_id,
            'salary_component_group_id' => $templateComponent->salary_component_group_id,
            'sequence' => $templateComponent->sequence,
            'nature' => $salaryComponent->nature,
            'component_type' => $salaryComponent->component_type,
            'amount_type' => $salaryComponent->amount_type,
            'amount' => 0, // Default amount, can be updated later
            'taxable' => $salaryComponent->taxable,
            'calculation_json' => $salaryComponent->calculation_json,
            'effective_from' => $this->selectedTemplate->effective_from,
            'effective_to' => $this->selectedTemplate->effective_to,
            'user_id' => Session::get('user_id'),
            'created_at' => now(),
            'updated_at' => now()
        ];

        $employeeComponent = SalaryComponentsEmployee::create($componentData);

        BatchItem::create([
            'batch_id' => $batch->id,
            'operation' => 'insert',
            'model_type' => SalaryComponentsEmployee::class,
            'model_id' => $employeeComponent->id,
            'new_data' => json_encode($componentData)
        ]);
    }

    protected function createDirectComponent($batch, $employeeId, $component, $sequence)
    {
        if (!isset($component['id'])) {
            throw new \Exception("Invalid component data structure.");
        }

        if (!$this->selectedSalaryCycleId) {
            throw new \Exception("Salary cycle must be selected for direct allocation.");
        }

        $componentData = [
            'firm_id' => Session::get('firm_id'),
            'employee_id' => $employeeId,
            'salary_cycle_id' => $this->selectedSalaryCycleId,
            'salary_component_id' => $component['id'],
            'salary_component_group_id' => $component['salary_component_group_id'],
            'sequence' => $sequence,
            'nature' => $component['nature'],
            'component_type' => $component['component_type'],
            'amount_type' => $component['amount_type'],
            'amount' => 0,
            'taxable' => $component['taxable'],
            'calculation_json' => $component['calculation_json'],
            'effective_from' => $this->effectiveFrom,
            'effective_to' => $this->effectiveTo,
            'user_id' => Session::get('user_id'),
            'created_at' => now(),
            'updated_at' => now()
        ];

        $employeeComponent = SalaryComponentsEmployee::create($componentData);

        BatchItem::create([
            'batch_id' => $batch->id,
            'operation' => 'insert',
            'model_type' => SalaryComponentsEmployee::class,
            'model_id' => $employeeComponent->id,
            'new_data' => json_encode($componentData)
        ]);
    }

    public function rollbackBatch($batchId)
    {
        try {
            DB::transaction(function () use ($batchId) {
                $batch = Batch::with('items')->findOrFail($batchId);

                // Hard delete all employee components
                $componentItems = $batch->items()
                    ->where('model_type', SalaryComponentsEmployee::class)
                    ->get();

                foreach ($componentItems as $item) {
                    if ($component = SalaryComponentsEmployee::find($item->model_id)) {
                        $component->forceDelete();
                    }
                }

                // Hard delete batch items
                $batch->items()->forceDelete();

                // Hard delete the batch itself
                $batch->forceDelete();
            });

            Flux::toast(
                heading: 'Success',
                text: 'Salary template allocation rolled back successfully.',
            );
        } catch (\Exception $e) {
            Flux::toast(
                variant: 'error',
                heading: 'Error',
                text: 'Failed to rollback: ' . $e->getMessage(),
            );
        }
    }

    public function resetForm()
    {
        $this->formData = [
            'template_id' => null,
        ];
        $this->selectedEmployees = [];
        $this->selectedTemplate = null;
        $this->templateComponents = [];
        $this->employeeSearch = '';
        $this->allocationType = '';
        $this->selectedComponents = [];
        $this->effectiveFrom = now()->format('Y-m-d');
        $this->effectiveTo = null;

        // Reset the filtered departments to show all employees
        $this->loadDepartmentsWithEmployees();
    }

    #[Computed]
    public function list()
    {
        return Batch::query()
            ->where('modulecomponent', 'salary_template_allocation')
            ->when($this->filters['title'] ?? null, fn($query, $value) =>
                $query->where('title', 'like', "%{$value}%"))
            ->withCount('items as items_count')
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);
    }

    public function showBatchItems($batchId)
    {
        try {
            $batch = Batch::with([
                'items' => function ($query) {
                    $query->orderBy('created_at', 'desc');
                }
            ])->findOrFail($batchId);

            $this->selectedBatchId = $batch->id;
            $this->showItemsModal = true;
        } catch (\Exception $e) {
            Flux::toast(
                variant: 'error',
                heading: 'Error',
                text: 'Failed to load batch items: ' . $e->getMessage(),
            );
        }
    }

    public function closeItemsModal()
    {
        $this->showItemsModal = false;
        $this->selectedBatchId = null;
    }

    public function viewDetails($batchId)
    {
        try {
            $this->selectedBatchId = $batchId;
            $this->clearBatchItemFilters();

            // Load batch items with their relationships
            $this->batchItems = BatchItem::where('batch_id', $batchId)
                ->where('model_type', SalaryComponentsEmployee::class)
                ->with(['salaryComponentEmployee.employee', 'salaryComponentEmployee.salary_component', 'salaryComponentEmployee.salary_template'])
                ->get();

            if ($this->batchItems->isEmpty()) {
                Flux::toast([
                    'variant' => 'warning',
                    'heading' => 'Warning',
                    'text' => 'No salary component allocations found in this batch.'
                ]);
            }

            $this->showItemsModal = true;

        } catch (\Exception $e) {
            Flux::toast([
                'variant' => 'error',
                'heading' => 'Error',
                'text' => 'Failed to load batch details: ' . $e->getMessage()
            ]);
        }
    }

    public function getFilteredBatchItemsProperty()
    {
        if (!$this->batchItems) {
            return collect();
        }

        return $this->batchItems
            ->when($this->batchItemSearch, function ($items) {
                return $items->filter(function ($item) {
                    $searchTerm = strtolower($this->batchItemSearch);
                    $employeeName = strtolower(
                        $item->salaryComponentEmployee->employee->fname . ' ' .
                        $item->salaryComponentEmployee->employee->lname
                    );
                    $componentName = strtolower($item->salaryComponentEmployee->salary_component->title);

                    return str_contains($employeeName, $searchTerm) ||
                        str_contains($componentName, $searchTerm);
                });
            })
            ->when($this->effectiveFromFilter, function ($items) {
                return $items->filter(function ($item) {
                    return Carbon::parse($item->salaryComponentEmployee->effective_from)
                        ->greaterThanOrEqual(Carbon::parse($this->effectiveFromFilter));
                });
            })
            ->when($this->effectiveToFilter, function ($items) {
                return $items->filter(function ($item) {
                    return Carbon::parse($item->salaryComponentEmployee->effective_to)
                        ->lessThanOrEqual(Carbon::parse($this->effectiveToFilter));
                });
            });
    }

    public function clearBatchItemFilters()
    {
        $this->batchItemSearch = '';
        $this->effectiveFromFilter = '';
        $this->effectiveToFilter = '';
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Payroll/blades/salary-template-allocations.blade.php'));
    }

    protected function loadSalaryCycles()
    {
        $this->salaryCycles = \App\Models\Hrms\SalaryCycle::where('firm_id', Session::get('firm_id'))
            ->where('is_inactive', false)
            ->get();
    }
}
