<?php

namespace App\Livewire\Hrms\Offboard;

use App\Models\Hrms\FinalSettlement;
use App\Models\Hrms\EmployeeExit;
use App\Models\Hrms\Employee;
use App\Models\Hrms\PayrollSlot;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;
use Flux;

class FinalSettlements extends Component
{
    use WithPagination;

    public $perPage = 10;
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $isEditing = false;

    // Field configuration for form and table
    public array $fieldConfig = [
        'exit_id' => ['label' => 'Exit', 'type' => 'select', 'listKey' => 'exits'],
        'employee_id' => ['label' => 'Employee', 'type' => 'select', 'listKey' => 'employees'],
        'settlement_date' => ['label' => 'Settlement Date', 'type' => 'date'],
        'disburse_payroll_slot_id' => ['label' => 'Disburse Payroll Slot', 'type' => 'select', 'listKey' => 'payroll_slots'],
        'fnf_earning_amount' => ['label' => 'Total Earning', 'type' => 'number'],
        'fnf_deduction_amount' => ['label' => 'Total Deduction', 'type' => 'number'],
        'full_final_status' => ['label' => 'Status', 'type' => 'select', 'listKey' => 'final_statuses'],
        'remarks' => ['label' => 'Remarks', 'type' => 'textarea'],
        'additional_rule' => ['label' => 'Additional Rule', 'type' => 'textarea'],
    ];

    // Filters for list
    public array $filterFields = [
        'employee_id' => ['label' => 'Employee', 'type' => 'select', 'listKey' => 'employees'],
        'exit_id' => ['label' => 'Exit', 'type' => 'select', 'listKey' => 'exits'],
        'disburse_payroll_slot_id' => ['label' => 'Payroll Slot', 'type' => 'select', 'listKey' => 'payroll_slots'],
        'full_final_status' => ['label' => 'Status', 'type' => 'text'],
    ];

    public array $listsForFields = [];
    public array $filters = [];
    public array $visibleFields = [];
    public array $visibleFilterFields = [];

    public $formData = [
        'id' => null,
        'firm_id' => null,
        'exit_id' => '',
        'employee_id' => '',
        'settlement_date' => '',
        'disburse_payroll_slot_id' => '',
        'fnf_earning_amount' => 0,
        'fnf_deduction_amount' => 0,
        'full_final_status' => 'pending',
        'remarks' => '',
        'additional_rule' => '',
    ];

    public function mount(): void
    {
        $this->initListsForFields();

        $this->visibleFields = ['employee_id', 'settlement_date', 'disburse_payroll_slot_id', 'fnf_earning_amount', 'fnf_deduction_amount', 'full_final_status'];
        $this->visibleFilterFields = ['employee_id', 'exit_id', 'disburse_payroll_slot_id'];

        $this->filters = array_fill_keys(array_keys($this->filterFields), '');

        if (empty($this->formData['settlement_date'])) {
            $this->formData['settlement_date'] = date('Y-m-d');
        }
    }

    protected function initListsForFields(): void
    {
        $firmId = Session::get('firm_id');
        $cacheKey = "final_settlements_lists_{$firmId}";

        $this->listsForFields = Cache::remember($cacheKey, 300, function () use ($firmId) {
            return [
                'exits' => EmployeeExit::where('firm_id', $firmId)
                    ->with('employee')
                    ->orderByDesc('id')
                    ->get()
                    ->map(function ($exit) {
                        $label = "Exit #{$exit->id}";
                        if ($exit->employee) {
                            $label .= " - {$exit->employee->fname} {$exit->employee->lname}";
                        }
                        return [
                            'id' => $exit->id,
                            'label' => $label,
                            'employee_id' => $exit->employee_id,
                        ];
                    })
                    ->toArray(),
                'employees' => Employee::where('firm_id', $firmId)
                    ->where(function ($q) {
                        $q->whereNull('is_inactive')->orWhere('is_inactive', false);
                    })
                    ->orderBy('fname')
                    ->get()
                    ->mapWithKeys(function ($employee) {
                        $name = trim("{$employee->fname} {$employee->lname}") ?: "Employee #{$employee->id}";
                        return [$employee->id => $name];
                    })
                    ->toArray(),
                'payroll_slots' => PayrollSlot::where('firm_id', $firmId)
                    ->orderByDesc('id')
                    ->pluck('title', 'id')
                    ->toArray(),
                'final_statuses' => FinalSettlement::full_final_status_select,
            ];
        });
    }

    public function applyFilters(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->filters = array_fill_keys(array_keys($this->filterFields), '');
        $this->resetPage();
    }

    public function toggleColumn(string $field): void
    {
        if (in_array($field, $this->visibleFields)) {
            $this->visibleFields = array_filter($this->visibleFields, fn ($f) => $f !== $field);
        } else {
            $this->visibleFields[] = $field;
        }
    }

    public function toggleFilterColumn(string $field): void
    {
        if (in_array($field, $this->visibleFilterFields)) {
            $this->visibleFilterFields = array_filter($this->visibleFilterFields, fn ($f) => $f !== $field);
        } else {
            $this->visibleFilterFields[] = $field;
        }
    }

    // Auto-select employee when exit is chosen
    public function updatedFormDataExitId($value): void
    {
        if (!$value) {
            return;
        }
        $exit = collect($this->listsForFields['exits'] ?? [])->firstWhere('id', (int) $value);
        if ($exit && !empty($exit['employee_id'])) {
            $this->formData['employee_id'] = $exit['employee_id'];
        }
    }

    #[Computed]
    public function list()
    {
        $firmId = Session::get('firm_id');

        return FinalSettlement::query()
            ->with(['employee', 'exit', 'disbursePayrollSlot'])
            ->where('firm_id', $firmId)
            ->when($this->filters['employee_id'], fn ($q, $v) => $q->where('employee_id', $v))
            ->when($this->filters['exit_id'], fn ($q, $v) => $q->where('exit_id', $v))
            ->when($this->filters['disburse_payroll_slot_id'], fn ($q, $v) => $q->where('disburse_payroll_slot_id', $v))
            ->when($this->filters['full_final_status'], fn ($q, $v) => $q->where('full_final_status', 'like', "%{$v}%"))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);
    }

    protected function rules(): array
    {
        return [
            'formData.exit_id' => 'required|exists:employee_exits,id',
            'formData.employee_id' => 'required|exists:employees,id',
            'formData.settlement_date' => 'required|date',
            'formData.disburse_payroll_slot_id' => 'nullable|exists:payroll_slots,id',
            'formData.fnf_earning_amount' => 'required|numeric|min:0',
            'formData.fnf_deduction_amount' => 'required|numeric|min:0',
            'formData.full_final_status' => 'nullable|string|max:255',
            'formData.remarks' => 'nullable|string|max:1000',
            'formData.additional_rule' => 'nullable|string|max:1000',
        ];
    }

    public function store(): void
    {
        $validated = $this->validate();

        $data = collect($validated['formData'])
            ->map(fn ($v) => $v === '' ? null : $v)
            ->toArray();

        $data['firm_id'] = Session::get('firm_id');

        if ($this->isEditing) {
            $record = FinalSettlement::findOrFail($this->formData['id']);
            $record->update($data);
            // Ensure totals reflect items regardless of manual edits
            $record->recomputeTotals();
            $toastMsg = 'Final settlement updated successfully';
        } else {
            $record = FinalSettlement::create($data);
            // Safety: initialize totals from existing items if any
            $record->recomputeTotals();
            $toastMsg = 'Final settlement created successfully';
        }

        // Clear caches for this screen
        $this->clearCache();
        // Also clear FinalSettlementItems lists cache as it uses final settlements in dropdown
        $firmId = Session::get('firm_id');
        Cache::forget("final_settlement_items_lists_{$firmId}");

        $this->resetForm();
        $this->modal('mdl-final-settlement')->close();

        Flux::toast(
            variant: 'success',
            heading: 'Changes saved.',
            text: $toastMsg,
        );
    }

    public function edit($id): void
    {
        $this->isEditing = true;
        $record = FinalSettlement::findOrFail($id);
        $this->formData = $record->toArray();
        $this->modal('mdl-final-settlement')->show();
    }

    public function delete($id): void
    {
        $record = FinalSettlement::findOrFail($id);
        $record->delete();

        // Clear caches for this screen and dependent items screen
        $this->clearCache();
        $firmId = Session::get('firm_id');
        Cache::forget("final_settlement_items_lists_{$firmId}");

        Flux::toast(
            variant: 'success',
            heading: 'Record deleted.',
            text: 'Final settlement deleted successfully.'
        );
    }

    protected function clearCache(): void
    {
        $firmId = Session::get('firm_id');
        Cache::forget("final_settlements_lists_{$firmId}");
    }

    public function resetForm(): void
    {
        $this->reset(['formData']);
        $this->formData['settlement_date'] = date('Y-m-d');
        $this->formData['fnf_earning_amount'] = 0;
        $this->formData['fnf_deduction_amount'] = 0;
        $this->formData['full_final_status'] = 'pending';
        $this->isEditing = false;
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Offboard/blades/final-settlements.blade.php'));
    }
}
