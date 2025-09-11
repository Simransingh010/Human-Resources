<?php

namespace App\Livewire\Hrms\Payroll;

use App\Models\Hrms\SalaryAdvance;
use App\Models\Hrms\Employee;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Session;
use Flux;

class SalaryAdvances extends Component
{
    use WithPagination;

    public $perPage = 10;
    public $sortBy = 'id';
    public $sortDirection = 'desc';

    // Modal Properties
    public $showAdvanceModal = false;
    public $selectedEmployee = null;
    public $advance_date = null;
    public $amount = null;
    public $installments = null;
    public $installment_amount = null;
    public $remarks = '';
    public $disburse_salary_component = null;
    public $recovery_salary_component = null;
    public $disburse_payroll_slot_id = null;
    public $recovery_wef_payroll_slot_id = null;
    public $additional_rule_remarks = null;
    public $isEditing = false;
    public $editingId = null;
    public $advanceItems = [];

    // Field configuration for form and table
    public array $fieldConfig = [
        'employee_name' => ['label' => 'Employee', 'type' => 'text'],
        'advance_date' => ['label' => 'Advance Date', 'type' => 'date'],
        'amount' => ['label' => 'Amount', 'type' => 'number'],
        'installments' => ['label' => 'Installments', 'type' => 'number'],
        'installment_amount' => ['label' => 'Installment Amount', 'type' => 'number'],
        'advance_status' => ['label' => 'Status', 'type' => 'text'],
        'disburse_salary_component' => ['label' => 'Disburse Component', 'type' => 'text'],
        'recovery_salary_component' => ['label' => 'Recovery Component', 'type' => 'text'],
        'disburse_payroll_slot_id' => ['label' => 'Disburse Payroll Slot', 'type' => 'select', 'listKey' => 'payrollSlots'],
        'recovery_wef_payroll_slot_id' => ['label' => 'Recovery WEF Payroll Slot', 'type' => 'select', 'listKey' => 'payrollSlots'],
        'additional_rule_remarks' => ['label' => 'Additional Remarks', 'type' => 'textarea'],
        'remarks' => ['label' => 'Remarks', 'type' => 'text'],
        'created_at' => ['label' => 'Created At', 'type' => 'datetime']
    ];

    // Filter fields configuration
    public array $filterFields = [
        'employee_id' => ['label' => 'Employee', 'type' => 'select', 'listKey' => 'employees'],
        'advance_status' => ['label' => 'Status', 'type' => 'select', 'listKey' => 'statuses']
    ];

    public array $listsForFields = [];
    public array $filters = [];
    public array $visibleFields = [];
    public array $visibleFilterFields = [];

    public function mount()
    {
        $this->initListsForFields();

        // Set default visible fields
        $this->visibleFields = [
            'employee_name',
            'advance_date',
            'amount',
            'installments',
            'installment_amount',
            'advance_status',
            'remarks',
            'created_at'
        ];

        $this->visibleFilterFields = [
            'employee_id',
            'advance_status'
        ];

        // Initialize filters
        $this->filters = array_fill_keys(array_keys($this->filterFields), '');
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
        // Statuses
        $this->listsForFields['statuses'] = SalaryAdvance::$advanceStatuses;
        // Payroll slots
        $this->listsForFields['payrollSlots'] = \App\Models\Hrms\PayrollSlot::where('firm_id', Session::get('firm_id'))
            ->orderBy('from_date', 'desc')
            ->get()
            ->mapWithKeys(function ($slot) {
                return [$slot->id => $slot->from_date->format('jS F Y') . ' to ' . $slot->to_date->format('jS F Y')];
            })
            ->toArray();
        $this->listsForFields['disburseComponents'] = [];
        $this->listsForFields['recoveryComponents'] = [];
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

    public function openAdvanceModal()
    {
        $this->reset(['selectedEmployee', 'advance_date', 'amount', 'installments', 'installment_amount', 'remarks', 'disburse_salary_component', 'recovery_salary_component', 'disburse_payroll_slot_id', 'recovery_wef_payroll_slot_id', 'additional_rule_remarks']);
        $this->advanceItems = [
            ['disburse_salary_component' => '', 'recovery_salary_component' => '', 'amount' => null]
        ];
        $this->showAdvanceModal = true;
    }

    public function closeAdvanceModal()
    {
        $this->showAdvanceModal = false;
        $this->reset(['selectedEmployee', 'advance_date', 'amount', 'installments', 'installment_amount', 'remarks', 'disburse_salary_component', 'recovery_salary_component', 'disburse_payroll_slot_id', 'recovery_wef_payroll_slot_id', 'additional_rule_remarks']);
        $this->advanceItems = [];
    }

    public function updatedAmount($value)
    {
        $this->calculateInstallmentAmount();
    }

    public function updatedInstallments($value)
    {
        $this->calculateInstallmentAmount();
    }

    public function updatedSelectedEmployee($value)
    {
        $this->loadComponentLists($value);
    }

    protected function loadComponentLists($employeeId)
    {
        // Disburse: component_type = 'advance'
        $this->listsForFields['disburseComponents'] = \App\Models\Hrms\SalaryComponentsEmployee::where('employee_id', $employeeId)
            ->whereHas('salary_component', function ($q) {
                $q->where('component_type', 'advance');
            })
            ->with('salary_component')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->salary_component->title => $item->salary_component->title];
            })
            ->toArray();
        // Recovery: component_type = 'arrear'
        $this->listsForFields['recoveryComponents'] = \App\Models\Hrms\SalaryComponentsEmployee::where('employee_id', $employeeId)
            ->whereHas('salary_component', function ($q) {
                $q->where('component_type', 'arrear');
            })
            ->with('salary_component')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->salary_component->title => $item->salary_component->title];
            })
            ->toArray();
    }

    protected function calculateInstallmentAmount()
    {
        if ($this->amount && $this->installments && $this->installments > 0) {
            $this->installment_amount = round($this->amount / $this->installments, 2);
        } else {
            $this->installment_amount = null;
        }
    }

    public function addAdvanceItem()
    {
        $this->advanceItems[] = ['disburse_salary_component' => '', 'recovery_salary_component' => '', 'amount' => null];
    }

    public function removeAdvanceItem($index)
    {
        if (isset($this->advanceItems[$index])) {
            unset($this->advanceItems[$index]);
            $this->advanceItems = array_values($this->advanceItems);
        }
    }

    public function availableDisburseComponents(int $currentIndex): array
    {
        $allComponents = $this->listsForFields['disburseComponents'] ?? [];
        $selectedComponents = collect($this->advanceItems)
            ->pluck('disburse_salary_component')
            ->filter()
            ->values()
            ->toArray();

        $currentSelected = $this->advanceItems[$currentIndex]['disburse_salary_component'] ?? null;
        $selectedOtherRows = array_values(array_filter(
            $selectedComponents,
            fn($component) => $component !== $currentSelected
        ));

        return array_filter(
            $allComponents,
            function ($title, $component) use ($selectedOtherRows) {
                return !in_array($component, $selectedOtherRows, true);
            },
            ARRAY_FILTER_USE_BOTH
        );
    }

    public function availableRecoveryComponents(int $currentIndex): array
    {
        $allComponents = $this->listsForFields['recoveryComponents'] ?? [];
        $selectedComponents = collect($this->advanceItems)
            ->pluck('recovery_salary_component')
            ->filter()
            ->values()
            ->toArray();

        $currentSelected = $this->advanceItems[$currentIndex]['recovery_salary_component'] ?? null;
        $selectedOtherRows = array_values(array_filter(
            $selectedComponents,
            fn($component) => $component !== $currentSelected
        ));

        return array_filter(
            $allComponents,
            function ($title, $component) use ($selectedOtherRows) {
                return !in_array($component, $selectedOtherRows, true);
            },
            ARRAY_FILTER_USE_BOTH
        );
    }

    public function saveAdvance()
    {
        try {
            $this->validate([
                'selectedEmployee' => 'required|exists:employees,id',
                'advance_date' => 'required|date',
                'installments' => 'required|integer|min:1',
                'remarks' => 'nullable|string|max:1000',
                'disburse_payroll_slot_id' => 'nullable|exists:payroll_slots,id',
                'recovery_wef_payroll_slot_id' => 'nullable|exists:payroll_slots,id',
                'additional_rule_remarks' => 'nullable|string',
                'advanceItems' => 'required|array|min:1',
                'advanceItems.*.disburse_salary_component' => 'required|string|max:255',
                'advanceItems.*.recovery_salary_component' => 'required|string|max:255',
                'advanceItems.*.amount' => 'required|numeric|min:1',
            ]);
            // Ensure disburse components are unique per submission
            $disburseComponents = array_map(fn($i) => $i['disburse_salary_component'], $this->advanceItems);
            if (count($disburseComponents) !== count(array_unique($disburseComponents))) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'advanceItems' => ['Duplicate disburse components are not allowed.']
                ]);
            }
            // Ensure recovery components are unique per submission
            $recoveryComponents = array_map(fn($i) => $i['recovery_salary_component'], $this->advanceItems);
            if (count($recoveryComponents) !== count(array_unique($recoveryComponents))) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'advanceItems' => ['Duplicate recovery components are not allowed.']
                ]);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            $messages = collect($e->validator->errors()->all())->implode("<br>");
            Flux::toast(
                variant: 'error',
                heading: 'Validation Error',
                text: $messages,
            );
            return;
        }

        try {
            foreach ($this->advanceItems as $item) {
                $amount = $item['amount'];
                $perInstallment = $this->installments > 0 ? round($amount / $this->installments, 2) : null;
                SalaryAdvance::create([
                    'firm_id' => Session::get('firm_id'),
                    'employee_id' => $this->selectedEmployee,
                    'advance_date' => $this->advance_date,
                    'amount' => $amount,
                    'installments' => $this->installments,
                    'installment_amount' => $perInstallment,
                    'advance_status' => 'pending',
                    'remarks' => $this->remarks,
                    'disburse_salary_component' => $item['disburse_salary_component'],
                    'recovery_salary_component' => $item['recovery_salary_component'],
                    'disburse_payroll_slot_id' => $this->disburse_payroll_slot_id,
                    'recovery_wef_payroll_slot_id' => $this->recovery_wef_payroll_slot_id,
                    'additional_rule_remarks' => $this->additional_rule_remarks,
                ]);
            }
            Flux::toast(
                variant: 'success',
                heading: 'Success',
                text: 'Salary advances created successfully.',
            );
            $this->closeAdvanceModal();
        } catch (\Exception $e) {
            Flux::toast(
                variant: 'error',
                heading: 'Error',
                text: 'Failed to create salary advances: ' . $e->getMessage(),
            );
        }
    }

    public function editAdvance($id)
    {
        $advance = \App\Models\Hrms\SalaryAdvance::findOrFail($id);
        $this->selectedEmployee = $advance->employee_id;
        $this->advance_date = $advance->advance_date ? $advance->advance_date->format('Y-m-d') : null;
        $this->amount = $advance->amount;
        $this->installments = $advance->installments;
        $this->installment_amount = $advance->installment_amount;
        $this->remarks = $advance->remarks;
        $this->disburse_salary_component = $advance->disburse_salary_component;
        $this->recovery_salary_component = $advance->recovery_salary_component;
        $this->disburse_payroll_slot_id = $advance->disburse_payroll_slot_id;
        $this->recovery_wef_payroll_slot_id = $advance->recovery_wef_payroll_slot_id;
        $this->additional_rule_remarks = $advance->additional_rule_remarks;
        $this->isEditing = true;
        $this->editingId = $advance->id;
        $this->showAdvanceModal = true;
        $this->loadComponentLists($advance->employee_id);
    }

    #[Computed]
    public function list()
    {
        $advances = SalaryAdvance::query()
            ->where('firm_id', Session::get('firm_id'))
            ->when($this->filters['employee_id'], fn($query, $value) =>
                $query->where('employee_id', $value))
            ->when($this->filters['advance_status'], fn($query, $value) =>
                $query->where('advance_status', $value))
            ->with(['employee'])
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);
        return $advances;
    }

    public function removeAdvance($advanceId)
    {
        try {
            $advance = SalaryAdvance::findOrFail($advanceId);
            $advance->delete();
            Flux::toast(
                variant: 'success',
                heading: 'Success',
                text: 'Salary advance removed successfully.',
            );
        } catch (\Exception $e) {
            Flux::toast(
                variant: 'error',
                heading: 'Error',
                text: 'Failed to remove salary advance: ' . $e->getMessage(),
            );
        }
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Payroll/blades/salary-advances.blade.php'));
    }
} 