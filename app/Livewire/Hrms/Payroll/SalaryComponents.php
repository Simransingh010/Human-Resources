<?php

namespace App\Livewire\Hrms\Payroll;

use App\Models\Hrms\SalaryComponent;
use App\Models\Hrms\SalaryComponentGroup;
use App\Models\Hrms\SalaryComponentsEmployee;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Session;
use Flux;

class SalaryComponents extends Component
{
    use WithPagination;

    public $perPage = 10;
    public $sortBy = 'title';
    public $sortDirection = 'asc';
    public $isEditing = false;
    public $selectedComponentId = null;
    public $salaryComponents = [];
    public $rule = [
        'type' => 'operation',
        'operator' => '+',
        'operands' => []
    ];
    public $currentPath = '';

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

    protected $listeners = [
        'ruleSaved' => 'handleRuleSaved'
    ];

    // Field configuration for form and table
    public array $fieldConfig = [
        'title' => ['label' => 'Title', 'type' => 'text'],
        'description' => ['label' => 'Description', 'type' => 'textarea'],
        'salary_component_group_id' => ['label' => 'Component Group', 'type' => 'select', 'listKey' => 'component_groups'],
        'nature' => ['label' => 'Nature', 'type' => 'select', 'listKey' => 'natures'],
        'component_type' => ['label' => 'Component Type', 'type' => 'select', 'listKey' => 'component_types'],
        'amount_type' => ['label' => 'Amount Type', 'type' => 'select', 'listKey' => 'amount_types'],
        'taxable' => ['label' => 'Taxable', 'type' => 'switch'],
        'calculation_json' => ['label' => 'Calculation', 'type' => 'textarea'],
        'document_required' => ['label' => 'Document Required', 'type' => 'switch'],
        'is_inactive' => ['label' => 'Inactive', 'type' => 'switch'],
    ];

    // Filter fields configuration
    public array $filterFields = [
        'title' => ['label' => 'Title', 'type' => 'text'],
        'salary_component_group_id' => ['label' => 'Component Group', 'type' => 'select', 'listKey' => 'component_groups'],
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
        'title' => '',
        'description' => '',
        'salary_component_group_id' => null,
        'nature' => '',
        'component_type' => '',
        'amount_type' => '',
        'taxable' => false,
        'calculation_json' => null,
        'document_required' => false,
        'is_inactive' => false,
    ];

    public function mount()
    {
        $this->initListsForFields();
        $this->resetRule();

        // Set default visible fields
        $this->visibleFields = ['title', 'salary_component_group_id', 'nature', 'component_type', 'taxable', 'amount_type'];
        $this->visibleFilterFields = ['title', 'salary_component_group_id', 'nature', 'component_type'];

        // Initialize filters
        $this->filters = array_fill_keys(array_keys($this->filterFields), '');

        // Load and normalize salary components for rule editor
        $components = SalaryComponent::where('firm_id', Session::get('firm_id'))->get();

        $this->salaryComponents = collect($components)->mapWithKeys(function ($component) {
            // Handle both array and object cases
            if (is_array($component)) {
                return [
                    $component['id'] => [
                        'id' => $component['id'],
                        'title' => $component['title'] ?? '',
                    ]
                ];
            } elseif (is_object($component)) {
                // If it's an Eloquent model or stdClass
                return [
                    $component->id => [
                        'id' => $component->id,
                        'title' => $component->title ?? '',
                    ]
                ];
            }

            // Fallback for unexpected types
            return [];
        })->toArray();
    }

    /**
     * Get the normalized title for a salary component
     * @param mixed $component
     * @return string
     */
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

    /**
     * Validate that a component exists in the salary components list
     * @param mixed $key
     * @return bool
     */
    protected function isValidComponentKey($key)
    {
        return array_key_exists($key, $this->salaryComponents);
    }

    protected function initListsForFields(): void
    {
        // Get component groups for dropdown
        $this->listsForFields['component_groups'] = SalaryComponentGroup::where('firm_id', Session::get('firm_id'))
            ->where('is_inactive', false)
            ->pluck('title', 'id')
            ->toArray();

        // Get lists from model constants
        $this->listsForFields['natures'] = SalaryComponent::NATURE_SELECT;
        $this->listsForFields['component_types'] = SalaryComponent::COMPONENT_TYPE_SELECT;
        $this->listsForFields['amount_types'] = SalaryComponent::AMOUNT_TYPE_SELECT;
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
        return SalaryComponent::query()
            ->where('firm_id', Session::get('firm_id'))
            ->when($this->filters['title'], fn($query, $value) =>
                $query->where('title', 'like', "%{$value}%"))
            ->when($this->filters['salary_component_group_id'], fn($query, $value) =>
                $query->where('salary_component_group_id', $value))
            ->when($this->filters['nature'], fn($query, $value) =>
                $query->where('nature', $value))
            ->when($this->filters['component_type'], fn($query, $value) =>
                $query->where('component_type', $value))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);
    }

    protected function rules()
    {
        return [
            'formData.title' => 'required|string|max:255',
            'formData.description' => 'nullable|string',
            'formData.salary_component_group_id' => 'nullable|integer',
            'formData.nature' => 'required|string',
            'formData.component_type' => 'required|string',
            'formData.amount_type' => 'required|string',
            'formData.taxable' => 'boolean',
            'formData.calculation_json' => 'nullable|json',
            'formData.document_required' => 'boolean',
            'formData.is_inactive' => 'boolean'
        ];
    }

    public function store()
    {
        $validatedData = $this->validate();

        $validatedData['formData'] = collect($validatedData['formData'])
            ->map(function ($val, $key) {
                if ($key === 'calculation_json' && !empty($val)) {
                    // Deco de JSON string to array before saving
                    return json_decode($val, true);
                }
                return $val === '' ? null : $val;
            })
            ->toArray();

        $validatedData['formData']['firm_id'] = session('firm_id');

        if ($this->isEditing) {
            $salaryComponent = SalaryComponent::findOrFail($this->formData['id']);
            $salaryComponent->update($validatedData['formData']);
            $toastMsg = 'Salary component updated successfully';
        } else {
            SalaryComponent::create($validatedData['formData']);
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
        $this->formData['document_required'] = false;
        $this->formData['is_inactive'] = false;
        $this->isEditing = false;
    }

    public function edit($id)
    {
        $this->isEditing = true;
        $salaryComponent = SalaryComponent::findOrFail($id);
        $this->formData = array_merge($this->formData, $salaryComponent->toArray());

        // Ensure calculation_json is a string for the form
        if (is_array($this->formData['calculation_json'])) {
            $this->formData['calculation_json'] = json_encode($this->formData['calculation_json'], JSON_PRETTY_PRINT);
        }

        $this->modal('mdl-salary-component')->show();
    }

    public function delete($id)
    {
        // Check if salary component has related records
        $salaryComponent = SalaryComponent::findOrFail($id);
        if (
            $salaryComponent->employees()->count() > 0 ||
            $salaryComponent->salary_templates_components()->count() > 0
        ) {
            Flux::toast(
                variant: 'error',
                heading: 'Cannot Delete',
                text: 'This salary component has related records and cannot be deleted.',
            );
            return;
        }

        $salaryComponent->delete();
        Flux::toast(
            variant: 'success',
            heading: 'Record Deleted.',
            text: 'Salary component has been deleted successfully',
        );
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

    public function openCalculationRule($componentId)
    {
        try {
            $this->selectedComponentId = $componentId;
            $component = SalaryComponent::findOrFail($componentId);

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

    public function addArg($section)
    {
        if ($section === 'then' && $this->rule['then']['type'] === 'function') {
            if (!isset($this->rule['then']['args'])) {
                $this->rule['then']['args'] = [];
            }
            $this->rule['then']['args'][] = ['type' => 'constant', 'value' => 0];
        } elseif ($section === 'else' && $this->rule['else']['type'] === 'function') {
            if (!isset($this->rule['else']['args'])) {
                $this->rule['else']['args'] = [];
            }
            $this->rule['else']['args'][] = ['type' => 'constant', 'value' => 0];
        }
    }

    public function removeArg($section, $index)
    {
        if ($section === 'then' && isset($this->rule['then']['args'])) {
            array_splice($this->rule['then']['args'], $index, 1);
        } elseif ($section === 'else' && isset($this->rule['else']['args'])) {
            array_splice($this->rule['else']['args'], $index, 1);
        }
    }

    public function handleRuleSaved($rule)
    {
        if ($this->selectedComponentId) {
            $component = SalaryComponent::findOrFail($this->selectedComponentId);
            $component->update(['calculation_json' => $rule]);

            Flux::toast(
                variant: 'success',
                heading: 'Rule Saved',
                text: 'Calculation rule has been updated successfully',
            );

            $this->selectedComponentId = null;
        }
    }

    public function addNestedOperation($path)
    {
        $parts = explode('.', $path);
        $current = &$this->rule;

        foreach ($parts as $part) {
            if (is_numeric($part)) {
                $current = &$current[(int) $part];
            } else {
                $current = &$current[$part];
            }
        }

        if (!isset($current['operands'])) {
            $current['operands'] = [];
        }

        $current['operands'][] = [
            'type' => 'constant',
            'value' => 0
        ];
    }

    public function removeNestedOperation($path, $index)
    {
        $parts = explode('.', $path);
        $current = &$this->rule;

        foreach ($parts as $part) {
            if (is_numeric($part)) {
                $current = &$current[(int) $part];
            } else {
                $current = &$current[$part];
            }
        }

        array_splice($current['operands'], $index, 1);
    }

    public function addFunctionArg($path)
    {
        $parts = explode('.', $path);
        $current = &$this->rule;

        foreach ($parts as $part) {
            if (is_numeric($part)) {
                $current = &$current[(int) $part];
            } else {
                $current = &$current[$part];
            }
        }

        if (!isset($current['args'])) {
            $current['args'] = [];
        }

        $current['args'][] = [
            'type' => 'constant',
            'value' => 0
        ];
    }

    public function removeFunctionArg($path, $index)
    {
        $parts = explode('.', $path);
        $current = &$this->rule;

        foreach ($parts as $part) {
            if (is_numeric($part)) {
                $current = &$current[(int) $part];
            } else {
                $current = &$current[$part];
            }
        }

        array_splice($current['args'], $index, 1);
    }

    public function addDeepNestedOperand($parentIndex, $nestedIndex)
    {
        if (!isset($this->rule['operands'][$parentIndex]['operands'][$nestedIndex]['operands'])) {
            $this->rule['operands'][$parentIndex]['operands'][$nestedIndex]['operands'] = [];
        }

        $this->rule['operands'][$parentIndex]['operands'][$nestedIndex]['operands'][] = [
            'type' => 'component',
            'key' => null
        ];
    }

    public function removeDeepNestedOperand($parentIndex, $nestedIndex, $index)
    {
        if (isset($this->rule['operands'][$parentIndex]['operands'][$nestedIndex]['operands'])) {
            array_splice($this->rule['operands'][$parentIndex]['operands'][$nestedIndex]['operands'], $index, 1);
        }
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
                if (!isset($rule['key']) || !array_key_exists($rule['key'], $this->salaryComponents)) {
                    throw new \Exception('Invalid or missing component key');
                }
                break;

            case 'constant':
                if (!isset($rule['value']) || !is_numeric($rule['value'])) {
                    throw new \Exception('Constant requires numeric value');
                }
                break;
        }
    }

    public function getRulePreview()
    {
        return $this->formatRule($this->rule);
    }

    protected function formatRule($rule)
    {
        switch ($rule['type']) {
            case 'conditional':
                $if = $this->formatRule($rule['if']);
                $then = $this->formatRule($rule['then']);
                $else = $this->formatRule($rule['else']);
                return "IF {$if} THEN {$then} ELSE {$else}";

            case 'operation':
                $operands = array_map(fn($op) => $this->formatRule($op), $rule['operands']);
                return '(' . implode(' ' . $rule['operator'] . ' ', $operands) . ')';

            case 'function':
                $args = array_map(fn($arg) => $this->formatRule($arg), $rule['args']);
                return $rule['name'] . '(' . implode(', ', $args) . ')';

            case 'component':
                return '[' . $rule['key'] . ']';

            case 'constant':
                return (string) $rule['value'];
        }
    }

    public function saveRule()
    {
        try {
            // Validate the entire rule structure before saving
            $this->validateRule($this->rule);

            if ($this->selectedComponentId) {
                $component = SalaryComponent::findOrFail($this->selectedComponentId);
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

    public function renderNestedOperation($path, $operation)
    {
        $html = '<div class="nested-operation-container ml-4 p-4 border-l-2 border-blue-200">';

        // Operator Selection
        $html .= '<div class="mb-4">';
        $html .= '<label class="block text-sm font-medium text-gray-700 mb-2">Operator for Nested Operation</label>';
        $html .= '<flux:select wire:model.live="rule.' . $path . '.operator">';
        $html .= '<flux:select.option value="+">Add (+)</flux:select.option>';
        $html .= '<flux:select.option value="-">Subtract (-)</flux:select.option>';
        $html .= '<flux:select.option value="*">Multiply (×)</flux:select.option>';
        $html .= '<flux:select.option value="/">Divide (÷)</flux:select.option>';
        $html .= '</flux:select>';
        $html .= '</div>';

        // Nested Operands
        $html .= '<div class="space-y-4">';
        $html .= '<div class="flex justify-between items-center">';
        $html .= '<label class="block text-sm font-medium text-gray-700">Nested Operands</label>';
        $html .= '<flux:button size="sm" wire:click="addOperand(\'' . $path . '\')" class="text-sm">';
        $html .= 'Add Nested Operand';
        $html .= '</flux:button>';
        $html .= '</div>';

        foreach ($operation['operands'] ?? [] as $i => $operand) {
            $html .= '<div class="relative p-4 border rounded-lg bg-white shadow-sm">';

            // Type Selection
            $html .= '<div class="flex items-center gap-4 mb-4">';
            $html .= '<div class="flex-1">';
            $html .= '<flux:select wire:model.live="rule.' . $path . '.operands.' . $i . '.type">';
            $html .= '<flux:select.option value="component">Salary Component</flux:select.option>';
            $html .= '<flux:select.option value="constant">Fixed Value</flux:select.option>';
            $html .= '<flux:select.option value="operation">Nested Operation</flux:select.option>';
            $html .= '</flux:select>';
            $html .= '</div>';
            $html .= '<flux:button wire:click="removeOperand(\'' . $path . '\', ' . $i . ')" class="text-red-500">';
            $html .= 'Remove';
            $html .= '</flux:button>';
            $html .= '</div>';

            // Content based on type
            if ($operand['type'] === 'operation') {
                $html .= $this->renderNestedOperation($path . '.operands.' . $i, $operand);
            } elseif ($operand['type'] === 'component') {
                $html .= '<div class="ml-4">';
                $html .= '<label class="block text-sm font-medium text-gray-700 mb-2">Select Component</label>';
                $html .= '<flux:select wire:model.live="rule.' . $path . '.operands.' . $i . '.key">';

                foreach ($this->salaryComponents as $id => $component) {
                    $title = $this->getComponentTitle($component);
                    $html .= '<flux:select.option value="' . htmlspecialchars($id) . '">' . htmlspecialchars($title) . '</flux:select.option>';
                }

                $html .= '</flux:select>';
                $html .= '</div>';
            } else {
                $html .= '<div class="ml-4">';
                $html .= '<label class="block text-sm font-medium text-gray-700 mb-2">Enter Value</label>';
                $html .= '<flux:input type="number" step="0.01" wire:model.live="rule.' . $path . '.operands.' . $i . '.value" placeholder="Enter value" />';
                $html .= '</div>';
            }

            $html .= '</div>';
        }

        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Payroll/blades/salary-components.blade.php'));
    }
}
