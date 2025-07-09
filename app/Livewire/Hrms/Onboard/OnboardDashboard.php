<?php

namespace App\Livewire\Hrms\Onboard;

use Livewire\Component;
use App\Models\Hrms\Employee;
use App\Models\Hrms\EmpAttendance;
use Carbon\Carbon;
use App\Models\Hrms\EmpLeaveRequest;
use App\Models\Hrms\Holiday;
use App\Models\Hrms\SalaryCycle;
use App\Models\Hrms\PayrollSlot;
use App\Models\Hrms\EmployeesSalaryExecutionGroup;
use App\Models\Hrms\SalaryExecutionGroup;
use App\Models\Settings\Department;
use App\Models\Hrms\PayrollComponentsEmployeesTrack;
use App\Models\Hrms\EmployeePersonalDetail;

class OnboardDashboard extends Component
{
    public $totalEmployees = 0;
    public $presentToday = 0;
    public $absentToday = 0;
    public $onLeaveToday = 0;
    public $onHalfdayToday = 0;
    public $onHolidayCount = 0;
    public $notMarkedToday = 0;
    public $holidays = [];
    public $selectedDate;
    public $todayAttendanceChart = [];
    public $monthAttendanceChart = [];
    public $expectedEmployees = 0;
    public $monthlyAttendanceData = [];
    public $monthlyLabels = [];
    public $data = [];
    public $holidaysForCalendar = '';

    public $currentPayrollCycleName = 'N/A';
    public $currentPayrollStatus = 'N/A';
    public $currentPayrollPeriod = 'N/A';
    public $currentPayrollEmployeesCount = 0;
    public $totalDepartments = 0;
    public $totalMaleEmployees = 0;
    public $totalFemaleEmployees = 0;
    public $pendingLeaveRequestsCount = 0;
    public $activePayrollCyclesCount = 0;
    public $totalSalaryDisbursed = 0;

    public function mount()
    {
        $this->selectedDate = Carbon::today()->format('Y-m-d');
        if (!session('firm_id')) {
            session()->put('firm_id', 3); // Temporary fix - you should set this properly through authentication
        }
        $this->loadStats();
        $this->prepareMonthlyData();
        $this->prepareChartData();
    }

    public function prepareMonthlyData()
    {
        $startOfYear = Carbon::now()->startOfYear();
        $months = [];
        $presentData = [];
        $absentData = [];
        $leaveData = [];
        $notMarkedData = [];

        for ($i = 0; $i < 12; $i++) {
            $month = $startOfYear->copy()->addMonths($i);
            $months[] = $month->format('M');

            // Dynamically get total active employees for this specific month,
            // considering their joining and leaving dates from the employee_job_profiles table.
            $totalEmployeesForMonth = Employee::where('employees.firm_id', session('firm_id'))
                ->where('employees.is_inactive', false)
                ->join('employee_job_profiles', 'employees.id', '=', 'employee_job_profiles.employee_id')
                ->where('employee_job_profiles.doh', '<=', $month->endOfMonth())
                ->count();

            $monthAttendance = EmpAttendance::where('firm_id', session('firm_id'))
                ->whereYear('work_date', $month->year)
                ->whereMonth('work_date', $month->month)
                ->get();

            $present = $monthAttendance->where('attendance_status_main', 'P')->count();
            $absent = $monthAttendance->where('attendance_status_main', 'A')->count();
            $leave = $monthAttendance->where('attendance_status_main', 'L')->count();

            $recordedAttendance = $present + $absent + $leave;
            $notMarked = max(0, $totalEmployeesForMonth - $recordedAttendance);

            $presentData[] = $present;
            $absentData[] = $absent;
            $leaveData[] = $leave;
            $notMarkedData[] = $notMarked;
        }

        $this->monthlyLabels = $months;
        $this->monthlyAttendanceData = [
            'present' => $presentData,
            'absent' => $absentData,
            'leave' => $leaveData,
            'notMarked' => $notMarkedData
        ];
    }

    public function prepareChartData()
    {
        $data = [];
        foreach ($this->monthlyLabels as $i => $month) {
            $data[] = [
                'date' => $month,
                'present' => $this->monthlyAttendanceData['present'][$i] ?? 0,
                'absent' => $this->monthlyAttendanceData['absent'][$i] ?? 0,
                'leave' => $this->monthlyAttendanceData['leave'][$i] ?? 0,
                'notMarked' => $this->monthlyAttendanceData['notMarked'][$i] ?? 0,
            ];
        }
        $this->data = $data;
    }

    public function loadStats()
    {
        $date = Carbon::parse($this->selectedDate);
        

        // Total active employees
        $this->totalEmployees = Employee::where('firm_id', session('firm_id'))
            ->where('is_inactive', false)
            ->count();

        // Expected employees (aapke business logic ke hisaab se, yahan total active hi liya hai)
        $this->expectedEmployees = $this->totalEmployees;

        // Attendance stats for selected date
        $attendance = EmpAttendance::where('firm_id', session('firm_id'))
            ->where('work_date', $date)
            ->get();

        $this->presentToday = $attendance->where('attendance_status_main', 'P')->count();
        $this->absentToday = $attendance->where('attendance_status_main', 'A')->count();
        $this->onLeaveToday = $this->todayLeaveRequests()->count();

        // Unmarked = expected - (present + absent + leave)
        $this->notMarkedToday = $this->expectedEmployees - (
            $this->presentToday + $this->absentToday + $this->onLeaveToday
        );

        // Prepare today's attendance chart data
        $this->todayAttendanceChart = [
            'Present' => $this->presentToday,
            'Absent' => $this->absentToday,
            'Leave' => $this->onLeaveToday,
            'Not Marked' => $this->notMarkedToday,
        ];

        // Prepare month's attendance chart data
        $startOfMonth = Carbon::parse($this->selectedDate)->startOfMonth();
        $endOfMonth = Carbon::parse($this->selectedDate)->endOfMonth();
        $monthAttendance = EmpAttendance::where('firm_id', session('firm_id'))
            ->whereBetween('work_date', [$startOfMonth, $endOfMonth])
            ->get();

        $this->monthAttendanceChart = [
            'Present' => $monthAttendance->where('attendance_status_main', 'P')->count(),
            'Absent' => $monthAttendance->where('attendance_status_main', 'A')->count(),
            'Leave' => $monthAttendance->where('attendance_status_main', 'L')->count(),
        ];

        // Fetch holidays for the calendar
        $holidays = Holiday::where('firm_id', session('firm_id'))
           ->where('is_inactive', 0)
            ->where('deleted_at', null)
            ->get();

        $holidayDates = [];
        $holidayDetails = [];
        foreach ($holidays as $holiday) {
            $startDate = Carbon::parse($holiday->start_date);
            $endDate = $holiday->end_date ? Carbon::parse($holiday->end_date) : $startDate;

            if ($holiday->repeat_annually) {
                $startDate->year($date->year);
                $endDate->year($date->year);
            }

            while ($startDate->lte($endDate)) {
                $holidayDates[] = $startDate->format('Y-m-d');
                $holidayDetails[] = [
                    'date' => $startDate->format('Y-m-d'),
                    'title' => $holiday->holiday_title
                ];
                $startDate->addDay();
            }
        }
        $this->holidays = $holidayDetails;
        $this->holidaysForCalendar = implode(',', array_unique($holidayDates));

        // Fetch current payroll cycle status
        $latestPayrollSlot = PayrollSlot::where('firm_id', session('firm_id'))
            ->orderBy('created_at', 'desc')
            ->first();

        if ($latestPayrollSlot) {
            $this->currentPayrollStatus = PayrollSlot::PAYROLL_SLOT_STATUS[$latestPayrollSlot->payroll_slot_status] ?? 'Unknown';
            $this->currentPayrollPeriod = Carbon::parse($latestPayrollSlot->from_date)->format('M d, Y') . ' - ' . Carbon::parse($latestPayrollSlot->to_date)->format('M d, Y');

            $salaryCycle = SalaryCycle::find($latestPayrollSlot->salary_cycle_id);
            $this->currentPayrollCycleName = $salaryCycle->title ?? 'N/A';

            // Fetch all salary execution groups for this salary cycle
            $salaryExecutionGroupIds = SalaryExecutionGroup::where('salary_cycle_id', $latestPayrollSlot->salary_cycle_id)
                ->pluck('id');

            // Count employees across all relevant salary execution groups
            $this->currentPayrollEmployeesCount = EmployeesSalaryExecutionGroup::where('firm_id', session('firm_id'))
                ->whereIn('salary_execution_group_id', $salaryExecutionGroupIds)
                ->count();
            
        } else {
            $this->currentPayrollCycleName = 'No Payroll Data';
            $this->currentPayrollStatus = 'N/A';
            $this->currentPayrollPeriod = 'N/A';
            $this->currentPayrollEmployeesCount = 0;
        }

        // Total Departments
        $this->totalDepartments = Department::where('firm_id', session('firm_id'))->count();

        // General Employee Statistics
        $this->totalMaleEmployees = Employee::where('firm_id', session('firm_id'))
            ->where('is_inactive', false)
            ->where('gender', 'Male') // Assuming 'gender' column and 'Male' value
            ->count();

        $this->totalFemaleEmployees = Employee::where('firm_id', session('firm_id'))
            ->where('is_inactive', false)
            ->where('gender', 'Female') // Assuming 'gender' column and 'Female' value
            ->count();

        // Pending Leave Requests
        $this->pendingLeaveRequestsCount = EmpLeaveRequest::where('firm_id', session('firm_id'))
            ->where('status', 'pending') // Assuming 'status' column and 'pending' value
            ->count();

        // Number of Active Payroll Cycles
        $this->activePayrollCyclesCount = SalaryCycle::where('firm_id', session('firm_id'))
            ->where('is_inactive', false) // Assuming 'false' means active
            ->count();

        // Total Salary Disbursed (for the current month based on selectedDate)
        $this->totalSalaryDisbursed = PayrollComponentsEmployeesTrack::where('firm_id', session('firm_id'))
            ->whereBetween('salary_period_from', [$startOfMonth, $endOfMonth])
            ->whereHas('payroll_slot', function ($query) {
                $query->where('payroll_slot_status', 'CM'); // 'CM' for Completed
            })
            ->sum('amount_payable');
    }
    

    public function updatedSelectedDate()
    {
        $this->loadStats();
    }

    #[\Livewire\Attributes\Computed]
    public function employeesWithoutAttendancePolicy()
    {
        return Employee::where('firm_id', session('firm_id'))
            ->where('is_inactive', false)
            ->whereDoesntHave('attendance_policy', function($query) {
                $query->where(function($q) {
                    $q->whereNull('valid_to')
                        ->orWhere('valid_to', '>=', Carbon::now());
                });
            })
            ->with(['emp_job_profile.department', 'emp_job_profile.designation', 'emp_personal_detail.media'])
            ->get();
    }

    #[\Livewire\Attributes\Computed]
    public function employeesWithoutWorkShift()
    {
        return Employee::where('firm_id', session('firm_id'))
            ->where('is_inactive', false)
            ->whereDoesntHave('emp_work_shifts', function($query) {
                $query->where(function($q) {
                    $q->whereNull('end_date')
                        ->orWhere('end_date', '>=', Carbon::now());
                });
            })
            ->with(['emp_job_profile.department', 'emp_job_profile.designation', 'emp_personal_detail.media'])
            ->get();
    }

    #[\Livewire\Attributes\Computed]
    public function employeesWithoutLeaveApprovers()
    {
        return Employee::where('employees.firm_id', session('firm_id'))
            ->where('employees.is_inactive', false)
            ->whereDoesntHave('leave_approval_rules', function($query) {
                $query->where('employee_leave_approval_rule.is_inactive', false)
                    ->where(function($q) {
                        $q->whereNull('leave_approval_rules.period_end')
                            ->orWhere('leave_approval_rules.period_end', '>=', now());
                    });
            })
            ->with(['emp_job_profile.department', 'emp_job_profile.designation', 'emp_personal_detail.media'])
            ->get();
    }

    #[\Livewire\Attributes\Computed]
    public function todayBirthdays()
    {
        $date = $this->selectedDate ?? Carbon::today()->format('Y-m-d');
        $day = Carbon::parse($date)->day;
        $month = Carbon::parse($date)->month;
        
        return EmployeePersonalDetail::query()
            ->with(['employee.emp_job_profile.department', 'employee.emp_job_profile.designation', 'media'])
            ->whereHas('employee', function ($query) {
                $query->where('firm_id', session('firm_id'))
                    ->where('is_inactive', false);
            })
            ->whereMonth('dob', $month)
            ->whereDay('dob', $day)
            ->get();
    }

    #[\Livewire\Attributes\Computed]
    public function todayWorkAnniversaries()
    {
        $date = $this->selectedDate ?? Carbon::today()->format('Y-m-d');
        $day = Carbon::parse($date)->day;
        $month = Carbon::parse($date)->month;
        
        return EmployeePersonalDetail::query()
            ->with(['employee.emp_job_profile.department', 'employee.emp_job_profile.designation', 'media'])
            ->whereHas('employee', function ($query) {
                $query->where('firm_id', session('firm_id'))
                    ->where('is_inactive', false);
            })
            ->whereMonth('doa', $month)
            ->whereDay('doa', $day)
            ->get();
    }

    #[\Livewire\Attributes\Computed]
    public function todayLeaveRequests()
    {
        $date = $this->selectedDate ?? Carbon::today()->format('Y-m-d');
        
        return EmpLeaveRequest::query()
            ->with([
                'employee.emp_job_profile.department', 
                'employee.emp_job_profile.designation', 
                'leave_type', 
                'employee.emp_personal_detail.media'
            ])
            ->where('firm_id', session('firm_id'))
            ->where('status', 'approved')
            ->whereDate('apply_from', '<=', $date)
            ->whereDate('apply_to', '>=', $date)
            ->latest()
            ->get();
    }

    #[\Livewire\Attributes\Computed]
    public function employeesNotMarked()
    {
        $date = Carbon::parse($this->selectedDate);

        // Get IDs of employees with an attendance record for the selected date
        $attendedEmployeeIds = EmpAttendance::where('firm_id', session('firm_id'))
            ->whereDate('work_date', $date)
            ->pluck('employee_id');

        // Get IDs of employees on leave for the selected date from the existing computed property
        $onLeaveEmployeeIds = $this->todayLeaveRequests()->pluck('employee_id');

        // Combine all IDs of employees who are accounted for
        $accountedForEmployeeIds = $attendedEmployeeIds->merge($onLeaveEmployeeIds)->unique();

        // Get all active employees who are NOT in the "accounted for" list
        return Employee::where('firm_id', session('firm_id'))
            ->where('is_inactive', false)
            ->whereNotIn('id', $accountedForEmployeeIds)
            ->with(['emp_personal_detail.media'])
            ->get();
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Onboard/blades/onboard-dashboard.blade.php'));
    }
} 