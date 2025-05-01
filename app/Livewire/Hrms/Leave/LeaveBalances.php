<?php

namespace App\Livewire\Hrms\Leave;

use App\Models\Hrms\EmpLeaveBalance;
use App\Models\Hrms\Employee;
use App\Models\Hrms\LeaveType;
use App\Models\Hrms\EmpLeaveTransaction;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Session;
use Flux;

class LeaveBalances extends Component
{
    use WithPagination;

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
    ];

    public array $listsForFields = [];
    public array $filters = [];
    public array $visibleFields = [];
    public array $visibleFilterFields = [];

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
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);
    }

    public function store()
    {
        $validatedData = $this->validate();

        $validatedData['formData'] = collect($validatedData['formData'])
            ->map(fn($val) => $val === '' ? null : $val)
            ->toArray();

        $validatedData['formData']['firm_id'] = session('firm_id');

        if ($this->isEditing) {
            $leaveBalance = EmpLeaveBalance::findOrFail($this->formData['id']);
            $oldAllocatedDays = $leaveBalance->allocated_days;
            $leaveBalance->update($validatedData['formData']);
            
            // Create transaction for allocation change if days changed
            if ($oldAllocatedDays != $validatedData['formData']['allocated_days']) {
                EmpLeaveTransaction::create([
                    'leave_balance_id' => $leaveBalance->id,
                    'transaction_type' => 'ALLOCATION_CHANGE',
                    'transaction_date' => now(),
                    'amount' => $validatedData['formData']['allocated_days'] - $oldAllocatedDays,
                    'created_by' => auth()->id(),
                    'firm_id' => session('firm_id')
                ]);
            }
            
            $toastMsg = 'Leave balance updated successfully';
        } else {
            $leaveBalance = EmpLeaveBalance::create($validatedData['formData']);
            
            // Create initial allocation transaction
            EmpLeaveTransaction::create([
                'leave_balance_id' => $leaveBalance->id,
                'transaction_type' => 'ALLOCATION',
                'transaction_date' => now(),
                'amount' => $validatedData['formData']['allocated_days'],
                'created_by' => auth()->id(),
                'firm_id' => session('firm_id')
            ]);
            
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

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Leave/blades/leave-balances.blade.php'));
    }
} 