<?php

namespace App\Livewire\Hrms\Payroll;

use App\Models\Hrms\Employee;
use App\Models\Hrms\SalaryComponent;
use App\Models\Settings\Department;
use App\Models\Settings\Designation;
use App\Models\Hrms\SalaryComponentsEmployee;
use App\Models\Hrms\EmployeeTaxRegime;
use App\Models\Hrms\PayrollComponentsEmployeesTrack;
use App\Models\Hrms\EmployeesSalaryExecutionGroup;
use App\Models\Hrms\PayrollSlot;
use App\Models\Hrms\SalaryExecutionGroup;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Cache;
use Flux;

class BulkEmployeeSalaryComponents extends Component
{
    use WithPagination;

    public $perPage = 10;
    public $sortBy = 'fname';
    public $sortDirection = 'asc';
    public $selectedEmployees = [];
    public $components = [];
    public $componentAmounts = [];
    public $employees = [];
    public $employeeComponents = [];
    public array $bulkupdate = [];

    // Salary Slip Modal Properties
    public $showSalarySlipModal = false;
    public $selectedEmployee = null;
    public $salaryComponents = [];
    public $totalEarnings = 0;
    public $totalDeductions = 0;
    public $netSalary = 0;
    public $netSalaryInWords = '';

    // Filter fields configuration
    public array $filterFields = [
        'department_id' => ['label' => 'Department', 'type' => 'select', 'listKey' => 'departments'],
        'designation_id' => ['label' => 'Designation', 'type' => 'select', 'listKey' => 'designations'],
        'salary_execution_group_id' => ['label' => 'Salary Execution Group', 'type' => 'select', 'listKey' => 'executionGroups'],
        'search' => ['label' => 'Search', 'type' => 'text'],
    ];

    public array $listsForFields = [];
    public array $filters = [];
    public array $visibleFilterFields = [];

    public function mount()
    {
        $this->initListsForFields();
        $this->loadComponents();

        // Set default visible filter fields
        $this->visibleFilterFields = ['department_id', 'designation_id', 'salary_execution_group_id', 'search'];

        // Initialize filters
        $this->filters = array_fill_keys(array_keys($this->filterFields), '');
    }

    protected function initListsForFields(): void
    {
        // Get departments for dropdown
        $this->listsForFields['departments'] = Department::where('firm_id', Session::get('firm_id'))
            ->where('is_inactive', false)
            ->pluck('title', 'id')
            ->toArray();

        // Get designations for dropdown
        $this->listsForFields['designations'] = Designation::where('firm_id', Session::get('firm_id'))
            ->where('is_inactive', false)
            ->pluck('title', 'id')
            ->toArray();

        // Get salary execution groups for dropdown
        $this->listsForFields['executionGroups'] = SalaryExecutionGroup::where('firm_id', Session::get('firm_id'))
            ->where('is_inactive', false)
            ->pluck('title', 'id')
            ->toArray();
    }

    protected function loadComponents()
    {
        $this->components = SalaryComponent::where('firm_id', Session::get('firm_id'))
            ->get()
            ->filter(function ($component) {
                // Only include components with static_known or static_unknown amount_type
                return in_array($component->amount_type, ['static_known', ]);
            })
            ->sortBy('title')
            ->map(function ($component) {
                return [
                    'id' => $component->id,
                    'title' => $component->title,
                    'nature' => $component->nature,
                    'component_type' => $component->component_type,
                    'amount_type' => $component->amount_type,
                    'is_calculated' => str_contains($component->amount_type, 'calculated_'),
                ];
            })
            ->toArray();
    }

    protected function loadEmployeeComponents($employeeIds)
    {


        $this->employeeComponents = SalaryComponentsEmployee::whereIn('employee_id', $employeeIds)
            ->where('firm_id', Session::get('firm_id'))
            ->where(function ($query) {
                $query->whereNull('effective_to')
                    ->orWhere('effective_to', '>', now());
            })
            ->get()
            ->map(function ($component) {
                // Initialize bulkupdate with current values
                $this->bulkupdate[$component->employee_id][$component->salary_component_id] = $component->amount;

                return [
                    'employee_id' => $component->employee_id,
                    'component_id' => $component->salary_component_id,
                    'amount' => $component->amount,
                    'amount_type' => $component->amount_type,
                    'nature' => $component->nature
                ];
            })
            ->groupBy('employee_id')
            ->toArray();
    }

    public function isComponentActive($employeeId, $componentId): bool
    {
        return isset($this->employeeComponents[$employeeId]) &&
            collect($this->employeeComponents[$employeeId])->contains('salary_component_id', $componentId);
    }

    // Simple helper methods for template
    public function getBadgeVariant($active): string
    {
        return $active ? 'success' : 'secondary';
    }

    public function getButtonVariant($active): string
    {
        return $active ? 'primary' : 'secondary';
    }

    public function getStatusText($active): string
    {
        return $active ? 'Active' : 'Inactive';
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

    public function toggleComponent($employeeId, $componentId)
    {
        try {
            $employee = Employee::findOrFail($employeeId);
            $component = collect($this->components)->firstWhere('id', $componentId);

            if (!$component) {
                throw new \Exception('Component not found');
            }

            // Check if component exists for employee
            $existingComponent = SalaryComponentsEmployee::where('employee_id', $employeeId)
                ->where('salary_component_id', $componentId)
                ->whereNull('effective_to')
                ->first();

            if ($existingComponent) {
                // Deactivate component
                $existingComponent->update(['effective_to' => now()]);

                Flux::toast(
                    variant: 'success',
                    heading: 'Component Deactivated',
                    text: "Component {$component['title']} has been deactivated for {$employee->fname}",
                );
            } else {
                // Activate component
                SalaryComponentsEmployee::create([
                    'firm_id' => Session::get('firm_id'),
                    'employee_id' => $employeeId,
                    'salary_component_id' => $componentId,
                    'sequence' => 0,
                    'nature' => $component['nature'],
                    'component_type' => $component['component_type'],
                    'amount_type' => $component['amount_type'],
                    'amount' => 0,
                    'taxable' => false,
                    'effective_from' => now(),
                    'user_id' => Session::get('user_id'),
                ]);

                Flux::toast(
                    variant: 'success',
                    heading: 'Component Activated',
                    text: "Component {$component['title']} has been activated for {$employee->fname}",
                );
            }

            // Clear cache for this employee
            Cache::forget('employee_components_' . $employeeId . '_' . Session::get('firm_id'));

            // Reload employee components
            $this->loadEmployeeComponents([$employeeId]);

        } catch (\Exception $e) {
            Flux::toast(
                variant: 'error',
                heading: 'Error',
                text: $e->getMessage(),
            );
        }
    }

    #[Computed]
    public function list()
    {
        $query = SalaryComponentsEmployee::query()
            ->select([
                'salary_components_employees.employee_id',
                'employees.fname',
                'employees.mname',
                'employees.lname',
                'employees.email',
                'employees.phone',
                'employees.gender',
                'employee_job_profiles.employee_code',
                'employee_job_profiles.department_id',
                'employee_job_profiles.designation_id',
                'departments.title as department_title',
                'designations.title as designation_title'
            ])
            ->join('employees', 'employees.id', '=', 'salary_components_employees.employee_id')
            ->join('employee_job_profiles', 'employees.id', '=', 'employee_job_profiles.employee_id')
            ->leftJoin('departments', 'employee_job_profiles.department_id', '=', 'departments.id')
            ->leftJoin('designations', 'employee_job_profiles.designation_id', '=', 'designations.id')
            ->where('salary_components_employees.firm_id', Session::get('firm_id'))
            ->where(function ($query) {
                $query->whereNull('salary_components_employees.effective_to')
                    ->orWhere('salary_components_employees.effective_to', '>', now());
            })
            ->where('employees.is_inactive', false)
            ->when($this->filters['department_id'], fn($query, $value) =>
                $query->where('employee_job_profiles.department_id', $value))
            ->when($this->filters['designation_id'], fn($query, $value) =>
                $query->where('employee_job_profiles.designation_id', $value))
            ->when($this->filters['salary_execution_group_id'], function($query, $value) {
                $query->whereExists(function ($subquery) use ($value) {
                    $subquery->select('id')
                        ->from('employees_salary_execution_group')
                        ->whereColumn('employee_id', 'employees.id')
                        ->where('salary_execution_group_id', $value)
                        ->where('firm_id', Session::get('firm_id'));
                });
            })
            ->when($this->filters['search'], fn($query, $value) =>
                $query->where(function ($q) use ($value) {
                    $q->where('employees.fname', 'like', "%{$value}%")
                        ->orWhere('employees.lname', 'like', "%{$value}%")
                        ->orWhere('employees.email', 'like', "%{$value}%")
                        ->orWhere('employees.phone', 'like', "%{$value}%")
                        ->orWhere('employee_job_profiles.employee_code', 'like', "%{$value}%");
                }))
            ->groupBy([
                'salary_components_employees.employee_id',
                'employees.fname',
                'employees.mname',
                'employees.lname',
                'employees.email',
                'employees.phone',
                'employees.gender',
                'employee_job_profiles.employee_code',
                'employee_job_profiles.department_id',
                'employee_job_profiles.designation_id',
                'departments.title',
                'designations.title'
            ])
            ->orderBy($this->sortBy, $this->sortDirection);

        $employees = $query->paginate($this->perPage);

        // Load components for displayed employees
        $this->loadEmployeeComponents($employees->pluck('employee_id')->toArray());

        return $employees;
    }

    public function updateComponentAmount($employeeId, $componentId)
    {
        try {
            $amount = $this->bulkupdate[$employeeId][$componentId] ?? 0;

            $component = SalaryComponentsEmployee::where('employee_id', $employeeId)
                ->where('salary_component_id', $componentId)
                ->where('firm_id', Session::get('firm_id'))
                ->where(function ($query) {
                    $query->whereNull('effective_to')
                        ->orWhere('effective_to', '>', now());
                })
                ->first();

            if (!$component) {
                throw new \Exception("Component not found or no longer active");
            }

            $updated = $component->update(['amount' => $amount]);

            if ($updated) {
                Flux::toast(
                    variant: 'success',
                    heading: 'Amount Updated',
                    text: "Component amount has been updated successfully",
                );
            } else {
                throw new \Exception("Failed to update component amount");
            }

        } catch (\Exception $e) {

            Flux::toast(
                variant: 'error',
                heading: 'Error',
                text: $e->getMessage(),
            );
        }
    }

    public function getComponentAmount($employeeId, $componentId)
    {
        return $this->componentAmounts[$employeeId][$componentId] ?? null;
    }

    protected function buildDependencyGraph($components)
    {
        $graph = [];
        foreach ($components as $component) {
            $graph[$component['id']] = [
                'dependencies' => $this->findDependencies($component['calculation_json']),
                'calculated' => false,
                'component' => $component
            ];
        }
        return $graph;
    }

    protected function findDependencies($formula)
    {
        $dependencies = [];

        if (!is_array($formula)) {
            return $dependencies;
        }

        if ($formula['type'] === 'component' && isset($formula['key'])) {
            $dependencies[] = $formula['key'];
        }

        if (isset($formula['operands']) && is_array($formula['operands'])) {
            foreach ($formula['operands'] as $operand) {
                $dependencies = array_merge($dependencies, $this->findDependencies($operand));
            }
        }

        return array_unique($dependencies);
    }

    protected function getCalculationOrder($graph)
    {
        $order = [];
        $visited = [];
        $temp = [];

        // Helper function for topological sort
        $visit = function ($nodeId) use (&$visit, &$order, &$visited, &$temp, $graph) {
            if (isset($temp[$nodeId])) {
                throw new \Exception("Circular dependency detected in salary components");
            }
            if (isset($visited[$nodeId])) {
                return;
            }

            $temp[$nodeId] = true;

            foreach ($graph[$nodeId]['dependencies'] as $depId) {
                if (isset($graph[$depId])) {
                    $visit($depId);
                }
            }

            unset($temp[$nodeId]);
            $visited[$nodeId] = true;
            $order[] = $nodeId;
        };

        // Visit each node
        foreach ($graph as $nodeId => $node) {
            if (!isset($visited[$nodeId])) {
                $visit($nodeId);
            }
        }

        return $order;
    }

    public function syncCalculations($employeeId)
    {
        try {
            // Get the employee
            $employee = Employee::findOrFail($employeeId);

            // Get calculated components
            $calculatedComponents = SalaryComponentsEmployee::where('employee_id', $employeeId)
                ->where('firm_id', Session::get('firm_id'))
                ->whereHas('salary_component', function ($query) {
                    $query->where('amount_type', 'calculated_known')
                          ->where('component_type', '!=', 'tds');
                })
                ->with('salary_component')
                ->get()
                ->map(function ($employeeComponent) use ($employeeId) {
                    if (empty($employeeComponent->calculation_json)) {
                        throw new \Exception("No calculation formula defined for component {$employeeComponent->salary_component->title} for employee ID {$employeeId}");
                    }
                    return [
                        'id' => $employeeComponent->salary_component->id,
                        'title' => $employeeComponent->salary_component->title,
                        'calculation_json' => $employeeComponent->calculation_json
                    ];
                });

            // Build dependency graph
            $graph = $this->buildDependencyGraph($calculatedComponents);

            // Get calculation order
            try {
                $calculationOrder = $this->getCalculationOrder($graph);
            } catch (\Exception $e) {
                throw new \Exception("Error in calculation dependencies: " . $e->getMessage());
            }

            // Get initial component values
            $componentValues = $this->getComponentValuesForEmployee($employeeId);

            // Calculate components in order
            foreach ($calculationOrder as $componentId) {
                try {
                    $component = $graph[$componentId]['component'];

                    if (empty($component['calculation_json'])) {
                        throw new \Exception("No calculation formula defined for component {$component['title']}");
                    }

                    // Calculate value using all previously calculated values
                    $calculatedValue = $this->executeCalculation($component['calculation_json'], $componentValues);

                    // Update the component value in database
                    SalaryComponentsEmployee::where('employee_id', $employeeId)
                        ->where('salary_component_id', $componentId)
                        ->where('firm_id', Session::get('firm_id'))
                        ->update([
                            'amount' => $calculatedValue
                        ]);

                    // Update component values for next calculations
                    $componentValues[$componentId] = $calculatedValue;
                    $componentValues[$component['title']] = $calculatedValue;

                    // Mark as calculated in graph
                    $graph[$componentId]['calculated'] = true;

                } catch (\Exception $e) {
                    throw new \Exception("Error calculating {$component['title']}: " . $e->getMessage());
                }
            }

            // After calculating other components, calculate tax
            $this->calculateTax($employeeId);

            // Reload employee components
            $this->loadEmployeeComponents([$employeeId]);

            Flux::toast(
                variant: 'success',
                heading: 'Calculations Updated',
                text: "Calculated components for {$employee->fname} have been updated successfully"
            );

        } catch (\Exception $e) {
            Flux::toast(
                variant: 'error',
                heading: 'Error',
                text: $e->getMessage()
            );
        }
    }

    public function syncAllCalculations()
    {
        try {
            // Get the base query without pagination
            $query = SalaryComponentsEmployee::query()
                ->select([
                    'salary_components_employees.employee_id',
                    'employees.fname',
                    'employees.mname',
                    'employees.lname',
                    'employees.email',
                    'employees.phone',
                    'employees.gender',
                    'employee_job_profiles.employee_code',
                    'employee_job_profiles.department_id',
                    'employee_job_profiles.designation_id',
                    'departments.title as department_title',
                    'designations.title as designation_title'
                ])
                ->join('employees', 'employees.id', '=', 'salary_components_employees.employee_id')
                ->join('employee_job_profiles', 'employees.id', '=', 'employee_job_profiles.employee_id')
                ->leftJoin('departments', 'employee_job_profiles.department_id', '=', 'departments.id')
                ->leftJoin('designations', 'employee_job_profiles.designation_id', '=', 'designations.id')
                ->where('salary_components_employees.firm_id', Session::get('firm_id'))
                ->where(function ($query) {
                    $query->whereNull('salary_components_employees.effective_to')
                        ->orWhere('salary_components_employees.effective_to', '>', now());
                })
                ->where('employees.is_inactive', false)
                ->when($this->filters['department_id'], fn($query, $value) =>
                    $query->where('employee_job_profiles.department_id', $value))
                ->when($this->filters['designation_id'], fn($query, $value) =>
                    $query->where('employee_job_profiles.designation_id', $value))
                ->when($this->filters['salary_execution_group_id'], function($query, $value) {
                    $query->whereExists(function ($subquery) use ($value) {
                        $subquery->select('id')
                            ->from('employees_salary_execution_group')
                            ->whereColumn('employee_id', 'employees.id')
                            ->where('salary_execution_group_id', $value)
                            ->where('firm_id', Session::get('firm_id'));
                    });
                })
                ->when($this->filters['search'], fn($query, $value) =>
                    $query->where(function ($q) use ($value) {
                        $q->where('employees.fname', 'like', "%{$value}%")
                            ->orWhere('employees.lname', 'like', "%{$value}%")
                            ->orWhere('employees.email', 'like', "%{$value}%")
                            ->orWhere('employees.phone', 'like', "%{$value}%")
                            ->orWhere('employee_job_profiles.employee_code', 'like', "%{$value}%");
                    }))
                ->groupBy([
                    'salary_components_employees.employee_id',
                    'employees.fname',
                    'employees.mname',
                    'employees.lname',
                    'employees.email',
                    'employees.phone',
                    'employees.gender',
                    'employee_job_profiles.employee_code',
                    'employee_job_profiles.department_id',
                    'employee_job_profiles.designation_id',
                    'departments.title',
                    'designations.title'
                ])
                ->orderBy($this->sortBy, $this->sortDirection);

            // Get all employee IDs from the query
            $employeeIds = $query->pluck('employee_id')->toArray();
            $totalEmployees = count($employeeIds);
            
            $successCount = 0;
            $errorCount = 0;
            $errorMessages = [];

            Flux::toast(
                variant: 'info',
                heading: 'Starting Sync',
                text: "Starting calculations for {$totalEmployees} employees..."
            );

            foreach ($employeeIds as $employeeId) {
                try {
                    // Get the employee
                    $employee = Employee::findOrFail($employeeId);

                    // Get calculated components
                    $calculatedComponents = SalaryComponentsEmployee::where('employee_id', $employeeId)
                        ->where('firm_id', Session::get('firm_id'))
                        ->whereHas('salary_component', function ($query) {
                            $query->where('amount_type', 'calculated_known')
                                  ->where('component_type', '!=', 'tds');
                        })
                        ->with('salary_component')
                        ->get()
                        ->map(function ($employeeComponent) use ($employeeId) {
                            if (empty($employeeComponent->calculation_json)) {
                                throw new \Exception("No calculation formula defined for component {$employeeComponent->salary_component->title} for employee ID {$employeeId}");
                            }
                            return [
                                'id' => $employeeComponent->salary_component->id,
                                'title' => $employeeComponent->salary_component->title,
                                'calculation_json' => $employeeComponent->calculation_json
                            ];
                        });

                    // Build dependency graph
                    $graph = $this->buildDependencyGraph($calculatedComponents);

                    // Get calculation order
                    $calculationOrder = $this->getCalculationOrder($graph);

                    // Get initial component values
                    $componentValues = $this->getComponentValuesForEmployee($employeeId);

                    // Calculate components in order
                    foreach ($calculationOrder as $componentId) {
                        $component = $graph[$componentId]['component'];

                        if (empty($component['calculation_json'])) {
                            throw new \Exception("No calculation formula defined for component {$component['title']}");
                        }

                        // Calculate value using all previously calculated values
                        $calculatedValue = $this->executeCalculation($component['calculation_json'], $componentValues);

                        // Update the component value in database
                        SalaryComponentsEmployee::where('employee_id', $employeeId)
                            ->where('salary_component_id', $componentId)
                            ->where('firm_id', Session::get('firm_id'))
                            ->update([
                                'amount' => $calculatedValue
                            ]);

                        // Update component values for next calculations
                        $componentValues[$componentId] = $calculatedValue;
                        $componentValues[$component['title']] = $calculatedValue;

                        // Mark as calculated in graph
                        $graph[$componentId]['calculated'] = true;
                    }

                    // After calculating other components, calculate tax
                    $this->calculateTax($employeeId);
                    $successCount++;

                    // Show progress toast every 10 employees
                    if ($successCount % 10 === 0) {
                        Flux::toast(
                            variant: 'info',
                            heading: 'Progress Update',
                            text: "Processed {$successCount} of {$totalEmployees} employees..."
                        );
                    }

                } catch (\Exception $e) {
                    $errorCount++;
                    $errorMessages[] = "Employee ID {$employeeId}: " . $e->getMessage();
                }
            }

            // Reload employee components for all employees
            $this->loadEmployeeComponents($employeeIds);

            // Show summary toast
            if ($errorCount === 0) {
                Flux::toast(
                    variant: 'success',
                    heading: 'All Calculations Updated',
                    text: "Successfully updated calculations for {$successCount} employees"
                );
            } else {
                Flux::toast(
                    variant: 'warning',
                    heading: 'Partial Success',
                    text: "Updated {$successCount} employees, failed for {$errorCount} employees. Check logs for details."
                );
                \Log::error("Bulk sync errors: " . implode("\n", $errorMessages));
            }

        } catch (\Exception $e) {
            Flux::toast(
                variant: 'error',
                heading: 'Error',
                text: "Failed to sync calculations: " . $e->getMessage()
            );
        }
    }

    protected function getComponentValuesForEmployee($employeeId)
    {
        $values = [];
        $components = SalaryComponentsEmployee::select([
            'salary_components_employees.amount',
            'salary_components_employees.salary_component_id',
            'salary_components.title'
        ])
            ->join('salary_components', 'salary_components.id', '=', 'salary_components_employees.salary_component_id')
            ->where('salary_components_employees.employee_id', $employeeId)
            ->where('salary_components_employees.firm_id', Session::get('firm_id'))
            ->get();

        foreach ($components as $component) {
            $values[$component->salary_component_id] = floatval($component->amount);
            $values[$component->title] = floatval($component->amount);
        }

        return $values;
    }

    protected function validateRequiredComponents($calculationJson, $componentValues)
    {
        // Handle both array and string inputs
        $json = is_array($calculationJson) ? $calculationJson : json_decode($calculationJson, true);
        $requiredComponents = $this->extractRequiredComponents($json);

        foreach ($requiredComponents as $component) {
            // Case-insensitive component check
            $found = false;
            foreach ($componentValues as $title => $value) {
                if (strtolower($title) === strtolower($component)) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                return false;
            }
        }

        return true;
    }

    protected function calculateTax($employeeId)
    {
        // 1. Get employee's tax regime
        $employeeTaxRegime = EmployeeTaxRegime::where('employee_id', $employeeId)
            ->where('firm_id', Session::get('firm_id'))
            ->where(function ($query) {
                $query->whereNull('effective_to')
                    ->orWhere('effective_to', '>', now());
            })
            ->with('tax_regime.tax_brackets')
            ->first();

        if (!$employeeTaxRegime) {
            // Instead of throwing an exception, just log or skip tax calculation
            \Log::info("Skipping tax calculation: No active tax regime for employee ID {$employeeId}");
            return; // Exit function without throwing error
        }
        try {
            // 2. Get total earnings for the month
            $salaryCycleEarnings = SalaryComponentsEmployee::where('employee_id', $employeeId)
                ->where('firm_id', Session::get('firm_id'))
                ->where('nature', 'earning')
                ->where('taxable', true)
                ->where(function ($query) {
                    $query->whereNull('effective_to')
                        ->orWhere('effective_to', '>', now());
                })
                ->sum('amount');
            $monthlyEarnings = $salaryCycleEarnings; //function to be added later

            // 3. Calculate annual income
            $annualIncome = ($monthlyEarnings * 12)-75000;//assuming standard deduction of 75000



            // 4. Get tax brackets for the regime
            $taxBrackets = $employeeTaxRegime->tax_regime->tax_brackets()
                ->where('type', 'SLAB')
                ->orderBy('income_from')
                ->get();

            // 5. Calculate tax for each slab
            $totalTax = 0;
            $tax_see='';
            $health_education_cess = 0;

            $remainingIncome = $annualIncome;

            foreach ($taxBrackets as $bracket) {
                $slabAmount = min(
                    $remainingIncome,
                    ($bracket->income_to ?? PHP_FLOAT_MAX) - $bracket->income_from
                );

                if ($slabAmount > 0) {
                    $taxForSlab = round(   ($slabAmount * $bracket->rate) / 100);
                    $totalTax += $taxForSlab;
                    $tax_see.="*".$taxForSlab;
                    $remainingIncome -= $slabAmount;
                }

                if ($remainingIncome <= 0) {
                    break;
                }
            }
$health_education_cess = .04 * $totalTax;


            // 6. Calculate monthly
            $total_tds_applicable_for_year = $totalTax + $health_education_cess; // Total TDS applicable for the year
            $total_tds_ytd = $this->calculateTdsTillytd($employeeId); // Get actual YTD from PayrollComponentsEmployeesTrack
            $total_tds_remaining_for_year = $total_tds_applicable_for_year - $total_tds_ytd;
            
            // Get actual slot counts
            $total_count_of_salary_slots = $this->getTotalSlotsCount($employeeId);
            $total_count_of_salary_slots_proccessed = $this->getProcessedSlotsCount($employeeId);
            $total_count_of_salary_slots_remaining = $total_count_of_salary_slots - $total_count_of_salary_slots_proccessed;

            if ($total_count_of_salary_slots_remaining <= 0) {
                throw new \Exception("No remaining salary slots in current financial year");
            }

            $monthlyTax = ($total_tds_remaining_for_year) / $total_count_of_salary_slots_remaining;
            $monthlyTax = $this->roundOffTax($monthlyTax);

            // 7. Update or create TDS component
            $tdsComponent = SalaryComponentsEmployee::updateOrCreate(
                [
                    'employee_id' => $employeeId,
                    'firm_id' => Session::get('firm_id'),
                    'salary_component_id' => $this->getTDSComponentId(), // You'll need to implement this
                ],
                [
                    'amount' => $monthlyTax,
                    'nature' => 'deduction',
                    'component_type' => 'tax',
                    'amount_type' => 'calculated_known',
                    'effective_from' => now(),
                ]
            );

            return $monthlyTax;

        } catch (\Exception $e) {
            throw new \Exception("Error calculating tax: " . $e->getMessage());
        }
    }

    // Helper method to get TDS component ID

    function roundOffTax($amount) {
        return round($amount / 10) * 10;
    }
    protected function calculateTdsTillytd($employeeId) 
    {
        try {
            // Get TDS component ID
            $tdsComponentId = $this->getTDSComponentId();
            
            // Get financial year from session
            $fyStart = session('fy_start');
            $fyEnd = session('fy_end');

            if (!$fyStart || !$fyEnd) {
                throw new \Exception("Financial year not set in session");
            }

            // Calculate total TDS deducted in current financial year
            $totalTdsYtd = PayrollComponentsEmployeesTrack::join('payroll_slots', 'payroll_components_employees_tracks.payroll_slot_id', '=', 'payroll_slots.id')
                ->where('payroll_components_employees_tracks.firm_id', Session::get('firm_id'))
                ->where('payroll_components_employees_tracks.employee_id', $employeeId)
                ->where('payroll_components_employees_tracks.salary_component_id', $tdsComponentId)
                ->where('payroll_components_employees_tracks.component_type', 'tds')
                ->whereBetween('payroll_components_employees_tracks.salary_period_from', [$fyStart, $fyEnd])
                ->where('payroll_slots.payroll_slot_status', 'CM') // Using the status from payroll_slots table
                ->sum('payroll_components_employees_tracks.amount_payable');

            return $totalTdsYtd;

        } catch (\Exception $e) {
            throw new \Exception("Error calculating YTD TDS: " . $e->getMessage());
        }
    }

    protected function getProcessedSlotsCount($employeeId) 
    {
        // Get financial year from session
        $fyStart = session('fy_start');
        $fyEnd = session('fy_end');

        // Get employee's execution group
        $executionGroupId = EmployeesSalaryExecutionGroup::where('employee_id', $employeeId)
            ->where('firm_id', Session::get('firm_id'))
            ->value('salary_execution_group_id');

        // Count completed slots in current financial year
        return PayrollSlot::where('firm_id', Session::get('firm_id'))
            ->where('salary_execution_group_id', $executionGroupId)
            ->whereBetween('from_date', [$fyStart, $fyEnd])
            ->where('payroll_slot_status', 'CM')
            ->count();
    }

    protected function getTotalSlotsCount($employeeId) 
    {
        // Get financial year from session
        $fyStart = session('fy_start');
        $fyEnd = session('fy_end');

        // Get employee's execution group
        $executionGroupId = EmployeesSalaryExecutionGroup::where('employee_id', $employeeId)
            ->where('firm_id', Session::get('firm_id'))
            ->value('salary_execution_group_id');

        // Count total slots in current financial year
        return PayrollSlot::where('firm_id', Session::get('firm_id'))
            ->where('salary_execution_group_id', $executionGroupId)
            ->whereBetween('from_date', [$fyStart, $fyEnd])
            ->count();
    }

    protected function getTDSComponentId()
    {
        return SalaryComponent::where('firm_id', Session::get('firm_id'))
            ->where('component_type', 'tds')

            ->value('id');
    }

    protected function extractRequiredComponents($node)
    {
        $components = [];

        if (is_array($node)) {
            if (isset($node['type']) && $node['type'] === 'component') {
                $components[] = $node['key'];
            } else {
                foreach ($node as $value) {
                    if (is_array($value)) {
                        $components = array_merge($components, $this->extractRequiredComponents($value));
                    }
                }
            }
        }

        return array_unique($components);
    }

    protected function executeCalculation($calculationJson, $componentValues)
    {
        if (empty($calculationJson)) {
            throw new \Exception("No calculation formula provided");
        }

        // If calculationJson is already an array, use it directly
        if (is_array($calculationJson)) {
            $json = $calculationJson;
        } else {
            // If it's a string, try to decode it
            $json = json_decode($calculationJson, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception("Invalid JSON formula: " . json_last_error_msg());
            }
        }

        return $this->evaluateNode($json, $componentValues);
    }

    protected function evaluateNode($node, $componentValues)
    {
        if (!is_array($node)) {
            return $node;
        }

        if (!isset($node['type'])) {
            throw new \Exception("Node type is not specified in formula");
        }

        switch ($node['type']) {
            case 'constant':
                return floatval($node['value']);

            case 'component':
                // Try to get value by ID first, then by title
                $key = $node['key'];
                if (isset($componentValues[$key])) {
                    return $componentValues[$key];
                }

                // If not found by ID, try to find by title (case-insensitive)
                $componentTitle = strtolower($key);
                foreach ($componentValues as $title => $value) {
                    if (strtolower($title) === $componentTitle) {
                        return $value;
                    }
                }
                throw new \Exception("Component '{$key}' not found in available components");

            case 'operation':
                $operands = array_map(
                    fn($operand) => $this->evaluateNode($operand, $componentValues),
                    $node['operands']
                );

                return $this->executeOperation($node['operator'], $operands);

            case 'function':
                $args = array_map(
                    fn($arg) => $this->evaluateNode($arg, $componentValues),
                    $node['args']
                );

                return $this->executeFunction($node['name'], $args);

            case 'conditional':
                $condition = $this->evaluateNode($node['if'], $componentValues);
                return $condition
                    ? $this->evaluateNode($node['then'], $componentValues)
                    : $this->evaluateNode($node['else'], $componentValues);

            default:
                throw new \Exception("Unknown node type: {$node['type']}");
        }
    }

    protected function executeOperation($operator, $operands)
    {
        if (empty($operands)) {
            throw new \Exception("No operands provided for operation");
        }

        switch ($operator) {
            case '+':
                return array_sum($operands);
            case '-':
                return $operands[0] - array_sum(array_slice($operands, 1));
            case '*':
                return array_reduce($operands, fn($carry, $item) => $carry * $item, 1);
            case '/':
                $result = $operands[0];
                for ($i = 1; $i < count($operands); $i++) {
                    if ($operands[$i] == 0) {
                        throw new \Exception("Division by zero");
                    }
                    $result /= $operands[$i];
                }
                return $result;
            case '>=':
                return $operands[0] >= ($operands[1] ?? 0);
            case '<=':
                return $operands[0] <= ($operands[1] ?? 0);
            case '>':
                return $operands[0] > ($operands[1] ?? 0);
            case '<':
                return $operands[0] < ($operands[1] ?? 0);
            case '==':
                return $operands[0] == ($operands[1] ?? 0);
            default:
                throw new \Exception("Unknown operator: {$operator}");
        }
    }

    protected function executeFunction($name, $args)
    {
        if (empty($args)) {
            throw new \Exception("No arguments provided for function {$name}");
        }

        switch ($name) {
            case 'min':
                return min(...$args);
            case 'max':
                return max(...$args);
            case 'round':
                $precision = $args[1] ?? 0;
                return round($args[0], $precision);
            case 'ceil':
                return ceil($args[0]);
            case 'floor':
                return floor($args[0]);
            default:
                throw new \Exception("Unknown function: {$name}");
        }
    }

    protected function hasCalculatedComponents($employeeId)
    {
        return SalaryComponentsEmployee::where('employee_id', $employeeId)
            ->where('firm_id', Session::get('firm_id'))
            ->where('amount_type', 'calculated_known')
            ->exists();
    }

    public function showSalarySlip($employeeId)
    {
        try {
            // Load employee with job profile
            $this->selectedEmployee = Employee::with('emp_job_profile.department', 'emp_job_profile.designation')
                ->findOrFail($employeeId);

            // Get salary components for the employee with their base component details
            $components = SalaryComponentsEmployee::where('employee_id', $employeeId)
                ->where('firm_id', Session::get('firm_id'))
                ->where(function ($query) {
                    $query->whereNull('effective_to')
                        ->orWhere('effective_to', '>', now());
                })
                ->with([
                    'salary_component' => function ($query) {
                        $query->select('id', 'title', 'nature', 'component_type', 'amount_type', 'calculation_json');
                    }
                ])
                ->get();

            $this->salaryComponents = [];
            $this->totalEarnings = 0;
            $this->totalDeductions = 0;

            // Group components by nature (earnings/deductions)
            foreach ($components as $component) {
                // Skip if salary_component relation is not loaded
                if (!$component->salary_component) {
                    continue;
                }

                $componentData = [
                    'title' => $component->salary_component->title,
                    'amount' => $component->amount,
                    'nature' => $component->nature,
                    'component_type' => $component->component_type,
                    'amount_type' => $component->amount_type,
                    'calculation_json' => $component->calculation_json
                ];

                $this->salaryComponents[] = $componentData;

                // Calculate totals based on nature
                if ($component->nature === 'earning') {
                    $this->totalEarnings += $component->amount;
                } elseif ($component->nature === 'deduction') {
                    $this->totalDeductions += $component->amount;
                }
            }

            // Sort components by nature and title
            $this->salaryComponents = collect($this->salaryComponents)->sortBy([
                ['nature', 'desc'], // earnings first
                ['title', 'asc']
            ])->values()->all();

            $this->netSalary = $this->totalEarnings - $this->totalDeductions;
            $this->netSalaryInWords = $this->numberToWords($this->netSalary);
            $this->showSalarySlipModal = true;

        } catch (\Exception $e) {
            Flux::toast(
                variant: 'error',
                heading: 'Error',
                text: 'Failed to load salary slip: ' . $e->getMessage(),
            );
        }
    }

    public function closeSalarySlipModal()
    {
        $this->showSalarySlipModal = false;
        $this->selectedEmployee = null;
        $this->salaryComponents = [];
        $this->totalEarnings = 0;
        $this->totalDeductions = 0;
        $this->netSalary = 0;
        $this->netSalaryInWords = '';
    }

    protected function numberToWords($number)
    {
        $f = new \NumberFormatter("en", \NumberFormatter::SPELLOUT);
        return ucfirst($f->format($number)) . ' Rupees Only';
    }

    public function downloadSalarySlip()
    {
        // TODO: Implement PDF generation and download
        // This will be implemented based on your PDF generation library preference
        Flux::toast(
            variant: 'info',
            heading: 'Coming Soon',
            text: 'PDF download functionality will be available soon.',
        );
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Payroll/blades/bulk-employee-salary-components.blade.php'));
    }
}