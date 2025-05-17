<?php

namespace App\Livewire\Hrms\Payroll;

use App\Models\Hrms\Employee;
use App\Models\Hrms\SalaryComponent;
use App\Models\Settings\Department;
use App\Models\Settings\Designation;
use App\Models\Hrms\SalaryComponentsEmployee;
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

    // Filter fields configuration
    public array $filterFields = [
        'department_id' => ['label' => 'Department', 'type' => 'select', 'listKey' => 'departments'],
        'designation_id' => ['label' => 'Designation', 'type' => 'select', 'listKey' => 'designations'],
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
        $this->visibleFilterFields = ['department_id', 'designation_id', 'search'];

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
    }

    protected function loadComponents()
    {
        $this->components = SalaryComponent::where('firm_id', Session::get('firm_id'))
            ->orderBy('title')
            ->get()
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
                'employees.lname',
                'employees.email'
            ])
            ->join('employees', 'employees.id', '=', 'salary_components_employees.employee_id')
            ->join('employee_job_profiles', 'employees.id', '=', 'employee_job_profiles.employee_id')
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
            ->when($this->filters['search'], fn($query, $value) =>
                $query->where(function ($q) use ($value) {
                    $q->where('employees.fname', 'like', "%{$value}%")
                        ->orWhere('employees.lname', 'like', "%{$value}%")
                        ->orWhere('employees.email', 'like', "%{$value}%");
                }))
            ->groupBy([
                'salary_components_employees.employee_id',
                'employees.fname',
                'employees.lname',
                'employees.email'
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

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Payroll/blades/bulk-employee-salary-components.blade.php'));
    }
}