<?php

namespace App\Livewire\Hrms\Payroll;

use Livewire\Component;
use App\Models\Hrms\Employee;
use App\Models\Hrms\SalaryComponentsEmployee;
use Illuminate\Support\Facades\Session;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Cache;
use Flux;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use App\Models\Hrms\SalaryChangeEmployee;

class SalaryIncrement extends Component
{
    use WithPagination;

    public $tab = 'single';  // Default tab
    public $search = '';
    public $selectedEmployee = null;
    public $selectedEmployeeId = null;
    public $sqlQueries = [];
    
    // Sorting
    public $sortBy = 'sequence';
    public $sortDirection = 'asc';

    // Increment Modal Properties
    public $selectedComponentId = null;
    public $incrementType = 'fixed_amount';
    public $operation = 'increase';
    public $modificationValue = null;
    public $currentAmount = 0;
    public $calculatedFinalAmount = 0;
    public $salaryComponentsList = [];
    public $remarks = '';
    public ?\Illuminate\Support\Carbon $start_date = null;
    public ?\Illuminate\Support\Carbon $end_date = null;

    public $rule = [
        'type' => 'operation',
        'operator' => '+',
        'operands' => []
    ];

    public $operators = [
        '+' => 'Add',
        '-' => 'Subtract',
        '*' => 'Multiply',
        '/' => 'Divide',
        '>=' => 'Greater than or equal to',
        '<=' => 'Less than or equal to',
        '==' => 'Equal to',
        '!=' => 'Not equal to'
    ];

    public $functions = [
        'round' => 'Round',
        'max' => 'Maximum',
        'min' => 'Minimum',
        'ceil' => 'Ceiling',
        'floor' => 'Floor'
    ];

    public $availableTypes = [
        'conditional' => 'Conditional',
        'operation' => 'Operation',
        'function' => 'Function',
        'component' => 'Component',
        'constant' => 'Constant'
    ];

    public $assignComponentIds = [];
    public ?\Illuminate\Support\Carbon $assignEffectiveFrom = null;
    public ?\Illuminate\Support\Carbon $assignEffectiveTo = null;
    
    protected $queryString = ['tab'];

    // Add rules for validation
    protected $rules = [
        'modificationValue' => 'required|numeric|min:0',
        'incrementType' => 'required|in:fixed_amount,percentage,new_amount',
        'operation' => 'required|in:increase,decrease',
        'remarks' => 'nullable|string|min:3|max:500',
        'start_date' => 'required',
        'end_date' => 'nullable|after:start_date',
    ];

    // --- Bulk Filter Properties ---
    public $bulkFilter = [
        'name' => '',
        'department_id' => null,
        'execution_group_id' => null,
        'doh_from' => null,
        'doh_to' => null,
        'component_id' => null,
        'amount_min' => null,
        'amount_max' => null,
    ];
    public $allSalaryComponents = [];
    public $allDepartments = [];
    public $allExecutionGroups = [];
    // public $bulkEmployees = [];
    public $bulkComponentAmounts = [];
    public $selectedBulkComponentId = null;
    public $selectedBulkEmployeeIds = [];
    public $selectAllBulkEmployees = false;

    // --- Bulk Modal Properties ---
    public $bulk_start_date = null;
    public $bulk_end_date = null;
    public $bulk_incrementType = 'fixed_amount';
    public $bulk_operation = 'increase';
    public $bulk_modificationValue = null;
    public $bulk_remarks = '';
    public $bulk_rule = [
        'type' => 'operation',
        'operator' => '+',
        'operands' => []
    ];

    // --- Bulk Data Initialization ---
    public function mount()
    {
        $this->initializeSalaryComponentsList();
        $this->start_date = now();
        $this->fetchBulkFilterData();
        // Collect SQL queries for browser console
        
    }

    protected function fetchBulkFilterData()
    {
        // Fetch all salary components (id => title)
        $this->allSalaryComponents = \App\Models\Hrms\SalaryComponent::where('firm_id', Session::get('firm_id'))
            ->pluck('title', 'id')
            ->toArray();

        // Fetch all departments (id => title)
        $this->allDepartments = \App\Models\Settings\Department::where('firm_id', Session::get('firm_id'))
            ->pluck('title', 'id')
            ->toArray();
        // Fetch all execution groups (id => title)
        $this->allExecutionGroups = \App\Models\Hrms\SalaryExecutionGroup::where('firm_id', Session::get('firm_id'))
            ->pluck('title', 'id')
            ->toArray();
    }

    // --- Bulk Employee Filtering ---
    public function getBulkEmployeesProperty()
    {
        $query = Employee::query()
            ->where('firm_id', Session::get('firm_id'))
            ->with([
                'emp_job_profile.department',
                'emp_job_profile',
                'salary_execution_groups',
                'salary_components_employees' => function($q) {
                    $q->where(function($q2) {
                        $q2->whereNull('effective_to')->orWhere('effective_to', '>=', now());
                    });
                }
            ]);
//        dd([
//            'firm_id' => Session::get('firm_id'),
//            'filters' => $this->bulkFilter,
//            'query' => $query->toSql(),
//            'bindings' => $query->getBindings(),
//            'results' => $query->take(100)->get()
//        ]);

        // Check if all filters are empty
        $allFiltersEmpty = true;
        foreach ($this->bulkFilter as $key => $value) {
            if ($value !== null && $value !== '') {
                $allFiltersEmpty = false;
                break;
            }
        }

        if ($allFiltersEmpty) {
            // No filters applied, return first 100 employees
            return $query->take(100)->get();
        }


        // Only apply filters if they are set
        if (!empty($this->bulkFilter['name'])) {
            $query->where(function($q) {
                $q->where('fname', 'like', '%' . $this->bulkFilter['name'] . '%')
                  ->orWhere('lname', 'like', '%' . $this->bulkFilter['name'] . '%');
            });
        }
        if (!empty($this->bulkFilter['department_id'])) {
            $query->whereHas('emp_job_profile', function($q) {
                $q->where('department_id', $this->bulkFilter['department_id']);
            });
        }
        if (!empty($this->bulkFilter['execution_group_id'])) {
            $query->whereHas('salary_execution_groups', function($q) {
                $q->where('salary_execution_group_id', $this->bulkFilter['execution_group_id']);
            });
        }
        if (!empty($this->bulkFilter['doh_from'])) {
            $query->whereHas('emp_job_profile', function($q) {
                $q->whereDate('doh', '>=', $this->bulkFilter['doh_from']);
            });
        }
        if (!empty($this->bulkFilter['doh_to'])) {
            $query->whereHas('emp_job_profile', function($q) {
                $q->whereDate('doh', '<=', $this->bulkFilter['doh_to']);
            });
        }
        if (!empty($this->bulkFilter['component_id']) && ($this->bulkFilter['amount_min'] !== null || $this->bulkFilter['amount_max'] !== null)) {
            $query->whereHas('salary_components_employees', function($q) {
                $q->where('salary_component_id', $this->bulkFilter['component_id']);
                if ($this->bulkFilter['amount_min'] !== null) {
                    $q->where('amount', '>=', $this->bulkFilter['amount_min']);
                }
                if ($this->bulkFilter['amount_max'] !== null) {
                    $q->where('amount', '<=', $this->bulkFilter['amount_max']);
                }
            });
        }
        // Return filtered employees (up to 100)
        return $query->take(100)->get();
    }

    protected function initializeSalaryComponentsList()
    {
        $this->salaryComponentsList = SalaryComponentsEmployee::query()
            ->where('firm_id', Session::get('firm_id'))
            ->whereNull('effective_to')
            ->with('salary_component')
            ->get()
            ->mapWithKeys(function ($component) {
                return [
                    $component->id => [
                        'id' => $component->id,
                        'title' => $component->salary_component->title ?? 'Unknown Component'
                    ]
                ];
            })
            ->toArray();
    }

    // Reset properties when the modal is closed
    public function resetModificationForm()
    {
        $this->selectedComponentId = null;
        $this->incrementType = 'fixed_amount';
        $this->operation = 'increase';
        $this->modificationValue = null;
        $this->currentAmount = 0;
        $this->calculatedFinalAmount = 0;
        $this->remarks = '';
        $this->start_date = null;
        $this->end_date = null;
    }

    // Debounce the search to prevent too many database queries
    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function selectEmployee($employeeId)
    {
        $this->selectedEmployeeId = $employeeId;
        $this->selectedEmployee = Employee::with([
            'emp_job_profile.designation', 
            'emp_job_profile.department'
        ])->find($employeeId);
        $this->search = ''; // Clear search after selection
        
        // Reset sorting when selecting new employee
        $this->sortBy = 'sequence';
        $this->sortDirection = 'asc';
        $this->resetPage();
    }

    public function clearSelection()
    {
        $this->selectedEmployeeId = null;
        $this->selectedEmployee = null;
        $this->search = '';
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

    public function editComponent($componentId)
    {
        $component = SalaryComponentsEmployee::find($componentId);
        if (!$component) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Salary component not found!'
            ]);
            return;
        }
        
        // Set initial values when opening modal
        $this->selectedComponentId = $componentId;
        $this->currentAmount = $component->amount;
        $this->incrementType = 'fixed_amount';
        $this->operation = 'increase';
        $this->modificationValue = null;
        $this->calculatedFinalAmount = $component->amount;
        $this->start_date = now();
        $this->end_date = null;
        
        $this->modal("increment-{$componentId}")->show();
    }

    public function updatedModificationValue()
    {
        $this->calculateFinalAmount();
    }

    public function updatedIncrementType()
    {
        $this->modificationValue = null;
        $this->calculatedFinalAmount = $this->currentAmount;
    }

    public function updatedOperation()
    {
        $this->calculateFinalAmount();
    }

    protected function calculateFinalAmount()
    {
        if (is_null($this->modificationValue) || is_null($this->currentAmount)) {
            $this->calculatedFinalAmount = $this->currentAmount ?? 0;
            return;
        }
        

        switch ($this->incrementType) {
            case 'fixed_amount':
                $this->calculatedFinalAmount = $this->operation === 'increase' 
                    ? $this->currentAmount + $this->modificationValue 
                    : $this->currentAmount - $this->modificationValue;
                break;

            case 'percentage':
                $changeAmount = ($this->currentAmount * $this->modificationValue) / 100;
                $this->calculatedFinalAmount = $this->operation === 'increase'
                    ? $this->currentAmount + $changeAmount
                    : $this->currentAmount - $changeAmount;
                break;

            case 'new_amount':
                $this->calculatedFinalAmount = $this->modificationValue;
                break;

            default:
                $this->calculatedFinalAmount = $this->currentAmount;
        }
    }

    public function saveModification($componentId)
    {
        try {
            dd($this->start_date);
            $this->validate();

            $oldComponent = SalaryComponentsEmployee::where('firm_id', Session::get('firm_id'))
                ->where('id', $componentId)
                ->first();

            if (!$oldComponent) {
                throw new \Exception('Salary component not found.');
            }

            DB::beginTransaction();
            try {
                $oldComponent->effective_to = $this->start_date->copy()->subDay()->format('Y-m-d');
                $oldComponent->save();

                $newComponent = $oldComponent->replicate();
                $newComponent->amount = $this->calculatedFinalAmount;
                $newComponent->effective_from = $this->start_date->format('Y-m-d');
                $newComponent->effective_to = $this->end_date?->format('Y-m-d');
                $newComponent->save();

                $changeDetails = [
                    'change_type' => 'amount_modification',
                    'modification_type' => $this->incrementType,
                    'operation' => $this->operation,
                    'old_amount' => $oldComponent->amount,
                    'new_amount' => $this->calculatedFinalAmount,
                    'modification_value' => $this->modificationValue,
                    'old_effective_from' => $oldComponent->effective_from,
                    'old_effective_to' => $oldComponent->effective_to,
                    'new_effective_from' => $this->start_date->format('Y-m-d'),
                    'new_effective_to' => $this->end_date?->format('Y-m-d'),
                ];

                SalaryChangeEmployee::create([
                    'firm_id' => Session::get('firm_id'),
                    'employee_id' => $oldComponent->employee_id,
                    'old_salary_components_employee_id' => $oldComponent->id,
                    'new_salary_components_employee_id' => $newComponent->id,
                    'old_effective_to' => $oldComponent->effective_to,
                    'remarks' => $this->remarks,
                    'changes_details_json' => $changeDetails
                ]);

                DB::commit();

                $this->clearComponentCache();
                $this->resetModificationForm();
                $this->modal("increment-{$componentId}")->close();

                $this->dispatch('notify', [
                    'type' => 'success',
                    'message' => 'Salary component modified successfully.'
                ]);

                Flux::toast('Salary component modified successfully.', 'success');

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            $this->showErrorToast('Failed to modify salary component: ' . $e->getMessage());
        }
    }
    public function cancelModification()
    {
        // Close all modals and reset the form
        Flux::modals()->close();
        $this->resetModificationForm();
    }

    public function deleteComponent($componentId)
    {
        try {
            $component = SalaryComponentsEmployee::where('firm_id', Session::get('firm_id'))
                ->where('id', $componentId)
                ->first();

            if (!$component) {
                throw new \Exception('Salary component not found.');
            }

            // Set effective_to to current date instead of deleting
            $component->effective_to = now();
            $component->save();

            // Clear the cache for this employee's components
            $this->clearComponentCache();

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Salary component deactivated successfully.'
            ]);

        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Failed to deactivate salary component: ' . $e->getMessage()
            ]);
        }
    }

    protected function clearComponentCache()
    {
        if ($this->selectedEmployeeId) {
            $cacheKey = "salary_components_{$this->selectedEmployeeId}_{$this->sortBy}_{$this->sortDirection}";
            Cache::forget($cacheKey);
        }
    }

    protected function getEmployees()
    {
        if (empty($this->search)) {
            return collect();
        }

        return Employee::query()
            ->where('firm_id', Session::get('firm_id'))
            ->where('is_inactive', false)
            ->where(function ($query) {
                $query->where('fname', 'like', '%' . $this->search . '%')
                    ->orWhere('lname', 'like', '%' . $this->search . '%')
                    ->orWhereHas('emp_job_profile', function ($query) {
                        $query->where('employee_code', 'like', '%' . $this->search . '%');
                    });
            })
            ->with(['emp_job_profile.designation'])
            ->take(10) // Limit results for performance
            ->get();
    }

    #[Computed]
    public function salaryComponents()
    {
        if (!$this->selectedEmployeeId) {
            return collect();
        }

        // Cache key includes sorting parameters and employee ID
        $cacheKey = "salary_components_{$this->selectedEmployeeId}_{$this->sortBy}_{$this->sortDirection}";

        return Cache::remember($cacheKey, now()->addMinutes(5), function () {
            $today = now()->startOfDay();
            return SalaryComponentsEmployee::query()
                ->where('firm_id', Session::get('firm_id'))
                ->where('employee_id', $this->selectedEmployeeId)
                ->where(function($query) use ($today) {
                    $query->whereNull('effective_to')
                        ->orWhere('effective_to', '>=', $today);
                })
                ->whereIn('amount_type', ['static_known', 'calculated_known']) // Filter only known components
                ->with(['salary_component', 'salary_component_group'])
                ->orderBy($this->sortBy, $this->sortDirection)
                ->get()
                ->map(function ($component) {
                    return [
                        'id' => $component->id,
                        'title' => $component->salary_component->title ?? 'Unknown Component',
                        'group' => $component->salary_component_group?->title,
                        'amount' => $component->amount,
                        'nature' => $component->nature,
                        'component_type' => $component->component_type,
                        'amount_type' => $component->amount_type,
                        'sequence' => $component->sequence,
                        'effective_from' => $component->effective_from?->format('d M Y'),
                        'effective_to' => $component->effective_to?->format('d M Y'),
                    ];
                });
        });
    }

    public function render()
    {
        $employees = $this->getEmployees();
        
        return view()->file(app_path('Livewire/Hrms/Payroll/blades/salary-increment.blade.php'), [
            'employees' => $employees,
            'salaryComponents' => $this->salaryComponents(),
            'bulkEmployees' => $this->getBulkEmployeesProperty(),
            'sqlQueries' => $this->sqlQueries,
        ]);
    }

    protected function resetRule()
    {
        $this->rule = [
            'type' => 'operation',
            'operator' => '+',
            'operands' => []
        ];
    }

    public function openCalculationRule($componentId)
    {
        try {
            $component = SalaryComponentsEmployee::with('salary_component')
                ->where('id', $componentId)
                ->where('firm_id', Session::get('firm_id'))
                ->first();

            if (!$component) {
                throw new \Exception('Salary component not found.');
            }

            // Reset the rule before opening the modal
            $this->resetRule();
            
            // Refresh the salary components list before opening the modal
            $this->initializeSalaryComponentsList();

            if ($component->salary_component->calculation_json) {
                $this->rule = $component->salary_component->calculation_json;
            }

            $this->selectedComponentId = $componentId;
            $this->start_date = now();
            $this->end_date = null;
            $this->modal('calculation-rule-modal')->show();
        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Failed to open calculation rule: ' . $e->getMessage()
            ]);
        }
    }

   
    public function saveRule()
    {
        try {
            $this->validate([
                'remarks' => 'nullable|string|min:3|max:500',
                'start_date' => 'required',
                'end_date' => 'nullable|after:start_date',
            ]);

            if (!$this->selectedComponentId) {
                throw new \Exception('No component selected.');
            }

            $oldComponent = SalaryComponentsEmployee::with('salary_component')
                ->where('id', $this->selectedComponentId)
                ->where('firm_id', Session::get('firm_id'))
                ->first();

            if (!$oldComponent) {
                throw new \Exception('Salary component not found.');
            }

            DB::beginTransaction();
            try {
                $oldComponent->effective_to = $this->start_date->copy()->subDay()->format('Y-m-d');
                $oldComponent->save();

                $newComponent = $oldComponent->replicate();
                $newComponent->effective_from = $this->start_date->format('Y-m-d');
                $newComponent->effective_to = $this->end_date?->format('Y-m-d');
                $newComponent->save();

                $oldComponent->salary_component->update([
                    'calculation_json' => $this->rule
                ]);

                $changeDetails = [
                    'change_type' => 'calculation_rule_modification',
                    'old_calculation_json' => $oldComponent->salary_component->calculation_json,
                    'new_calculation_json' => $this->rule,
                    'old_effective_from' => $oldComponent->effective_from,
                    'old_effective_to' => $oldComponent->effective_to,
                    'new_effective_from' => $this->start_date->format('Y-m-d'),
                    'new_effective_to' => $this->end_date?->format('Y-m-d'),
                ];

                SalaryChangeEmployee::create([
                    'firm_id' => Session::get('firm_id'),
                    'employee_id' => $oldComponent->employee_id,
                    'old_salary_components_employee_id' => $oldComponent->id,
                    'new_salary_components_employee_id' => $newComponent->id,
                    'old_effective_to' => $oldComponent->effective_to,
                    'remarks' => $this->remarks,
                    'changes_details_json' => $changeDetails
                ]);

                DB::commit();

                $this->modal('calculation-rule-modal')->close();
                $this->resetRule();
                $this->selectedComponentId = null;
                $this->remarks = '';
                $this->start_date = null;
                $this->end_date = null;

                $this->dispatch('notify', [
                    'type' => 'success',
                    'message' => 'Calculation rule updated successfully.'
                ]);

                Flux::toast('Calculation rule updated successfully.', 'success');

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            $this->showErrorToast('Failed to save calculation rule: ' . $e->getMessage());
        }
    }

    private function showErrorToast($message)
    {
        Flux::toast($message, 'error');

        $this->dispatch('notify', [
            'type' => 'error',
            'message' => $message
        ]);
    }

    public function addOperand()
    {
        if (!isset($this->rule['operands'])) {
            $this->rule['operands'] = [];
        }

        $this->rule['operands'][] = [
            'type' => 'component',
            'key' => null
        ];
    }

    public function removeOperand($index)
    {
        if (isset($this->rule['operands'])) {
            array_splice($this->rule['operands'], $index, 1);
        }
    }

    public function updatedRuleType()
    {
        switch ($this->rule['type']) {
            case 'conditional':
                $this->rule = [
                    'type' => 'conditional',
                    'if' => [
                        'left' => ['type' => 'component', 'key' => null],
                        'operator' => '==',
                        'right' => ['type' => 'constant', 'value' => 0]
                    ],
                    'then' => ['type' => 'constant', 'value' => 0],
                    'else' => ['type' => 'constant', 'value' => 0]
                ];
                break;
            case 'operation':
                $this->rule = [
                    'type' => 'operation',
                    'operator' => '+',
                    'operands' => []
                ];
                break;
            case 'component':
                $this->rule = [
                    'type' => 'component',
                    'key' => null
                ];
                break;
            case 'constant':
                $this->rule = [
                    'type' => 'constant',
                    'value' => 0
                ];
                break;
        }
    }

    protected function getComponentTitle($component)
    {
        if (is_array($component)) {
            return $component['title'] ?? '';
        }

        if (is_object($component)) {
            if (method_exists($component, 'toArray')) {
                $array = $component->toArray();
                return $array['title'] ?? '';
            }
            return $component->title ?? '';
        }

        return '';
    }

    protected function validateDateRange()
    {
        // Check for overlapping date ranges
        if ($this->selectedEmployeeId && $this->start_date) {
            $existingComponent = SalaryComponentsEmployee::where('employee_id', $this->selectedEmployeeId)
                ->where('id', '!=', $this->selectedComponentId)
                ->where(function ($query) {
                    $query->where(function ($q) {
                        $q->where('effective_from', '<=', $this->start_date)
                            ->where(function ($q2) {
                                $q2->where('effective_to', '>=', $this->start_date)
                                    ->orWhereNull('effective_to');
                            });
                    })->orWhere(function ($q) {
                        if ($this->end_date) {
                            $q->where('effective_from', '<=', $this->end_date)
                                ->where(function ($q2) {
                                    $q2->where('effective_to', '>=', $this->end_date)
                                        ->orWhereNull('effective_to');
                                });
                        }
                    });
                })
                ->exists();

            if ($existingComponent) {
                throw new \Exception('The selected date range overlaps with an existing salary component.');
            }
        }
    }

    public function updatedStartDate()
    {
        try {
            $this->validateDateRange();
        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => $e->getMessage()
            ]);
            $this->start_date = null;
        }
    }

    public function updatedEndDate()
    {
        try {
            $this->validateDateRange();
        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => $e->getMessage()
            ]);
            $this->end_date = null;
        }
    }

    public function updatedBulkFilter()
    {
        $this->selectedBulkEmployeeIds = [];
        $this->selectAllBulkEmployees = false;
        $this->selectedBulkComponentId = null; // Reset component selection on filter change
    }

    public function getAvailableComponentsForAssignmentProperty()
    {
        if (!$this->selectedEmployeeId) {
            return [];
        }
        // Get all components for the firm
        $allComponents = \App\Models\Hrms\SalaryComponent::where('firm_id', Session::get('firm_id'))->get();
        // Get already assigned component IDs
        $assignedIds = SalaryComponentsEmployee::where('employee_id', $this->selectedEmployeeId)
            ->where(function($q) {
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', now());
            })
            ->pluck('salary_component_id')
            ->toArray();
        // Return only unassigned components
        return $allComponents->whereNotIn('id', $assignedIds)->map(function($c) {
            return [
                'id' => $c->id,
                'title' => $c->title
            ];
        })->values()->all();
    }

    public function assignNewComponents()
    {
        $this->validate([
            'assignComponentIds' => 'required|array|min:1',
            'assignComponentIds.*' => 'exists:salary_components,id',
            'assignEffectiveFrom' => 'required',
            'assignEffectiveTo' => 'nullable|after:assignEffectiveFrom',
        ]);
        if (!$this->selectedEmployeeId) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'No employee selected.'
            ]);
            return;
        }
        DB::beginTransaction();
        try {
            foreach ($this->assignComponentIds as $componentId) {
                // Double check not already assigned
                $alreadyAssigned = SalaryComponentsEmployee::where('employee_id', $this->selectedEmployeeId)
                    ->where('salary_component_id', $componentId)
                    ->where(function($q) {
                        $q->whereNull('effective_to')->orWhere('effective_to', '>=', now());
                    })
                    ->exists();
                if ($alreadyAssigned) continue;
                $component = \App\Models\Hrms\SalaryComponent::find($componentId);
                if (!$component) continue;
                $newComponent = SalaryComponentsEmployee::create([
                    'firm_id' => Session::get('firm_id'),
                    'employee_id' => $this->selectedEmployeeId,
                    'salary_template_id' => null,
                    'salary_component_group_id' => $component->salary_component_group_id,
                    'salary_component_id' => $component->id,
                    'sequence' => 0,
                    'nature' => $component->nature,
                    'component_type' => $component->component_type,
                    'amount_type' => $component->amount_type,
                    'amount' => 0,
                    'taxable' => $component->taxable,
                    'calculation_json' => $component->calculation_json,
                    'effective_from' => $this->assignEffectiveFrom?->format('Y-m-d'),
                    'effective_to' => $this->assignEffectiveTo?->format('Y-m-d'),
                    'user_id' => Session::get('user_id'),
                ]);
                // Log assignment
                $changeDetails = [
                    'change_type' => 'direct_assignment',
                    'component_id' => $component->id,
                    'component_title' => $component->title,
                    'assigned_effective_from' => $this->assignEffectiveFrom?->format('Y-m-d'),
                    'assigned_effective_to' => $this->assignEffectiveTo?->format('Y-m-d'),
                ];
                SalaryChangeEmployee::create([
                    'firm_id' => Session::get('firm_id'),
                    'employee_id' => $this->selectedEmployeeId,
                    'old_salary_components_employee_id' => null,
                    'new_salary_components_employee_id' => $newComponent->id,
                    'old_effective_to' => null,
                    'remarks' => 'Direct assignment',
                    'changes_details_json' => $changeDetails
                ]);
            }
            DB::commit();
            $this->assignComponentIds = [];
            $this->assignEffectiveFrom = null;
            $this->assignEffectiveTo = null;
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Components assigned successfully.'
            ]);
            $this->modal('assign-components-modal')->close();
            $this->initializeSalaryComponentsList();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Failed to assign components: ' . $e->getMessage()
            ]);
        }
    }

    // Returns array of salary components (id => title) common to all filtered employees
    public function getCommonSalaryComponentsProperty()
    {
        $employees = $this->getBulkEmployeesProperty();
        if ($employees->isEmpty()) {
            return [];
        }
        // Get all salary component IDs for each employee
        $allComponentIds = $employees->map(function($employee) {
            return $employee->salary_components_employees->pluck('salary_component_id')->unique();
        });
        // Find intersection (common IDs)
        $commonIds = $allComponentIds->reduce(function($carry, $item) {
            return is_null($carry) ? $item : $carry->intersect($item);
        });
        if (!$commonIds || $commonIds->isEmpty()) {
            return [];
        }
        // Get titles for these IDs (from SalaryComponent model)
        $components = \App\Models\Hrms\SalaryComponent::whereIn('id', $commonIds)->pluck('title', 'id')->toArray();
        return $components;
    }

    public function toggleSelectAllBulkEmployees()
    {
        $employees = $this->getBulkEmployeesProperty();
        if (count($this->selectedBulkEmployeeIds) === $employees->count()) {
            $this->selectedBulkEmployeeIds = [];
            $this->selectAllBulkEmployees = false;
        } else {
            $this->selectedBulkEmployeeIds = $employees->pluck('id')->map(fn($id) => (string)$id)->toArray();
            $this->selectAllBulkEmployees = true;
        }
    }

    /**
     * Open the correct bulk modal based on the selected component type
     */
    public function openBulkUpdateModal()
    {
        if (!$this->selectedBulkComponentId || empty($this->selectedBulkEmployeeIds)) {
            $message = '';
            if (!$this->selectedBulkComponentId && empty($this->selectedBulkEmployeeIds)) {
                $message = 'Please select a component and at least one employee for bulk update.';
            } elseif (!$this->selectedBulkComponentId) {
                $message = 'Please select a component for bulk update.';
            } else {
                $message = 'Please select at least one employee for bulk update.';
            }
            
            Flux::toast($message, 'Error');
            return;
        }
        // Get the selected component's type
        $component = \App\Models\Hrms\SalaryComponent::find($this->selectedBulkComponentId);
        if (!$component) {
            Flux::toast('Component not found.', 'error');
            return;
        }
        if ($component->amount_type === 'static_known') {
            // Reset modal fields
            $this->bulk_incrementType = 'fixed_amount';
            $this->bulk_operation = 'increase';
            $this->bulk_modificationValue = null;
            $this->bulk_remarks = '';
            $this->bulk_start_date = now();
            $this->bulk_end_date = null;
            $this->modal('bulk-increment-modal')->show();
        } elseif ($component->amount_type === 'calculated_known') {
            // Try to get the formula from the first selected employee's assignment
            $firstEmployeeId = $this->selectedBulkEmployeeIds[0] ?? null;
            $empComponent = null;
            if ($firstEmployeeId) {
                $empComponent = \App\Models\Hrms\SalaryComponentsEmployee::where('employee_id', $firstEmployeeId)
                    ->where('salary_component_id', $component->id)
                    ->orderByDesc('effective_from')
                    ->first();
            }
            $this->bulk_rule = $empComponent && $empComponent->calculation_json
                ? $empComponent->calculation_json
                : ($component->calculation_json ?: [
                    'type' => 'operation',
                    'operator' => '+',
                    'operands' => []
                ]);
            $this->bulk_remarks = '';
            $this->bulk_start_date = now();
            $this->bulk_end_date = null;
            $this->modal('bulk-calculation-rule-modal')->show();
        } else {
            Flux::toast('Bulk update is only supported for static or calculated components.', 'error');
        }
    }

    /**
     * Save bulk increment/decrement for selected employees and component
     */
    public function saveBulkModification()
    {
        $this->validate([
            'bulk_modificationValue' => 'required|numeric|min:0.01',
            'bulk_incrementType' => 'required|in:fixed_amount,percentage,new_amount',
            'bulk_operation' => 'required|in:increase,decrease',
            'bulk_start_date' => 'required',
            'bulk_end_date' => 'nullable|after:bulk_start_date',
            'bulk_remarks' => 'nullable|string|min:3|max:500',
            'selectedBulkComponentId' => 'required|integer',
            'selectedBulkEmployeeIds' => 'required|array|min:1',
        ]);

        $componentId = $this->selectedBulkComponentId;
        $employeeIds = $this->selectedBulkEmployeeIds;
        $startDate = $this->bulk_start_date;
        $endDate = $this->bulk_end_date;
        $incrementType = $this->bulk_incrementType;
        $operation = $this->bulk_operation;
        $modificationValue = $this->bulk_modificationValue;
        $remarks = $this->bulk_remarks;

        $batch = null;
        \DB::beginTransaction();
        try {
            $batch = \App\Services\BulkOperationService::start('SalaryComponentsEmployee', 'bulk_increment', 'Bulk Salary Increment');
            foreach ($employeeIds as $employeeId) {
                $oldComponent = \App\Models\Hrms\SalaryComponentsEmployee::where('firm_id', session('firm_id'))
                    ->where('employee_id', $employeeId)
                    ->where('salary_component_id', $componentId)
                    ->where(function($q) use ($startDate) {
                        $q->whereNull('effective_to')->orWhere('effective_to', '>=', $startDate);
                    })
                    ->orderByDesc('effective_from')
                    ->first();
                if (!$oldComponent) {
                    // Optionally log or skip
                    continue;
                }
                $original = $oldComponent->getOriginal();
                // Set the current component's effective_to to the day before new start date
                $oldComponent->effective_to = $startDate->copy()->subDay()->format('Y-m-d');
                $oldComponent->save();
                \App\Services\BulkOperationService::logUpdate($batch, $oldComponent, $original);

                // Calculate new amount
                $currentAmount = $oldComponent->amount;
                $newAmount = $currentAmount;
                switch ($incrementType) {
                    case 'fixed_amount':
                        $newAmount = $operation === 'increase'
                            ? $currentAmount + $modificationValue
                            : $currentAmount - $modificationValue;
                        break;
                    case 'percentage':
                        $changeAmount = ($currentAmount * $modificationValue) / 100;
                        $newAmount = $operation === 'increase'
                            ? $currentAmount + $changeAmount
                            : $currentAmount - $changeAmount;
                        break;
                    case 'new_amount':
                        $newAmount = $modificationValue;
                        break;
                }

                // Create a new component with updated amount
                $newComponent = $oldComponent->replicate();
                $newComponent->amount = $newAmount;
                $newComponent->effective_from = $startDate->format('Y-m-d');
                $newComponent->effective_to = $endDate?->format('Y-m-d');
                $newComponent->save();
                \App\Services\BulkOperationService::logInsert($batch, $newComponent);

                // Log the salary change
                $changeDetails = [
                    'change_type' => 'amount_modification',
                    'modification_type' => $incrementType,
                    'operation' => $operation,
                    'old_amount' => $currentAmount,
                    'new_amount' => $newAmount,
                    'modification_value' => $modificationValue,
                    'old_effective_from' => $oldComponent->effective_from,
                    'old_effective_to' => $oldComponent->effective_to,
                    'new_effective_from' => $startDate->format('Y-m-d'),
                    'new_effective_to' => $endDate?->format('Y-m-d'),
                ];
                \App\Models\Hrms\SalaryChangeEmployee::create([
                    'firm_id' => session('firm_id'),
                    'employee_id' => $employeeId,
                    'old_salary_components_employee_id' => $oldComponent->id,
                    'new_salary_components_employee_id' => $newComponent->id,
                    'old_effective_to' => $oldComponent->effective_to,
                    'remarks' => $remarks,
                    'changes_details_json' => $changeDetails
                ]);
            }
            \DB::commit();
            $this->bulk_modificationValue = null;
            $this->bulk_remarks = '';
            $this->bulk_start_date = now();
            $this->bulk_end_date = null;
            $this->selectedBulkEmployeeIds = [];
            $this->selectAllBulkEmployees = false;
            $this->modal('bulk-increment-modal')->close();
            \Flux\Flux::toast('Bulk salary increment applied successfully.', 'success');
        } catch (\Exception $e) {
            \DB::rollBack();
            \Flux\Flux::toast('Bulk salary increment failed: ' . $e->getMessage(), 'error');
        }
    }
}
