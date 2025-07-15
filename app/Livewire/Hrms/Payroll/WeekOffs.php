<?php

namespace App\Livewire\Hrms\Payroll;

use App\Models\Hrms\SalaryCycle;
use App\Models\Hrms\SalaryExecutionGroup;
use App\Models\Hrms\PayrollSlot;
use App\Models\Hrms\Employee;
use App\Models\Hrms\FlexiWeekOff;
use Illuminate\Support\Facades\Session;
use Livewire\Component;

class WeekOffs extends Component
{
    public $payrollCycles = [];
    public $executionGroups = [];
    public $payrollSlots = [];
    public $selectedCycleId = null;
    public $selectedGroupId = null;
    public $selectedSlotId = null;
    public $slotDetails = null;
    public $weekOffTable = [];
    public $searchName = '';
    public $sortBy = 'employee_name';
    public $sortDirection = 'asc';

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
        $this->weekOffTable = [];
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
        $this->weekOffTable = [];
    }

    public function selectSlot($slotId)
    {
        $this->selectedSlotId = $slotId;
        $this->slotDetails = PayrollSlot::find($slotId);
        $this->fetchWeekOffTable();
    }

    public function updatedSearchName()
    {
        $this->fetchWeekOffTable();
    }

    public function sort($column)
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
        $this->fetchWeekOffTable();
    }

    private function fetchWeekOffTable()
    {
        $this->weekOffTable = [];
        if (!$this->slotDetails) return;
        $groupId = $this->slotDetails->salary_execution_group_id;
        $from = $this->slotDetails->from_date;
        $to = $this->slotDetails->to_date;
        $group = SalaryExecutionGroup::find($groupId);
        if (!$group) return;
        $employees = $group->employees()->with(['emp_job_profile', 'emp_job_profile.department'])->get();
        $rows = [];
        foreach ($employees as $employee) {
            $weekOffs = FlexiWeekOff::where('firm_id', $employee->firm_id)
                ->where('employee_id', $employee->id)
                ->whereHas('availedAttendance', function($q) use ($from, $to) {
                    $q->whereBetween('work_date', [$from, $to]);
                })
                ->with(['availedAttendance', 'consumedAttendance'])
                ->get();
            foreach ($weekOffs as $weekOff) {
                $availedAttendance = $weekOff->availedAttendance;
                $consumedAttendance = $weekOff->consumedAttendance ?? null;
                $statusWords = [
                    'A' => 'Available',
                    'C' => 'Consumed',
                    'L' => 'Lapsed',
                    'CF' => 'Carry Forward',
                ];
                $status = $statusWords[$weekOff->week_off_Status] ?? $weekOff->week_off_Status;
                $rows[] = [
                    'employee_name' => trim($employee->fname . ' ' . $employee->lname),
                    'department' => $employee->emp_job_profile->department->title ?? '',
                    'availed_date' => $availedAttendance ? $availedAttendance->work_date->format('Y-m-d') : '',
                    'consumed_date' => $consumedAttendance && $consumedAttendance->work_date ? $consumedAttendance->work_date->format('Y-m-d') : '',
                    'status' => $status,
                    'remarks' => $availedAttendance ? ($availedAttendance->attend_remarks ?? '') : '',
                ];
            }
        }
        // Filter by search
        if ($this->searchName) {
            $search = strtolower($this->searchName);
            $rows = array_filter($rows, function($row) use ($search) {
                return strpos(strtolower($row['employee_name']), $search) !== false;
            });
        }
        // Sort
        $rows = collect($rows)->sortBy(function($row) {
            return $row[$this->sortBy] ?? '';
        }, SORT_REGULAR, $this->sortDirection === 'desc')->values()->toArray();
        $this->weekOffTable = $rows;
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Payroll/blades/week-offs.blade.php'), [
            'weekOffTable' => $this->weekOffTable,
            'sortBy' => $this->sortBy,
            'sortDirection' => $this->sortDirection,
            'slotDetails' => $this->slotDetails,
            'payrollCycles' => $this->payrollCycles,
            'executionGroups' => $this->executionGroups,
            'payrollSlots' => $this->payrollSlots,
            'selectedCycleId' => $this->selectedCycleId,
            'selectedGroupId' => $this->selectedGroupId,
            'selectedSlotId' => $this->selectedSlotId,
            'searchName' => $this->searchName,
        ]);
    }
}
