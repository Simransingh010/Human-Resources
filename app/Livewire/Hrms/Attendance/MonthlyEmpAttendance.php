<?php

namespace App\Livewire\Hrms\Attendance;

use Livewire\Component;
use App\Models\Hrms\EmpAttendance;
use App\Models\Hrms\Employee;
use App\Models\Settings\Department;
use App\Models\Settings\Designation;
use App\Models\Settings\Joblocation;
use App\Models\Hrms\AttendanceLocation;
use Carbon\Carbon;
use Livewire\WithPagination;
use Flux;
use Illuminate\Support\Facades\Session;

class MonthlyEmpAttendance extends Component
{
    use WithPagination;

    public $dateRange = [];
    public $firm_id;
    public $employeeNameFilter = '';
    public $selectedDepartment = '';
    public $listsForFields = [];
    public $attendanceData = [];
    public $attendanceLogs = [];
    public $selectedEmployeeName = '';
    public $statusDates = [];
    public $selectedStatusLabel = '';
    public $activeStatuses = [];
    protected $paginationTheme = 'tailwind';

    public function mount()
    {
        $this->firm_id = Session::get('firm_id');
        if (!$this->firm_id) {
            Flux::toast(
                variant: 'error',
                heading: 'Error',
                text: 'Firm ID not found in session'
            );
            return;
        }

        // Set default date range to current month
        $this->dateRange = [
            'start' => now()->startOfMonth()->format('Y-m-d'),
            'end' => now()->format('Y-m-d')
        ];

        $this->initListsForFields();
        $this->loadAttendanceData();
    }

    protected function initListsForFields(): void
    {
        $this->listsForFields['attendance_statuses'] = EmpAttendance::ATTENDANCE_STATUS_MAIN_SELECT;
    }

    protected function loadAttendanceData()
    {
        $query = Employee::where('firm_id', $this->firm_id)
            ->when($this->employeeNameFilter, function ($query) {
                $query->where(function ($q) {
                    $q->where('fname', 'like', '%' . $this->employeeNameFilter . '%')
                        ->orWhere('lname', 'like', '%' . $this->employeeNameFilter . '%');
                });
            })
            ->when($this->selectedDepartment, function ($query) {
                $query->where('department_id', $this->selectedDepartment);
            });

        $employees = $query->get();

        // Set active statuses for this date range and these employees
        $this->activeStatuses = EmpAttendance::whereBetween('work_date', [$this->dateRange['start'], $this->dateRange['end']])
            ->whereIn('employee_id', $employees->pluck('id'))
            ->pluck('attendance_status_main')
            ->unique()
            ->map(fn($status) => (string) $status)
            ->values()
            ->toArray();

        $this->attendanceData = [];

        foreach ($employees as $employee) {
            $attendance = EmpAttendance::where('employee_id', $employee->id)
                ->whereBetween('work_date', [$this->dateRange['start'], $this->dateRange['end']])
                ->get();

            $statusCounts = [
                'P' => 0, 'A' => 0, 'HD' => 0, 'PW' => 0, 'L' => 0,
                'WFR' => 0, 'CW' => 0, 'OD' => 0, 'H' => 0, 'W' => 0,
                'S' => 0, 'POW' => 0, 'LM' => 0
            ];

            // Count statuses
            foreach ($attendance as $record) {
                if (isset($statusCounts[$record->attendance_status_main])) {
                    $statusCounts[$record->attendance_status_main]++;
                }
            }

            // Calculate days with no status
            $start = Carbon::parse($this->dateRange['start']);
            $end = Carbon::parse($this->dateRange['end']);
            $totalDays = $start->diffInDays($end) + 1;
            $daysWithStatus = array_sum($statusCounts);
            $noStatusCount = $totalDays - $daysWithStatus;

            $this->attendanceData[] = [
                'id' => $employee->id,
                'name' => $employee->fname . ' ' . $employee->lname,
                'employee_code' => $employee->employee_code,
                'email' => $employee->email,
                'phone' => $employee->phone,
                'department' => $employee->department ? $employee->department->title : null,
                'designation' => $employee->designation ? $employee->designation->title : null,
                'status_counts' => $statusCounts,
                'no_status_count' => max(0, $noStatusCount)
            ];
        }
    }

    public function showStatusDates($employeeId, $status)
    {
        $employee = Employee::find($employeeId);
        if (!$employee) {
            Flux::toast(
                variant: 'error',
                heading: 'Error',
                text: 'Employee not found'
            );
            return;
        }

        $this->selectedEmployeeName = $employee->fname . ' ' . $employee->lname;
        
        if ($status === 'no_status') {
            $this->selectedStatusLabel = 'No Status';
            
            // Get all dates in range
            $start = Carbon::parse($this->dateRange['start']);
            $end = Carbon::parse($this->dateRange['end']);
            $allDates = collect();
            
            for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
                $allDates->push($date->format('Y-m-d'));
            }
            
            // Get dates with status
            $datesWithStatus = EmpAttendance::where('employee_id', $employeeId)
                ->whereBetween('work_date', [$this->dateRange['start'], $this->dateRange['end']])
                ->pluck('work_date')
                ->map(fn($date) => Carbon::parse($date)->format('Y-m-d'))
                ->toArray();
            
            // Get dates with no status
            $this->statusDates = $allDates->diff($datesWithStatus)->values()->toArray();
        } else {
            $this->selectedStatusLabel = $this->listsForFields['attendance_statuses'][$status] ?? $status;
            
            $this->statusDates = EmpAttendance::where('employee_id', $employeeId)
                ->whereBetween('work_date', [$this->dateRange['start'], $this->dateRange['end']])
                ->where('attendance_status_main', $status)
                ->orderBy('work_date', 'asc')
                ->pluck('work_date')
                ->toArray();
        }

        $this->modal('status-dates-modal')->show();
    }

    public function updatedEmployeeNameFilter()
    {
        $this->loadAttendanceData();
    }

    public function updatedSelectedDepartment()
    {
        $this->loadAttendanceData();
    }

    public function updatedDateRange()
    {
        $this->loadAttendanceData();
    }

    public function getDepartmentsProperty()
    {
        return Department::where('firm_id', $this->firm_id)->get();
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Attendance/blades/monthly-emp-attendance.blade.php'));
    }
} 