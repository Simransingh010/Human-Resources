<?php

namespace App\Livewire\Hrms\Payroll;

use App\Models\Hrms\Employee;
use App\Models\Hrms\SalaryComponent;
use App\Models\Hrms\SalaryComponentsEmployee;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\Session;
use Flux;

#[Title('Employee Salary Components')]
class SalaryComponentEmployees extends Component
{
    use WithPagination;

    public $perPage = 20;
    public $sortBy = 'title';
    public $sortDirection = 'asc';
    public $isEditing = false;
    public $selectedComponentId = null;
    public $employeeId = null;
    public $employee = null;
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

    public $salaryComponents = [];

    // Field configuration for form and table
    public array $fieldConfig = [
        'title' => ['label' => 'Title', 'type' => 'text'],
        'nature' => ['label' => 'Nature', 'type' => 'select', 'listKey' => 'natures'],
        'component_type' => ['label' => 'Component Type', 'type' => 'select', 'listKey' => 'component_types'],
        'amount_type' => ['label' => 'Amount Type', 'type' => 'select', 'listKey' => 'amount_types'],
        'amount' => ['label' => 'Amount', 'type' => 'number'],
        'taxable' => ['label' => 'Taxable', 'type' => 'switch'],

        'effective_from' => ['label' => 'Effective From', 'type' => 'date'],
        'effective_to' => ['label' => 'Effective To', 'type' => 'date'],
        'calculation_json' => ['label' => 'Calculation', 'type' => 'textarea'],
    ];

    // Filter fields configuration
    public array $filterFields = [
        'title' => ['label' => 'Title', 'type' => 'text'],
        'nature' => ['label' => 'Nature', 'type' => 'select', 'listKey' => 'natures'],
        'component_type' => ['label' => 'Component Type', 'type' => 'select', 'listKey' => 'component_types'],
    ];

    public array $listsForFields = [];
    public array $filters = [];
    public array $visibleFields = [];
    public array $visibleFilterFields = [];

    public $formData = [
        'id' => null,
        'firm_id' => null,
        'employee_id' => null,
        'salary_component_id' => null,
        'nature' => '',
        'component_type' => '',
        'amount_type' => '',
        'amount' => 0,
        'taxable' => false,
        'calculation_json' => null,
        'effective_from' => null,
        'effective_to' => null,
    ];

    public function mount($employeeId = null)
    {
        if ($employeeId) {
            $this->employeeId = $employeeId;
            $this->employee = Employee::findOrFail($employeeId);
            $this->formData['employee_id'] = $employeeId;
            $this->formData['firm_id'] = Session::get('firm_id');
        }

        $this->initListsForFields();
        $this->resetRule();

        // Load and normalize salary components for rule editor
        $components = SalaryComponent::where('firm_id', Session::get('firm_id'))->get();
        $this->salaryComponents = collect($components)->mapWithKeys(function ($component) {
            return [
                $component->id => [
                    'id' => $component->id,
                    'title' => $component->title ?? '',
                ]
            ];
        })->toArray();

        // Set default visible fields
        $this->visibleFields = ['title', 'nature', 'component_type', 'taxable', 'amount_type'];
        $this->visibleFilterFields = ['title', 'nature', 'component_type'];

        // Initialize filters
        $this->filters = array_fill_keys(array_keys($this->filterFields), '');

        $this->formData['effective_from'] = null;
        $this->formData['effective_to'] = null;
    }

    protected function initListsForFields(): void
    {
        // Get lists from model constants
        $this->listsForFields['natures'] = SalaryComponentsEmployee::NATURE_SELECT;
        $this->listsForFields['component_types'] = SalaryComponentsEmployee::COMPONENT_TYPE_SELECT;
        $this->listsForFields['amount_types'] = SalaryComponentsEmployee::AMOUNT_TYPE_SELECT;
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
        return SalaryComponentsEmployee::query()
            ->select([
                'salary_components_employees.*',
                'salary_components.title',
                'salary_components.description'
            ])
            ->join('salary_components', 'salary_components.id', '=', 'salary_components_employees.salary_component_id')
            ->where('salary_components_employees.firm_id', Session::get('firm_id'))
            ->where('salary_components_employees.employee_id', $this->employeeId)
            ->where(function ($query) {
                $query->whereNull('salary_components_employees.effective_to')
                    ->orWhere('salary_components_employees.effective_to', '>', now());
            })
            ->when($this->filters['title'], fn($query, $value) =>
            $query->where('salary_components.title', 'like', "%{$value}%"))
            ->when($this->filters['nature'], fn($query, $value) =>
            $query->where('salary_components_employees.nature', $value))
            ->when($this->filters['component_type'], fn($query, $value) =>
            $query->where('salary_components_employees.component_type', $value))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);
    }

    protected function rules()
    {
        return [
            'formData.salary_component_id' => 'required|integer',
            'formData.nature' => 'required|string',
            'formData.component_type' => 'required|string',
            'formData.amount_type' => 'required|string',
            'formData.amount' => 'required|numeric|min:0',
            'formData.taxable' => 'boolean',
            'formData.calculation_json' => 'nullable|json',
            'formData.effective_from' => 'nullable|date',
            'formData.effective_to' => 'nullable|date',
        ];
    }

    public function store()
    {
        $validatedData = $this->validate();

        $validatedData['formData'] = collect($validatedData['formData'])
            ->map(function ($val, $key) {
                if ($key === 'calculation_json' && !empty($val)) {
                    return json_decode($val, true);
                }
                return $val === '' ? null : $val;
            })
            ->toArray();

        $validatedData['formData']['firm_id'] = session('firm_id');
        $validatedData['formData']['employee_id'] = $this->employeeId;
        if (!$this->isEditing && empty($validatedData['formData']['effective_from'])) {
            $validatedData['formData']['effective_from'] = now();
        }

        if ($this->isEditing) {
            $currentComponent = SalaryComponentsEmployee::findOrFail($this->formData['id']);
            $currentComponent->update($validatedData['formData']);
            $toastMsg = 'Salary component updated successfully';
        } else {
            SalaryComponentsEmployee::create($validatedData['formData']);
            $toastMsg = 'Salary component added successfully';
        }

        $this->resetForm();
        $this->modal('mdl-salary-component')->close();
        Flux::toast(
            variant: 'success',
            heading: 'Changes saved.',
            text: $toastMsg,
        );
    }

    public function resetForm()
    {
        $this->reset(['formData']);
        $this->formData['taxable'] = false;
        $this->formData['amount'] = 0;
        $this->formData['effective_from'] = null;
        $this->formData['effective_to'] = null;
        $this->isEditing = false;
    }

    public function edit($id)
    {
        $this->isEditing = true;
        $component = SalaryComponentsEmployee::with('salary_component')->findOrFail($id);
        $this->formData = array_merge($this->formData, [
            'id' => $component->id,
            'firm_id' => $component->firm_id,
            'employee_id' => $component->employee_id,
            'salary_component_id' => $component->salary_component_id,
            'nature' => $component->nature,
            'component_type' => $component->component_type,
            'amount_type' => $component->amount_type,
            'amount' => $component->amount,
            'taxable' => $component->taxable,
            'calculation_json' => is_array($component->calculation_json)
                ? json_encode($component->calculation_json, JSON_PRETTY_PRINT)
                : $component->calculation_json,
            'title' => $component->salary_component->title,
            'effective_from' => $component->effective_from ? $component->effective_from->format('Y-m-d') : null,
            'effective_to' => $component->effective_to ? $component->effective_to->format('Y-m-d') : null,
        ]);
        $this->modal('mdl-salary-component')->show();
    }

    public function delete($id)
    {
        $component = SalaryComponentsEmployee::findOrFail($id);
        $component->update(['effective_to' => now()]);

        Flux::toast(
            variant: 'success',
            heading: 'Record Deleted.',
            text: 'Salary component has been deactivated successfully',
        );
    }

    public function openCalculationRule($componentId)
    {
        try {
            $this->selectedComponentId = $componentId;
            $component = SalaryComponentsEmployee::findOrFail($componentId);

            if ($component->calculation_json) {
                $this->rule = $component->calculation_json;
            } else {
                $this->resetRule();
            }

            $this->modal('mdl-calculation-rule')->show();
        } catch (\Exception $e) {
            Flux::toast(
                variant: 'error',
                heading: 'Error',
                text: 'Failed to open calculation rule: ' . $e->getMessage()
            );
        }
    }

    protected function resetRule()
    {
        $this->rule = [
            'type' => 'operation',
            'operator' => '+',
            'operands' => []
        ];
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

    public function addNestedOperand($parentIndex)
    {
        if (!isset($this->rule['operands'][$parentIndex]['operands'])) {
            $this->rule['operands'][$parentIndex]['operands'] = [];
        }

        $this->rule['operands'][$parentIndex]['operands'][] = [
            'type' => 'component',
            'key' => null
        ];

        // If type is operation, initialize operator
        if ($this->rule['operands'][$parentIndex]['type'] === 'operation') {
            $lastIndex = count($this->rule['operands'][$parentIndex]['operands']) - 1;
            $this->rule['operands'][$parentIndex]['operator'] = '+';
        }
    }

    public function removeNestedOperand($parentIndex, $index)
    {
        if (isset($this->rule['operands'][$parentIndex]['operands'])) {
            array_splice($this->rule['operands'][$parentIndex]['operands'], $index, 1);
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

    protected function validateRule($rule, $depth = 0)
    {
        if ($depth > 5) { // Limit nesting to 5 levels for practicality
            throw new \Exception('Calculation rule nesting is too deep (max 5 levels)');
        }

        if (!isset($rule['type'])) {
            throw new \Exception('Rule type is required');
        }

        switch ($rule['type']) {
            case 'operation':
                if (!isset($rule['operator'])) {
                    throw new \Exception('Operation requires an operator');
                }
                if (!isset($rule['operands']) || !is_array($rule['operands'])) {
                    throw new \Exception('Operation requires operands array');
                }
                if (count($rule['operands']) < 2) {
                    throw new \Exception('Operation requires at least 2 operands');
                }
                foreach ($rule['operands'] as $operand) {
                    $this->validateRule($operand, $depth + 1);
                }
                break;

            case 'component':
                if (!isset($rule['key'])) {
                    throw new \Exception('Component key is missing. Rule: ' . json_encode($rule));
                }

                if (!array_key_exists($rule['key'], $this->salaryComponents)) {
                    throw new \Exception('Invalid component key: ' . $rule['key'] . '. Available keys: ' . implode(', ', array_keys($this->salaryComponents)));
                }
                break;

            case 'constant':
                if (!isset($rule['value']) || !is_numeric($rule['value'])) {
                    throw new \Exception('Constant requires numeric value');
                }
                break;
        }
    }

    public function saveRule()
    {
        try {
            // Validate the entire rule structure before saving
            $this->validateRule($this->rule);

            if ($this->selectedComponentId) {
                $component = SalaryComponentsEmployee::findOrFail($this->selectedComponentId);
                $component->update(['calculation_json' => $this->rule]);

                $this->modal('mdl-calculation-rule')->close();
                Flux::toast(
                    variant: 'success',
                    heading: 'Rule Saved',
                    text: 'Calculation rule has been updated successfully'
                );
            }
        } catch (\Exception $e) {
            Flux::toast(
                variant: 'error',
                heading: 'Validation Error',
                text: $e->getMessage()
            );
        }
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Payroll/blades/salary-component-employees.blade.php'));
    }
}