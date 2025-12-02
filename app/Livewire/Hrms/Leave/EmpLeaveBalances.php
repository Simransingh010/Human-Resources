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
    public $perPage = 25;
    public $isEditing = false;
    public $search = '';
    public $selectedPeriod = '';
    
    public array $listsForFields = [];
    public array $periods = [];

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
        $this->loadPeriods();
        
        // Set default period to current/latest
        if (!empty($this->periods)) {
            $this->selectedPeriod = array_key_first($this->periods);
        }
    }

    protected function initListsForFields(): void
    {
        $this->listsForFields['employees'] = Employee::where('firm_id', session('firm_id'))
            ->pluck('fname', 'id');
        $this->listsForFields['leave_types'] = LeaveType::where('firm_id', session('firm_id'))
            ->where('is_inactive', false)
            ->pluck('leave_title', 'id');
    }

    protected function loadPeriods(): void
    {
        // Get unique periods that have at least one balance record
        $periods = EmpLeaveBalance::where('firm_id', session('firm_id'))
            ->selectRaw('DATE(period_start) as period_start, DATE(period_end) as period_end, COUNT(*) as record_count')
            ->groupBy('period_start', 'period_end')
            ->having('record_count', '>', 0)
            ->orderBy('period_start', 'desc')
            ->get();

        $this->periods = [];
        foreach ($periods as $period) {
            $start = \Carbon\Carbon::parse($period->period_start);
            $end = \Carbon\Carbon::parse($period->period_end);
            
            $key = $start->format('Y-m-d') . '|' . $end->format('Y-m-d');
            $label = $start->format('M Y') . ' - ' . $end->format('M Y');
            
            // Only add if not already exists (handles any remaining duplicates)
            if (!isset($this->periods[$key])) {
                $this->periods[$key] = $label . ' (' . $period->record_count . ')';
            }
        }
    }

    #[Computed]
    public function leaveTypes()
    {
        return LeaveType::where('firm_id', session('firm_id'))
            ->where('is_inactive', false)
            ->orderBy('leave_title')
            ->get();
    }

    #[Computed]
    public function pivotData()
    {
        $firmId = session('firm_id');

        $query = Employee::where('firm_id', $firmId)
            ->where('is_inactive', false)
            ->with(['emp_leave_balances' => function ($q) use ($firmId) {
                $q->where('firm_id', $firmId)->with('leave_type');
                
                if ($this->selectedPeriod) {
                    [$start, $end] = explode('|', $this->selectedPeriod);
                    $q->whereDate('period_start', $start)->whereDate('period_end', $end);
                }
            }]);

        // If a period is selected, sort employees with balances first
        if ($this->selectedPeriod) {
            [$start, $end] = explode('|', $this->selectedPeriod);
            
            // Add subquery to check if employee has balance for this period
            $query->withCount(['emp_leave_balances as has_balance_for_period' => function ($q) use ($firmId, $start, $end) {
                $q->where('firm_id', $firmId)
                  ->whereDate('period_start', $start)
                  ->whereDate('period_end', $end);
            }]);
            
            // Sort: employees with records first, then alphabetically
            $query->orderByDesc('has_balance_for_period')
                  ->orderBy('fname');
        } else {
            $query->orderBy('fname');
        }

        // Search filter
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('fname', 'like', '%' . $this->search . '%')
                  ->orWhere('lname', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%');
            });
        }

        return $query->paginate($this->perPage);
    }

    public function getBalanceForEmployee($employee, $leaveTypeId)
    {
        return $employee->emp_leave_balances
            ->where('leave_type_id', $leaveTypeId)
            ->first();
    }

    public function store()
    {
        $validatedData = $this->validate($this->rules());

        $validatedData['formData'] = collect($validatedData['formData'])
            ->map(fn($val) => $val === '' ? null : $val)
            ->toArray();

        $validatedData['formData']['firm_id'] = session('firm_id');
        
        // Recalculate balance
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
        Flux::toast(variant: 'success', heading: 'Saved', text: $toastMsg);
    }

    private function calculateBalance($allocatedDays, $consumedDays, $carryForwardedDays, $lapsedDays)
    {
        return $allocatedDays + $carryForwardedDays - $consumedDays - $lapsedDays;
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
        $this->formData['period_start'] = $leaveBalance->period_start?->format('Y-m-d') ?? '';
        $this->formData['period_end'] = $leaveBalance->period_end?->format('Y-m-d') ?? '';
        $this->modal('mdl-leave-balance')->show();
    }

    public function editCell($employeeId, $leaveTypeId)
    {
        // Find existing balance or prepare new one
        $balance = EmpLeaveBalance::where('firm_id', session('firm_id'))
            ->where('employee_id', $employeeId)
            ->where('leave_type_id', $leaveTypeId);

        if ($this->selectedPeriod) {
            [$start, $end] = explode('|', $this->selectedPeriod);
            $balance->where('period_start', $start)->where('period_end', $end);
        }

        $balance = $balance->first();

        if ($balance) {
            $this->edit($balance->id);
        } else {
            // Prepare new balance
            $this->resetForm();
            $this->formData['employee_id'] = $employeeId;
            $this->formData['leave_type_id'] = $leaveTypeId;
            
            if ($this->selectedPeriod) {
                [$start, $end] = explode('|', $this->selectedPeriod);
                $this->formData['period_start'] = $start;
                $this->formData['period_end'] = $end;
            }
            
            $this->modal('mdl-leave-balance')->show();
        }
    }

    public function showLeaveTransactions($id)
    {
        $this->selectedId = $id;
        $this->modal('leave-transactions')->show();
    }

    public function getBalanceColorClass($balance)
    {
        if (!$balance) return 'text-gray-400';
        if ($balance->balance <= 0) return 'text-red-600 dark:text-red-400';
        if ($balance->balance <= 5) return 'text-amber-600 dark:text-amber-400';
        return 'text-emerald-600 dark:text-emerald-400';
    }

    public function getBalanceBgClass($balance)
    {
        if (!$balance) return 'bg-gray-50 dark:bg-gray-800/50';
        if ($balance->balance <= 0) return 'bg-red-50 dark:bg-red-900/20';
        if ($balance->balance <= 5) return 'bg-amber-50 dark:bg-amber-900/20';
        return 'bg-emerald-50 dark:bg-emerald-900/20';
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedSelectedPeriod()
    {
        $this->resetPage();
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Leave/blades/emp-leave-balances.blade.php'));
    }
} 