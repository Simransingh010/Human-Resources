<?php

namespace App\Livewire\Hrms\Payroll;

use App\Models\Hrms\SalaryCycle;
use App\Models\Hrms\SalaryExecutionGroup;
use App\Models\Hrms\PayrollSlot;
use Illuminate\Support\Facades\Session;
use Livewire\Component;

class WeekOffCredit extends Component
{
    public $payrollCycles = [];
    public $executionGroups = [];
    public $payrollSlots = [];
    public $selectedCycleId = null;
    public $selectedGroupId = null;
    public $selectedSlotId = null;
    public $slotDetails = null;
    public $sortBy = 'employee';
    public $sortDirection = 'asc';
    public $searchName = '';
    private $searchCache = [];
    private $lastCacheKey = null;
    public $showSyncModal = false;
    public $syncDays = 0;
    public $syncEmployeeId = null;
    public $showBulkSyncModal = false;

    public function mount()
    {
        $this->payrollCycles = SalaryCycle::where('firm_id', Session::get('firm_id'))->get();
    }

    public function updatedSelectedCycleId($value)
    {
        $this->selectedGroupId = null;
        $this->selectedSlotId = null;
        $this->slotDetails = null;
        $this->executionGroups = SalaryExecutionGroup::where('firm_id', Session::get('firm_id'))
            ->where('salary_cycle_id', $value)
            ->where('is_inactive', false)
            ->get();
        $this->payrollSlots = [];
    }

    public function updatedSelectedGroupId($value)
    {
        $this->selectedSlotId = null;
        $this->slotDetails = null;
        $this->payrollSlots = PayrollSlot::where('firm_id', Session::get('firm_id'))
            ->where('salary_cycle_id', $this->selectedCycleId)
            ->where('salary_execution_group_id', $value)
            ->orderBy('from_date', 'asc')
            ->get();
    }

    public function selectSlot($slotId)
    {
        $this->selectedSlotId = $slotId;
        $this->slotDetails = PayrollSlot::find($slotId);
    }

    public function sort($column)
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
    }

    public function updatedSelectedSlotId()
    {
        $this->searchCache = [];
        $this->lastCacheKey = null;
    }

    public function updatedSearchName()
    {
        // No-op, just triggers reactivity
    }

    public function getWeekOffTableProperty()
    {
        if (!$this->slotDetails) return collect([]);
        $groupId = $this->slotDetails->salary_execution_group_id;
        $from = $this->slotDetails->from_date;
        $to = $this->slotDetails->to_date;
        $slotKey = $this->selectedSlotId;
        $searchKey = strtolower(trim($this->searchName));
        $cacheKey = $slotKey . '|' . $searchKey . '|' . $this->sortBy . '|' . $this->sortDirection;
        if ($this->lastCacheKey === $cacheKey && isset($this->searchCache[$cacheKey])) {
            return $this->searchCache[$cacheKey];
        }
        $group = \App\Models\Hrms\SalaryExecutionGroup::find($groupId);
        if (!$group) return collect([]);
        $employees = $group->employees()->get();
        $rows = [];
        foreach ($employees as $employee) {
            $weekOffs = \App\Models\Hrms\FlexiWeekOff::where('firm_id', $employee->firm_id)
                ->where('employee_id', $employee->id)
                ->whereHas('availedAttendance', function($q) use ($from, $to) {
                    $q->whereBetween('work_date', [$from, $to]);
                })
                ->get();
            $total = $weekOffs->count();
            if ($total > 0) {
                $employeeName = trim($employee->fname . ' ' . ($employee->mname ?? '') . ' ' . $employee->lname);
                if ($searchKey && strpos(strtolower($employeeName), $searchKey) === false) {
                    continue;
                }
                $available = $weekOffs->where('week_off_Status', 'A')->count();
                $consumed = $weekOffs->where('week_off_Status', 'C')->count();
                $carryForward = $weekOffs->where('week_off_Status', 'CF')->count();
                $rows[] = [
                    'employee' => $employee,
                    'employee_name' => $employeeName,
                    'total' => $total,
                    'available' => $available,
                    'consumed' => $consumed,
                    'carry_forward' => $carryForward,
                ];
            }
        }
        // Sorting
        $sorted = collect($rows)->sortBy(function($row) {
            $key = $this->sortBy;
            if ($key === 'employee') {
                return strtolower($row['employee_name']);
            }
            return $row[$key];
        }, SORT_REGULAR, $this->sortDirection === 'desc')->values();
        $this->searchCache[$cacheKey] = $sorted;
        $this->lastCacheKey = $cacheKey;
        return $sorted;
    }

    public function getSlotHasEndedProperty()
    {
        if (!$this->slotDetails) return false;
        $today = now()->startOfDay();
        $slotEndDate = \Carbon\Carbon::parse($this->slotDetails->to_date)->startOfDay();
        return $today->gt($slotEndDate);
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Payroll/blades/week-off-credit.blade.php'), [
            'weekOffTable' => $this->weekOffTable,
            'sortBy' => $this->sortBy,
            'sortDirection' => $this->sortDirection,
            'slotHasEnded' => $this->slotHasEnded,
        ]);
    }

    public function openSyncModal($employeeId)
    {
        $this->syncEmployeeId = $employeeId;
        $this->syncDays = $this->getEmployeeAvailableDays($employeeId);
        $this->showSyncModal = true;
    }

    public function updatedSyncDays($value)
    {
        $max = $this->getEmployeeAvailableDays($this->syncEmployeeId);
        if ($value > $max) {
            $this->syncDays = $max;
        } elseif ($value < 0) {
            $this->syncDays = 0;
        }
    }

    private function getEmployeeAvailableDays($employeeId)
    {
        if (!$this->slotDetails) return 0;
        $from = $this->slotDetails->from_date;
        $to = $this->slotDetails->to_date;
        $weekOffs = \App\Models\Hrms\FlexiWeekOff::where('firm_id', Session::get('firm_id'))
            ->where('employee_id', $employeeId)
            ->whereHas('availedAttendance', function($q) use ($from, $to) {
                $q->whereBetween('work_date', [$from, $to]);
            })
            ->where('week_off_Status', 'A')
            ->count();
        return $weekOffs;
    }

    public function closeSyncModal()
    {
        $this->showSyncModal = false;
    }

    public function confirmSync()
    {
        $employeeId = $this->syncEmployeeId;
        $daysToCredit = (int) $this->syncDays;
        if (!$employeeId || $daysToCredit <= 0 || !$this->slotDetails) {
            $this->showSyncModal = false;
            return;
        }
        $from = $this->slotDetails->from_date;
        $to = $this->slotDetails->to_date;
        $firmId = session('firm_id');

        // 1. Find LeaveType with leave_type_main = 'weekoff'
        $leaveType = \App\Models\Hrms\LeaveType::where('firm_id', $firmId)
            ->where('leave_type_main', 'weekoff')
            ->first();

        if (!$leaveType) {
            $this->showSyncModal = false;
            \Flux\Flux::toast('Leave type not found', 'error');
            return;
        }

        // 2. Find EmpLeaveBalance for this employee, leave type, and slot period (do not create)
        $leaveBalance = \App\Models\Hrms\EmpLeaveBalance::where([
            'firm_id' => $firmId,
            'employee_id' => $employeeId,
            'leave_type_id' => $leaveType->id,

        ])->first();
        if (!$leaveBalance) {
            $this->showSyncModal = false;
            \Flux\Flux::toast('Leave balance not found', 'error');
            return;
        }
        $leaveBalance->allocated_days += $daysToCredit;
        // $leaveBalance->carry_forwarded_days += $daysToCredit;
        $leaveBalance->balance += $daysToCredit;
        $leaveBalance->save();

        // 3. Update that many FlexiWeekOff records from 'A' to 'CF' for this slot
        $weekOffs = \App\Models\Hrms\FlexiWeekOff::where('firm_id', $firmId)
            ->where('employee_id', $employeeId)
            ->whereHas('availedAttendance', function($q) use ($from, $to) {
                $q->whereBetween('work_date', [$from, $to]);
            })
            ->where('week_off_Status', 'A')
            ->limit($daysToCredit)
            ->get();
        foreach ($weekOffs as $weekOff) {
            $weekOff->week_off_Status = 'CF';
            $weekOff->save();
        }

        $this->showSyncModal = false;
        \Flux\Flux::toast('Week Offs has been Credited Successfully', 'Success');
        // Refresh table by resetting cache
        $this->updatedSelectedSlotId();
    }

    public function openBulkSyncModal()
    {
        $this->showBulkSyncModal = true;
    }

    public function closeBulkSyncModal()
    {
        $this->showBulkSyncModal = false;
    }

    public function confirmBulkSync()
    {
        if (!$this->slotDetails) {
            $this->showBulkSyncModal = false;
            return;
        }
        $from = $this->slotDetails->from_date;
        $to = $this->slotDetails->to_date;
        $firmId = session('firm_id');
        $leaveType = \App\Models\Hrms\LeaveType::where('firm_id', $firmId)
            ->where('leave_type_main', 'weekoff')
            ->first();
        if (!$leaveType) {
            $this->showBulkSyncModal = false;
            \Flux\Flux::toast('Leave type not found', 'error');
            return;
        }
        $groupId = $this->slotDetails->salary_execution_group_id;
        $group = \App\Models\Hrms\SalaryExecutionGroup::find($groupId);
        if (!$group) {
            $this->showBulkSyncModal = false;
            return;
        }
        $employees = $group->employees()->get();
        foreach ($employees as $employee) {
            $employeeId = $employee->id;
            $available = $this->getEmployeeAvailableDays($employeeId);
            if ($available > 0) {
                // Only update existing leave balance, do not create
                $leaveBalance = \App\Models\Hrms\EmpLeaveBalance::where([
                    'firm_id' => $firmId,
                    'employee_id' => $employeeId,
                    'leave_type_id' => $leaveType->id,
                    'period_start' => $from,
                    'period_end' => $to,
                ])->first();
                if (!$leaveBalance) {
                    \Flux\Flux::toast('Leave balance not found for employee ID ' . $employeeId, 'error');
                    continue;
                }
                $leaveBalance->allocated_days += $available;
                $leaveBalance->carry_forwarded_days += $available;
                $leaveBalance->balance += $available;
                $leaveBalance->save();
                // Update FlexiWeekOffs
                $weekOffs = \App\Models\Hrms\FlexiWeekOff::where('firm_id', $firmId)
                    ->where('employee_id', $employeeId)
                    ->whereHas('availedAttendance', function($q) use ($from, $to) {
                        $q->whereBetween('work_date', [$from, $to]);
                    })
                    ->where('week_off_Status', 'A')
                    ->limit($available)
                    ->get();
                foreach ($weekOffs as $weekOff) {
                    $weekOff->week_off_Status = 'CF';
                    $weekOff->save();
                }
            }
        }
        $this->showBulkSyncModal = false;
        $this->updatedSelectedSlotId();
    }
}
