<?php

namespace App\Livewire\Hrms\Leave;

use App\Models\Hrms\EmpLeaveBalance;
use App\Models\Hrms\Employee;
use App\Models\Hrms\LeaveType;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Session;
use Flux;

class EmpLeaveBalances extends Component
{
    use WithPagination;
    public $selectedId = null;
    public $perPage = 10;
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $isEditing = false;

    // Field configuration for form and table
    public array $fieldConfig = [
        'employee_id' => ['label' => 'Employee', 'type' => 'select', 'listKey' => 'employees'],
        'leave_type_id' => ['label' => 'Leave Type', 'type' => 'select', 'listKey' => 'leave_types'],
        'period_start' => ['label' => 'Period Start', 'type' => 'date'],
        'period_end' => ['label' => 'Period End', 'type' => 'date'],
        'allocated_days' => ['label' => 'Allocated Days', 'type' => 'number'],
        'consumed_days' => ['label' => 'Consumed Days', 'type' => 'number'],
        'carry_forwarded_days' => ['label' => 'Carry Forwarded Days', 'type' => 'number'],
        'lapsed_days' => ['label' => 'Lapsed Days', 'type' => 'number'],
        'balance' => ['label' => 'Balance', 'type' => 'number'],
    ];

    // Filter fields configuration
    public array $filterFields = [
        'employee_id' => ['label' => 'Employee', 'type' => 'select', 'listKey' => 'employees'],
        'leave_type_id' => ['label' => 'Leave Type', 'type' => 'select', 'listKey' => 'leave_types'],
        'period_start' => ['label' => 'Period Start', 'type' => 'date'],
        'period_end' => ['label' => 'Period End', 'type' => 'date'],
        'created_at' => ['label' => 'Created At', 'type' => 'date'],
        'updated_at' => ['label' => 'Updated At', 'type' => 'date'],
    ];

    public array $listsForFields = [];
    public array $filters = [];
    public array $visibleFields = [];
    public array $visibleFilterFields = [];
    public $viewMode = 'accordion'; // 'table' or 'accordion'

    public $formData = [
        'id' => null,
        'firm_id' => null,
        'employee_id' => null,
        'leave_type_id' => null,
        'period_start' => '',
        'period_end' => '',
        'allocated_days' => 0,
        'consumed_days' => 0,
        'carry_forwarded_days' => 0,
        'lapsed_days' => 0,
        'balance' => 0,
    ];

    protected function rules()
    {
        return [
            'formData.employee_id' => 'required|integer|exists:employees,id',
            'formData.leave_type_id' => 'required|integer|exists:leave_types,id',
            'formData.period_start' => 'required|date',
            'formData.period_end' => 'required|date|after:formData.period_start',
            'formData.allocated_days' => 'required|numeric|min:0',
            'formData.consumed_days' => 'required|numeric|min:0',
            'formData.carry_forwarded_days' => 'required|numeric|min:0',
            'formData.lapsed_days' => 'required|numeric|min:0',
            'formData.balance' => 'required|numeric',
        ];
    }

    public function mount()
    {
        $this->initListsForFields();
        
        // Set default visible fields
        $this->visibleFields = ['employee_id', 'leave_type_id', 'period_start', 'period_end', 'allocated_days', 'consumed_days', 'balance'];
        $this->visibleFilterFields = ['employee_id', 'leave_type_id', 'period_start', 'period_end'];
        
        // Initialize filters
        $this->filters = array_fill_keys(array_keys($this->filterFields), '');
    }

    protected function initListsForFields(): void
    {
        $this->listsForFields['employees'] = Employee::where('firm_id', session('firm_id'))
            ->pluck('fname', 'id');
        $this->listsForFields['leave_types'] = LeaveType::where('firm_id', session('firm_id'))
            ->pluck('leave_title', 'id');
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
        return EmpLeaveBalance::query()
            ->with(['employee', 'leave_type'])
            ->where('firm_id', Session::get('firm_id'))
            ->when($this->filters['employee_id'], fn($query, $value) => 
                $query->where('employee_id', $value))
            ->when($this->filters['leave_type_id'], fn($query, $value) => 
                $query->where('leave_type_id', $value))
            ->when($this->filters['period_start'], fn($query, $value) => 
                $query->where('period_start', '>=', $value))
            ->when($this->filters['period_end'], fn($query, $value) => 
                $query->where('period_end', '<=', $value))
            ->when($this->filters['created_at'], fn($query, $value) => 
                $query->whereDate('created_at', $value))
            ->when($this->filters['updated_at'], fn($query, $value) => 
                $query->whereDate('updated_at', $value))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);
    }

    public function store()
    {
        $validatedData = $this->validate($this->rules());

        $validatedData['formData'] = collect($validatedData['formData'])
            ->map(fn($val) => $val === '' ? null : $val)
            ->toArray();

        $validatedData['formData']['firm_id'] = session('firm_id');
        
        // Recalculate balance to ensure consistency
        $validatedData['formData']['balance'] = $this->calculateBalance(
            $validatedData['formData']['allocated_days'],
            $validatedData['formData']['consumed_days'],
            $validatedData['formData']['carry_forwarded_days'],
            $validatedData['formData']['lapsed_days']
        );

        if ($this->isEditing) {
            $leaveBalance = EmpLeaveBalance::findOrFail($this->formData['id']);
            $leaveBalance->update($validatedData['formData']);
            $toastMsg = 'Leave balance updated successfully';
        } else {
            EmpLeaveBalance::create($validatedData['formData']);
            $toastMsg = 'Leave balance added successfully';
        }

        $this->resetForm();
        $this->modal('mdl-leave-balance')->close();
        Flux::toast(
            variant: 'success',
            heading: 'Changes saved.',
            text: $toastMsg,
        );
    }

    /**
     * Calculate balance based on the formula
     */
    private function calculateBalance($allocatedDays, $consumedDays, $carryForwardedDays, $lapsedDays)
    {
        return $allocatedDays + $carryForwardedDays - $consumedDays - $lapsedDays;
    }

    /**
     * Recalculate balance for all leave balances
     */
    public function recalculateAllBalances()
    {
        $leaveBalances = EmpLeaveBalance::where('firm_id', session('firm_id'))->get();
        $updatedCount = 0;

        foreach ($leaveBalances as $balance) {
            $calculatedBalance = $this->calculateBalance(
                $balance->allocated_days,
                $balance->consumed_days,
                $balance->carry_forwarded_days,
                $balance->lapsed_days
            );

            if ($calculatedBalance != $balance->balance) {
                $balance->update(['balance' => $calculatedBalance]);
                $updatedCount++;
            }
        }

        Flux::toast(
            variant: 'success',
            heading: 'Balance Recalculation Complete',
            text: "Updated {$updatedCount} leave balance records.",
        );
    }

    public function resetForm()
    {
        $this->reset(['formData']);
        $this->formData['allocated_days'] = 0;
        $this->formData['consumed_days'] = 0;
        $this->formData['carry_forwarded_days'] = 0;
        $this->formData['lapsed_days'] = 0;
        $this->formData['balance'] = 0;
        $this->isEditing = false;
    }

    public function edit($id)
    {
        $this->isEditing = true;
        $leaveBalance = EmpLeaveBalance::findOrFail($id);
        $this->formData = $leaveBalance->toArray();
        $this->formData['period_start'] = $leaveBalance->period_start ? $leaveBalance->period_start->format('Y-m-d') : '';
        $this->formData['period_end'] = $leaveBalance->period_end ? $leaveBalance->period_end->format('Y-m-d') : '';
        $this->modal('mdl-leave-balance')->show();
    }

    public function delete($id)
    {
        // Check if leave balance has related records
        $leaveBalance = EmpLeaveBalance::findOrFail($id);
        if ($leaveBalance->emp_leave_transactions()->count() > 0) {
            Flux::toast(
                variant: 'error',
                heading: 'Cannot Delete',
                text: 'This leave balance has related records and cannot be deleted.',
            );
            return;
        }

        $leaveBalance->delete();
        Flux::toast(
            variant: 'success',
            heading: 'Record Deleted.',
            text: 'Leave balance has been deleted successfully',
        );
    }

    public function showLeaveTransactions($id)
    {
        $this->selectedId = $id;
        $this->modal('leave-transactions')->show();
    }

    #[Computed]
    public function groupedLeaveData()
    {
        $leaveBalances = EmpLeaveBalance::query()
            ->with(['employee', 'leave_type'])
            ->where('firm_id', Session::get('firm_id'))
            ->when($this->filters['employee_id'], fn($query, $value) => 
                $query->where('employee_id', $value))
            ->when($this->filters['leave_type_id'], fn($query, $value) => 
                $query->where('leave_type_id', $value))
            ->when($this->filters['period_start'], fn($query, $value) => 
                $query->where('period_start', '>=', $value))
            ->when($this->filters['period_end'], fn($query, $value) => 
                $query->where('period_end', '<=', $value))
            ->when($this->filters['created_at'], fn($query, $value) => 
                $query->whereDate('created_at', $value))
            ->when($this->filters['updated_at'], fn($query, $value) => 
                $query->whereDate('updated_at', $value))
            ->orderBy('employee_id')
            ->orderBy('leave_type_id')
            ->orderBy('period_start')
            ->get();

        $grouped = [];
        
        foreach ($leaveBalances as $balance) {
            $employeeId = $balance->employee_id;
            $leaveTypeId = $balance->leave_type_id;
            
            if (!isset($grouped[$employeeId])) {
                $grouped[$employeeId] = [
                    'employee' => $balance->employee,
                    'leave_types' => []
                ];
            }
            
            if (!isset($grouped[$employeeId]['leave_types'][$leaveTypeId])) {
                $grouped[$employeeId]['leave_types'][$leaveTypeId] = [
                    'leave_type' => $balance->leave_type,
                    'periods' => []
                ];
            }
            
            $grouped[$employeeId]['leave_types'][$leaveTypeId]['periods'][] = $balance;
        }
        
        return $grouped;
    }

    public function getPeriodLabel($balance)
    {
        $start = $balance->period_start ? $balance->period_start->format('M Y') : 'N/A';
        $end = $balance->period_end ? $balance->period_end->format('M Y') : 'N/A';
        
        if ($start === $end) {
            return $start;
        }
        
        return "{$start} - {$end}";
    }

    public function getBalanceColor($balance)
    {
        if ($balance->balance <= 0) {
            return 'text-red-600';
        } elseif ($balance->balance <= 5) {
            return 'text-yellow-600';
        } else {
            return 'text-green-600';
        }
    }

    /**
     * Get calculated balance for comparison
     */
    public function getCalculatedBalance($balance)
    {
        return $this->calculateBalance(
            $balance->allocated_days,
            $balance->consumed_days,
            $balance->carry_forwarded_days,
            $balance->lapsed_days
        );
    }

    /**
     * Check if balance is inconsistent
     */
    public function isBalanceInconsistent($balance)
    {
        $calculated = $this->getCalculatedBalance($balance);
        return abs($calculated - $balance->balance) > 0.01; // Allow for small floating point differences
    }

    public function toggleViewMode()
    {
        $this->viewMode = $this->viewMode === 'accordion' ? 'table' : 'accordion';
    }

    // /**
    //  * Quick adjust a specific field in leave balance
    //  */
    // public function quickAdjust($id, $field, $amount)
    // {
    //     $leaveBalance = EmpLeaveBalance::findOrFail($id);
        
    //     // Validate field
    //     $allowedFields = ['allocated_days', 'consumed_days', 'carry_forwarded_days', 'lapsed_days'];
    //     if (!in_array($field, $allowedFields)) {
    //         Flux::toast(
    //             variant: 'error',
    //             heading: 'Invalid Field',
    //             text: 'Cannot adjust this field.',
    //         );
    //         return;
    //     }

    //     // Update the field
    //     $newValue = max(0, $leaveBalance->$field + $amount);
    //     $leaveBalance->$field = $newValue;
        
    //     // Recalculate balance
    //     $leaveBalance->balance = $this->calculateBalance(
    //         $leaveBalance->allocated_days,
    //         $leaveBalance->consumed_days,
    //         $leaveBalance->carry_forwarded_days,
    //         $leaveBalance->lapsed_days
    //     );
        
    //     $leaveBalance->save();
        
    //     $fieldLabel = str_replace('_', ' ', ucfirst($field));
    //     Flux::toast(
    //         variant: 'success',
    //         heading: 'Balance Updated',
    //         text: "{$fieldLabel} adjusted by {$amount}. New balance: {$leaveBalance->balance}",
    //     );
    // }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Leave/blades/emp-leave-balances.blade.php'));
    }
} 