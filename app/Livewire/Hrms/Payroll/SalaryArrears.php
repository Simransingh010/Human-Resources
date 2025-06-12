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
    public $showArrearModal = false;
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
            'paid_amount',
            'installments',
            'installment_amount',
            'arrear_status',
            'remarks',
            'created_at'
        ];
        $this->visibleFilterFields = [
            'employee_id',
            'arrear_status'
        ];
        $this->filters = array_fill_keys(array_keys($this->filterFields), '');
        // If an employee is preselected, load their components
        if ($this->selectedEmployee) {
            $this->loadComponentList($this->selectedEmployee);
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
        $this->listsForFields['payrollSlots'] = \App\Models\Hrms\PayrollSlot::where('firm_id', Session::get('firm_id'))
            ->orderBy('from_date', 'desc')
            ->get()
            ->mapWithKeys(function ($slot) {
                return [$slot->id => $slot->from_date->format('jS F Y') . ' to ' . $slot->to_date->format('jS F Y')];
            })
            ->toArray();
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
        $this->showArrearModal = true;
    }

    public function closeArrearModal()
    {
        $this->showArrearModal = false;
        $this->reset(['selectedEmployee', 'salary_component_id', 'effective_from', 'effective_to', 'total_amount', 'paid_amount', 'installments', 'installment_amount', 'arrear_status', 'disburse_wef_payroll_slot_id', 'additional_rule', 'remarks']);
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

    protected function calculateInstallmentAmount()
    {
        if ($this->total_amount && $this->installments && $this->installments > 0) {
            $this->installment_amount = round($this->total_amount / $this->installments, 2);
        } else {
            $this->installment_amount = null;
        }
    }

    public function saveArrear()
    {
        try {
            $this->validate([
                'selectedEmployee' => 'required|exists:employees,id',
                'salary_component_id' => 'required|exists:salary_components,id',
                'effective_from' => 'required|date',
                'effective_to' => 'required|date|after_or_equal:effective_from',
                'total_amount' => 'required|numeric|min:1',
                'paid_amount' => 'nullable|numeric|min:0',
                'installments' => 'required|integer|min:1',
                'arrear_status' => 'required|string',
                'disburse_wef_payroll_slot_id' => 'nullable|exists:payroll_slots,id',
                'additional_rule' => 'nullable|string',
                'remarks' => 'nullable|string|max:1000',
            ]);
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
            SalaryArrear::create([
                'firm_id' => Session::get('firm_id'),
                'employee_id' => $this->selectedEmployee,
                'salary_component_id' => $this->salary_component_id,
                'effective_from' => $this->effective_from,
                'effective_to' => $this->effective_to,
                'total_amount' => $this->total_amount,
                'paid_amount' => $this->paid_amount,
                'installments' => $this->installments,
                'installment_amount' => $this->installment_amount,
                'arrear_status' => $this->arrear_status ?? 'pending',
                'disburse_wef_payroll_slot_id' => $this->disburse_wef_payroll_slot_id,
                'additional_rule' => $this->additional_rule,
                'remarks' => $this->remarks,
            ]);
            Flux::toast(
                variant: 'success',
                heading: 'Success',
                text: 'Salary arrear created successfully.',
            );
            $this->closeArrearModal();
        } catch (\Exception $e) {
            Flux::toast(
                variant: 'error',
                heading: 'Error',
                text: 'Failed to create salary arrear: ' . $e->getMessage(),
            );
        }
    }

    public function editArrear($id)
    {
        $arrear = SalaryArrear::findOrFail($id);
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
        $this->isEditing = true;
        $this->editingId = $arrear->id;
        $this->showArrearModal = true;
        $this->loadComponentList($arrear->employee_id);
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