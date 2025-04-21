<?php

namespace App\Livewire\Hrms\Attendance;

use Livewire\Component;
use App\Models\Hrms\Employee;
use App\Models\Hrms\EmpAttendance;
use Carbon\Carbon;
use App\Models\Hrms\EmpLeaveRequest;

class TodayAttendanceStats extends Component
{
    public $totalEmployees = 0;
    public $presentToday = 0;
    public $absentToday = 0;
    public $onLeaveToday = 0;
    public $onHalfdayToday = 0;
    public $onHolidayCount = 0;
    public $unmarkedCount = 0;
    public $selectedDate;

    public function mount()
    {
        $this->selectedDate = Carbon::today()->format('Y-m-d');
        $this->loadStats();
    }

    public function loadStats()
    {
        $firmId = 3;
        $date = Carbon::parse($this->selectedDate);

        // Get total active employees
        $this->totalEmployees = Employee::where('firm_id', $firmId)
            ->where('is_inactive', false)
            ->count();

        // Get attendance stats for selected date
        $attendance = EmpAttendance::where('firm_id', $firmId)
            ->where('work_date', $date)
            ->get();

        $this->presentToday = $attendance->where('attendance_status_main', '1')->count();
        $this->absentToday = $attendance->where('attendance_status_main', '2')->count();
        $this->onHalfdayToday = $attendance->where('attendance_status_main', '3')->count();
        $this->onLeaveToday = $attendance->where('attendance_status_main', '4')->count();
        $this->onHolidayCount = $attendance->where('attendance_status_main', '5')->count();
        $this->unmarkedCount = $this->totalEmployees - ($this->presentToday + $this->absentToday + $this->onLeaveToday + $this->onHalfdayToday + $this->onHolidayCount);
    }

    public function updatedSelectedDate()
    {
        $this->loadStats();
    }

    #[\Livewire\Attributes\Computed]
    public function todayLeaveRequests()
    {
        $date = $this->selectedDate ?? Carbon::today()->format('Y-m-d');
        
        return EmpLeaveRequest::query()
            ->with(['employee', 'leave_type'])
            ->where('firm_id', session('firm_id'))
            ->whereDate('apply_from', '<=', $date)
            ->whereDate('apply_to', '>=', $date)
            ->latest()
            ->get();
    }

    public function render()
    {
        return view('livewire.hrms.attendance.today-attendance-stats');
    }
} 