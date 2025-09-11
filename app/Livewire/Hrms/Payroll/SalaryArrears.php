<?php

namespace App\Livewire\Hrms\Payroll;

use App\Models\Hrms\SalaryArrear;
use App\Models\Hrms\Employee;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Session;
use Flux;

class SalaryArrears extends Component
{
    use WithPagination;

    public $perPage = 10;
    public $sortBy = 'id';
    public $sortDirection = 'desc';

    // Modal Properties
    public $selectedEmployee = null;
    public $effective_from = null;
    public $effective_to = null;
    public $total_amount = null;
    public $paid_amount = null;
    public $installments = null;
    public $installment_amount = null;
    public $arrear_status = null;
    public $salary_component_id = null;
    public $disburse_wef_payroll_slot_id = null;
    public $additional_rule = null;
    public $remarks = '';
    public $isEditing = false;
    public $editingId = null;
    public $arrearItems = [];

    // Field configuration for form and table
    public array $fieldConfig = [
        'employee_name' => ['label' => 'Employee', 'type' => 'text'],
        'salary_component_id' => ['label' => 'Component', 'type' => 'select', 'listKey' => 'components'],
        'effective_from' => ['label' => 'Effective From', 'type' => 'date'],
        'effective_to' => ['label' => 'Effective To', 'type' => 'date'],
        'total_amount' => ['label' => 'Total Amount', 'type' => 'number'],
        'paid_amount' => ['label' => 'Paid Amount', 'type' => 'number'],
        'installments' => ['label' => 'Installments', 'type' => 'number'],
        'installment_amount' => ['label' => 'Installment Amount', 'type' => 'number'],
        'arrear_status' => ['label' => 'Status', 'type' => 'select', 'listKey' => 'statuses'],
        'disburse_wef_payroll_slot_id' => ['label' => 'Disburse WEF Payroll Slot', 'type' => 'select', 'listKey' => 'payrollSlots'],
        'additional_rule' => ['label' => 'Additional Rule', 'type' => 'textarea'],
        'remarks' => ['label' => 'Remarks', 'type' => 'textarea'],
        'created_at' => ['label' => 'Created At', 'type' => 'datetime']
    ];

    // Filter fields configuration
    public array $filterFields = [
        'employee_id' => ['label' => 'Employee', 'type' => 'select', 'listKey' => 'employees'],
        'arrear_status' => ['label' => 'Status', 'type' => 'select', 'listKey' => 'statuses']
    ];

    public array $listsForFields = [];
    public array $filters = [];
    public array $visibleFields = [];
    public array $visibleFilterFields = [];

    public function mount()
    {
        $this->initListsForFields();
        $this->visibleFields = [
            'employee_name',
            'salary_component_id',
            'effective_from',
            'effective_to',
            'total_amount',
//            'paid_amount',
            'installments',
            'installment_amount',
            'arrear_status',
            'remarks',
//            'created_at'
        ];
        $this->visibleFilterFields = [
            'employee_id',
            'arrear_status'
        ];
        $this->filters = array_fill_keys(array_keys($this->filterFields), '');
        // If an employee is preselected, load their components and payroll slots
        if ($this->selectedEmployee) {
            $this->loadComponentList($this->selectedEmployee);
            $this->loadPayrollSlotList($this->selectedEmployee);
        }
    }

    protected function initListsForFields(): void
    {
        $this->listsForFields['employees'] = Employee::where('firm_id', Session::get('firm_id'))
            ->where('is_inactive', false)
            ->get()
            ->mapWithKeys(function ($employee) {
                return [$employee->id => $employee->fname . ' ' . $employee->lname];
            })
            ->toArray();
        $this->listsForFields['statuses'] = SalaryArrear::$arrearStatuses;
        $this->listsForFields['payrollSlots'] = [];
        $this->listsForFields['components'] = [];
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

    public function openArrearModal()
    {
        $this->reset(['selectedEmployee', 'salary_component_id', 'effective_from', 'effective_to', 'total_amount', 'paid_amount', 'installments', 'installment_amount', 'arrear_status', 'disburse_wef_payroll_slot_id', 'additional_rule', 'remarks']);
        $this->isEditing = false;
        $this->editingId = null;
        $this->arrearItems = [
            ['salary_component_id' => '', 'amount' => null]
        ];
        $this->modal('mdl-salary-arrear')->show();
    }

    public function closeArrearModal()
    {
        $this->reset(['selectedEmployee', 'salary_component_id', 'effective_from', 'effective_to', 'total_amount', 'paid_amount', 'installments', 'installment_amount', 'arrear_status', 'disburse_wef_payroll_slot_id', 'additional_rule', 'remarks']);
        $this->isEditing = false;
        $this->editingId = null;
        $this->arrearItems = [];
        $this->modal('mdl-salary-arrear')->close();
    }

    public function updatedTotalAmount($value)
    {
        $this->calculateInstallmentAmount();
    }

    public function updatedInstallments($value)
    {
        $this->calculateInstallmentAmount();
    }

    public function updatedSelectedEmployee($value)
    {
        $this->loadComponentList($value);
        $this->loadPayrollSlotList($value);
    }

    protected function loadComponentList($employeeId)
    {
        $this->listsForFields['components'] = 
            \App\Models\Hrms\SalaryComponentsEmployee::where('employee_id', $employeeId)
                ->with('salary_component')
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->salary_component->id => $item->salary_component->title];
                })
                ->toArray();
    }

    protected function loadPayrollSlotList($employeeId)
    {
        $employee = Employee::find($employeeId);
        if ($employee) {
            $this->listsForFields['payrollSlots'] = $employee->payroll_slots()
                ->orderBy('from_date', 'desc')
                ->get()
                ->mapWithKeys(function ($slot) {
                    return [$slot->id => $slot->from_date->format('jS F Y') . ' to ' . $slot->to_date->format('jS F Y')];
                })
                ->toArray();
        } else {
            $this->listsForFields['payrollSlots'] = [];
        }
    }

    protected function calculateInstallmentAmount()
    {
        if ($this->total_amount && $this->installments && $this->installments > 0) {
            $this->installment_amount = round($this->total_amount / $this->installments, 2);
        } else {
            $this->installment_amount = null;
        }
    }

    public function availableComponents(int $currentIndex): array
    {
        $allComponents = $this->listsForFields['components'] ?? [];
        $selectedComponentIds = collect($this->arrearItems)
            ->pluck('salary_component_id')
            ->filter()
            ->values()
            ->toArray();

        $currentSelected = $this->arrearItems[$currentIndex]['salary_component_id'] ?? null;
        $selectedOtherRows = array_values(array_filter(
            $selectedComponentIds,
            fn($id) => (string)$id !== (string)$currentSelected
        ));

        // Filter out components chosen in other rows
        return array_filter(
            $allComponents,
            function ($title, $id) use ($selectedOtherRows) {
                return !in_array((string)$id, array_map('strval', $selectedOtherRows), true);
            },
            ARRAY_FILTER_USE_BOTH
        );
    }

    public function addArrearItem()
    {
        $this->arrearItems[] = ['salary_component_id' => '', 'amount' => null];
    }

    public function removeArrearItem($index)
    {
        if (isset($this->arrearItems[$index])) {
            unset($this->arrearItems[$index]);
            $this->arrearItems = array_values($this->arrearItems);
        }
    }

    public function saveArrear()
    {
        try {
            $this->validate([
                'selectedEmployee' => 'required|exists:employees,id',
                'effective_from' => 'required|date',
                'effective_to' => 'required|date|after_or_equal:effective_from',
                'paid_amount' => 'nullable|numeric|min:0',
                'installments' => 'required|integer|min:1',
                'arrear_status' => 'required|string',
                'disburse_wef_payroll_slot_id' => 'nullable|integer',
                'additional_rule' => 'nullable|string',
                'remarks' => 'nullable|string|max:1000',
                'arrearItems' => 'required|array|min:1',
                'arrearItems.*.salary_component_id' => 'required|exists:salary_components,id',
                'arrearItems.*.amount' => 'required|numeric|min:1',
            ]);
            // Ensure components are unique per submission
            $componentIds = array_map(fn($i) => $i['salary_component_id'], $this->arrearItems);
            if (count($componentIds) !== count(array_unique($componentIds))) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'arrearItems' => ['Duplicate components are not allowed.']
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
            foreach ($this->arrearItems as $item) {
                $amount = $item['amount'];
                $perInstallment = $this->installments > 0 ? round($amount / $this->installments, 2) : null;
                SalaryArrear::create([
                    'firm_id' => Session::get('firm_id'),
                    'employee_id' => $this->selectedEmployee,
                    'salary_component_id' => $item['salary_component_id'],
                    'effective_from' => $this->effective_from,
                    'effective_to' => $this->effective_to,
                    'total_amount' => $amount,
                    'paid_amount' => 0,
                    'installments' => $this->installments,
                    'installment_amount' => $perInstallment,
                    'arrear_status' => $this->arrear_status ?? 'pending',
                    'disburse_wef_payroll_slot_id' => $this->disburse_wef_payroll_slot_id,
                    'additional_rule' => $this->additional_rule,
                    'remarks' => $this->remarks,
                ]);
            }
            Flux::toast(
                variant: 'success',
                heading: 'Success',
                text: 'Salary arrears created successfully.',
            );
            $this->closeArrearModal();
        } catch (\Exception $e) {
            Flux::toast(
                variant: 'error',
                heading: 'Error',
                text: 'Failed to create salary arrears: ' . $e->getMessage(),
            );
        }
    }

    public function edit($id)
    {
        $this->isEditing = true;
        $arrear = SalaryArrear::findOrFail($id);
        $this->editingId = $arrear->id;
        $this->selectedEmployee = $arrear->employee_id;
        $this->salary_component_id = $arrear->salary_component_id;
        $this->effective_from = $arrear->effective_from ? $arrear->effective_from->format('Y-m-d') : null;
        $this->effective_to = $arrear->effective_to ? $arrear->effective_to->format('Y-m-d') : null;
        $this->total_amount = $arrear->total_amount;
        $this->paid_amount = $arrear->paid_amount;
        $this->installments = $arrear->installments;
        $this->installment_amount = $arrear->installment_amount;
        $this->arrear_status = $arrear->arrear_status;
        $this->disburse_wef_payroll_slot_id = $arrear->disburse_wef_payroll_slot_id;
        $this->additional_rule = $arrear->additional_rule;
        $this->remarks = $arrear->remarks;
        $this->loadComponentList($arrear->employee_id);
        $this->loadPayrollSlotList($arrear->employee_id);
        $this->modal('mdl-salary-arrear')->show();
    }

    #[Computed]
    public function list()
    {
        $arrears = SalaryArrear::query()
            ->where('firm_id', Session::get('firm_id'))
            ->when($this->filters['employee_id'], fn($query, $value) =>
                $query->where('employee_id', $value))
            ->when($this->filters['arrear_status'], fn($query, $value) =>
                $query->where('arrear_status', $value))
            ->with(['employee', 'salary_component'])
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);
        return $arrears;
    }

    public function removeArrear($arrearId)
    {
        try {
            $arrear = SalaryArrear::findOrFail($arrearId);
            $arrear->delete();
            Flux::toast(
                variant: 'success',
                heading: 'Success',
                text: 'Salary arrear removed successfully.',
            );
        } catch (\Exception $e) {
            Flux::toast(
                variant: 'error',
                heading: 'Error',
                text: 'Failed to remove salary arrear: ' . $e->getMessage(),
            );
        }
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Payroll/blades/salary-arrears.blade.php'));
    }
} 