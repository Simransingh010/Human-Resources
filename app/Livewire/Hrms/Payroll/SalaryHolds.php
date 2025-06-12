<?php

namespace App\Livewire\Hrms\Payroll;

use App\Models\Hrms\SalaryHold;
use App\Models\Hrms\Employee;
use App\Models\Hrms\PayrollSlot;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Session;
use Flux;

class SalaryHolds extends Component
{
    use WithPagination;

    public $perPage = 10;
    public $sortBy = 'id';
    public $sortDirection = 'desc';

    // Modal Properties
    public $showHoldModal = false;
    public $selectedEmployee = null;
    public $selectedPayrollSlots = [];
    public $remarks = '';

    // Field configuration for form and table
    public array $fieldConfig = [
        'employee_name' => ['label' => 'Employee', 'type' => 'text'],
        'payroll_period' => ['label' => 'Payroll Period', 'type' => 'text'],
        'remarks' => ['label' => 'Remarks', 'type' => 'text'],
        'created_at' => ['label' => 'Created At', 'type' => 'datetime']
    ];

    // Filter fields configuration
    public array $filterFields = [
        'employee_id' => ['label' => 'Employee', 'type' => 'select', 'listKey' => 'employees'],
        'payroll_slot_id' => ['label' => 'Payroll Period', 'type' => 'select', 'listKey' => 'payrollSlots']
    ];

    public array $listsForFields = [];
    public array $filters = [];
    public array $visibleFields = [];
    public array $visibleFilterFields = [];

    public $payrollSlotId;
    public $salaryHolds;

    public function mount($payrollSlotId)
    {
        $this->payrollSlotId = $payrollSlotId;
        $this->loadSalaryHolds();
        $this->initListsForFields();

        // Set default visible fields
        $this->visibleFields = [
            'employee_name',
            'payroll_period',
            'remarks',
            'created_at'
        ];

        $this->visibleFilterFields = [
            'employee_id',
            'payroll_slot_id'
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

        // Get payroll slots for dropdown
        $this->listsForFields['payrollSlots'] = PayrollSlot::where('firm_id', Session::get('firm_id'))
            ->orderBy('from_date', 'asc')
            ->get()
            ->mapWithKeys(function ($slot) {
                return [$slot->id => $slot->from_date->format('jS F Y') . ' to ' . $slot->to_date->format('jS F Y')];
            })
            ->toArray();
    }

    protected function loadSalaryHolds()
    {
        $this->salaryHolds = SalaryHold::where('firm_id', Session::get('firm_id'))
            ->where('payroll_slot_id', $this->payrollSlotId)
            ->with(['employee'])
            ->get();
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

    public function openHoldModal()
    {
        $this->reset(['selectedEmployee', 'selectedPayrollSlots', 'remarks']);
        $this->showHoldModal = true;
    }

    public function closeHoldModal()
    {
        $this->showHoldModal = false;
        $this->reset(['selectedEmployee', 'selectedPayrollSlots', 'remarks']);
    }

    public function holdSalary()
    {
        try {
            $this->validate([
                'selectedEmployee' => 'required|exists:employees,id',
                'selectedPayrollSlots' => 'required|array|min:1',
                'selectedPayrollSlots.*' => 'exists:payroll_slots,id',
                'remarks' => 'nullable|string|max:1000'
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
            $firmId = Session::get('firm_id');
            $created = 0;
            foreach ($this->selectedPayrollSlots as $payrollSlotId) {
                // Check if salary is already held for this employee and payroll slot
                $existingHold = SalaryHold::where('employee_id', $this->selectedEmployee)
                    ->where('payroll_slot_id', $payrollSlotId)
                    ->where('firm_id', $firmId)
                    ->first();
                if (!$existingHold) {
                    SalaryHold::create([
                        'firm_id' => $firmId,
                        'employee_id' => $this->selectedEmployee,
                        'payroll_slot_id' => $payrollSlotId,
                        'remarks' => $this->remarks
                    ]);
                    $created++;
                }
            }
            if ($created > 0) {
                Flux::toast(
                    variant: 'success',
                    heading: 'Success',
                    text: 'Salary hold(s) created successfully.',
                );
            } else {
                Flux::toast(
                    variant: 'info',
                    heading: 'Info',
                    text: 'No new salary holds were created (may already exist).',
                );
            }
            $this->closeHoldModal();
        } catch (\Exception $e) {
            Flux::toast(
                variant: 'error',
                heading: 'Error',
                text: 'Failed to create salary hold: ' . $e->getMessage(),
            );
        }
    }

    public function removeHold($holdId)
    {
        try {
            $hold = SalaryHold::findOrFail($holdId);
            $hold->delete();

            Flux::toast(
                variant: 'success',
                heading: 'Success',
                text: 'Salary hold removed successfully.',
            );
        } catch (\Exception $e) {
            Flux::toast(
                variant: 'error',
                heading: 'Error',
                text: 'Failed to remove salary hold: ' . $e->getMessage(),
            );
        }
    }

    #[Computed]
    public function list()
    {
        // Aggregate by employee and payroll period (grouped)
        $holds = SalaryHold::query()
            ->where('firm_id', Session::get('firm_id'))
            ->when($this->filters['employee_id'], fn($query, $value) =>
                $query->where('employee_id', $value))
            ->when($this->filters['payroll_slot_id'], fn($query, $value) =>
                $query->where('payroll_slot_id', $value))
            ->with(['employee', 'payrollSlot'])
            ->orderBy($this->sortBy, $this->sortDirection)
            ->get()
            ->groupBy(function ($hold) {
                return $hold->employee_id . '-' . $hold->payroll_slot_id;
            })
            ->map(function ($group) {
                // Aggregate remarks (join with ;)
                $first = $group->first();
                $remarks = $group->pluck('remarks')->filter()->unique()->implode('; ');
                $first->remarks = $remarks;
                return $first;
            })
            ->values();

        // Paginate manually
        $page = request()->get('page', 1);
        $perPage = $this->perPage;
        $items = $holds->slice(($page - 1) * $perPage, $perPage);
        return new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $holds->count(),
            $perPage,
            $page,
            ['path' => request()->url()]
        );
    }

    public function render()
    {
        return view('livewire.hrms.payroll.salary-holds');
    }
} 