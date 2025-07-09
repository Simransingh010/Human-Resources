<?php

namespace App\Livewire\Hrms\Attendance;

use Livewire\Component;
use App\Models\Hrms\Employee;
use App\Models\Hrms\EmpAttendance;
use App\Models\Hrms\EmpLeaveRequest;
use App\Models\Hrms\Holiday;
use App\Models\Hrms\EmpWorkShift;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class EmployeeAttendance extends Component
{
    public $selectedDate;
    public $presentDays = 0;
    public $workingDays = 0;
    public $averageHours = 0;
    public $thisWeekPresent = 0;
    public $thisWeekTotal = 0;
    public $attendanceScore = 'A+';
    public $standardHours = 0;
    public $overtimeHours = 0;
    public $targetHours = 160; // Default monthly target hours
    public $casualLeave = 0;
    public $sickLeave = 0;
    public $earnedLeave = 0;
    public $upcomingHolidays = [];
    public $recentActivities = [];
    public $chartData = [];

    public function mount()
    {
        $this->selectedDate = Carbon::today()->format('Y-m-d');
        $this->loadData();
    }

    public function updatedSelectedDate()
    {
        $this->loadData();
    }

    private function loadData()
    {
        $this->loadAttendanceStats();
        $this->loadWeeklyStats();
        $this->loadWorkingHours();
        $this->loadLeaveBalance();
        $this->loadUpcomingHolidays();
        $this->loadRecentActivities();
        $this->prepareChartData();
    }

    private function loadAttendanceStats()
    {
        $userId = Auth::id();
        $employee = Employee::where('user_id', $userId)->first();
        
        if (!$employee) {
            return;
        }

        $date = Carbon::parse($this->selectedDate);
        $startOfMonth = $date->copy()->startOfMonth();
        $endOfMonth = $date->copy()->endOfMonth();
        
        // Get employee's active work shift
        $empWorkShift = EmpWorkShift::where('employee_id', $employee->id)
            ->where(function($query) use ($startOfMonth) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', $startOfMonth);
            })
            ->where('start_date', '<=', $endOfMonth)
            ->with(['work_shift.work_shifts_algos'])
            ->first();
            
        if (!$empWorkShift) {
            $this->workingDays = 0;
            return;
        }

        // Get work shift algorithm configuration
        $workShiftAlgo = $empWorkShift->work_shift->work_shifts_algos()
            ->where('is_inactive', 0)
            ->first();

        if (!$workShiftAlgo) {
            $this->workingDays = 0;
            return;
        }

        // Get week off pattern
        $weekOffPattern = json_decode($workShiftAlgo->week_off_pattern, true) ?? [
            'type' => '',
            'fixed_weekly' => [
                'off_days' => []
            ],
            'rotating' => [
                'cycle' => [0, 0, 0, 0, 0, 0, 0],
                'offset' => 0
            ],
            'holiday_calendar' => [
                'id' => null,
                'use_public_holidays' => true
            ],
            'exceptions' => []
        ];

        // Get holidays based on holiday calendar and pattern settings
        $holidays = [];
        $useHolidays = $weekOffPattern['type'] === 'holiday_calendar' || 
                      ($weekOffPattern['type'] === 'combined' && $weekOffPattern['holiday_calendar']['use_public_holidays']);

        if ($useHolidays && $workShiftAlgo->holiday_calendar_id) {
            $holidays = Holiday::where('firm_id', $employee->firm_id)
                ->where('holiday_calendar_id', $workShiftAlgo->holiday_calendar_id)
                ->where('is_inactive', 0)
                ->whereNull('deleted_at')
                ->get();
        }
        
        $holidayDates = [];
        foreach ($holidays as $holiday) {
            $holidayStart = Carbon::parse($holiday->start_date);
            $holidayEnd = $holiday->end_date ? Carbon::parse($holiday->end_date) : $holidayStart;
            
            while ($holidayStart->lte($holidayEnd)) {
                $holidayDates[] = $holidayStart->format('Y-m-d');
                $holidayStart->addDay();
            }
        }

        // Add exception dates to holiday dates
        if (!empty($weekOffPattern['exceptions'])) {
            $holidayDates = array_merge($holidayDates, $weekOffPattern['exceptions']);
        }

        // Calculate working days
        $workingDays = 0;
        $currentDay = $startOfMonth->copy();
        
        while ($currentDay->lte($endOfMonth)) {
            $isWeekOff = false;
            $currentDate = $currentDay->format('Y-m-d');
            $dayOfWeek = $currentDay->dayOfWeek; // 0 (Sunday) to 6 (Saturday)

            // Check week off based on pattern type
            switch ($weekOffPattern['type']) {
                case 'fixed_weekly':
                    // Check if the day is in fixed weekly off days
                    $isWeekOff = in_array($dayOfWeek, $weekOffPattern['fixed_weekly']['off_days']);
                    break;

                case 'rotating':
                    // Calculate position in rotation cycle
                    $daysFromStart = $currentDay->diffInDays($empWorkShift->start_date);
                    $cycleLength = count($weekOffPattern['rotating']['cycle']);
                    $cyclePosition = ($daysFromStart + $weekOffPattern['rotating']['offset']) % $cycleLength;
                    $isWeekOff = $weekOffPattern['rotating']['cycle'][$cyclePosition] === 1;
                    break;

                case 'holiday_calendar':
                    // Already handled by holidays array
                    break;

                case 'combined':
                    // Check both fixed weekly pattern and holidays
                    $isWeekOff = in_array($dayOfWeek, $weekOffPattern['fixed_weekly']['off_days']);
                    break;
            }
            
            // If not a week off and not a holiday, count as working day
            if (!$isWeekOff && !in_array($currentDate, $holidayDates)) {
                $workingDays++;
            }
            
            $currentDay->addDay();
        }
        
        $this->workingDays = $workingDays;
        
        // Get present days for the employee in this month
        $attendanceRecords = EmpAttendance::where('employee_id', $employee->id)
            ->whereBetween('work_date', [$startOfMonth->format('Y-m-d'), $endOfMonth->format('Y-m-d')])
            ->get();
            
        $this->presentDays = $attendanceRecords->where('attendance_status_main', 'P')->count();
        
        // Calculate average hours worked
        $totalHours = 0;
        $recordsWithHours = 0;
        
        foreach ($attendanceRecords as $record) {
            if ($record->check_in && $record->check_out) {
                $checkIn = Carbon::parse($record->check_in);
                $checkOut = Carbon::parse($record->check_out);
                $hours = $checkOut->diffInMinutes($checkIn) / 60;
                
                if ($hours > 0) {
                    $totalHours += $hours;
                    $recordsWithHours++;
                }
            }
        }
        
        $this->averageHours = $recordsWithHours > 0 ? round($totalHours / $recordsWithHours, 1) : 0;
        
        // Calculate attendance score based on attendance percentage
        $attendancePercentage = $workingDays > 0 ? ($this->presentDays / $workingDays) * 100 : 0;
        
        if ($attendancePercentage >= 95) {
            $this->attendanceScore = 'A+';
        } elseif ($attendancePercentage >= 90) {
            $this->attendanceScore = 'A';
        } elseif ($attendancePercentage >= 85) {
            $this->attendanceScore = 'B+';
        } elseif ($attendancePercentage >= 80) {
            $this->attendanceScore = 'B';
        } elseif ($attendancePercentage >= 75) {
            $this->attendanceScore = 'C+';
        } elseif ($attendancePercentage >= 70) {
            $this->attendanceScore = 'C';
        } else {
            $this->attendanceScore = 'D';
        }
    }

    private function loadWeeklyStats()
    {
        $userId = Auth::id();
        $employee = Employee::where('user_id', $userId)->first();
        
        if (!$employee) {
            return;
        }

        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();
        
        // Calculate total working days in the week (excluding weekends and holidays)
        $workingDays = 0;
        $currentDay = $startOfWeek->copy();
        
        // Get holidays for the week
        $holidays = Holiday::where('firm_id', $employee->firm_id)
            ->where('is_inactive', 0)
            ->whereNull('deleted_at')
            ->get();
            
        $holidayDates = [];
        foreach ($holidays as $holiday) {
            $holidayStart = Carbon::parse($holiday->start_date);
            $holidayEnd = $holiday->end_date ? Carbon::parse($holiday->end_date) : $holidayStart;
            
            while ($holidayStart->lte($holidayEnd)) {
                $holidayDates[] = $holidayStart->format('Y-m-d');
                $holidayStart->addDay();
            }
        }
        
        while ($currentDay->lte($endOfWeek) && $currentDay->lte(Carbon::now())) {
            // Skip weekends and holidays
            if (!$currentDay->isWeekend() && !in_array($currentDay->format('Y-m-d'), $holidayDates)) {
                $workingDays++;
            }
            $currentDay->addDay();
        }
        
        $this->thisWeekTotal = $workingDays;
        
        // Get present days for the employee in this week
        $attendanceRecords = EmpAttendance::where('employee_id', $employee->id)
            ->whereBetween('work_date', [$startOfWeek->format('Y-m-d'), $endOfWeek->format('Y-m-d')])
            ->get();
            
        $this->thisWeekPresent = $attendanceRecords->where('attendance_status_main', 'P')->count();
    }

    private function loadWorkingHours()
    {
        $userId = Auth::id();
        $employee = Employee::where('user_id', $userId)->first();
        
        if (!$employee) {
            return;
        }

        $date = Carbon::parse($this->selectedDate);
        $startOfMonth = $date->copy()->startOfMonth();
        $endOfMonth = $date->copy()->endOfMonth();
        
        $attendanceRecords = EmpAttendance::where('employee_id', $employee->id)
            ->whereBetween('work_date', [$startOfMonth->format('Y-m-d'), $endOfMonth->format('Y-m-d')])
            ->get();
        
        $standardHours = 0;
        $overtimeHours = 0;
        $standardHoursPerDay = 8; // Default standard hours per day
        
        foreach ($attendanceRecords as $record) {
            if ($record->check_in && $record->check_out) {
                $checkIn = Carbon::parse($record->check_in);
                $checkOut = Carbon::parse($record->check_out);
                $hours = $checkOut->diffInMinutes($checkIn) / 60;
                
                if ($hours > $standardHoursPerDay) {
                    $standardHours += $standardHoursPerDay;
                    $overtimeHours += $hours - $standardHoursPerDay;
                } else {
                    $standardHours += $hours;
                }
            }
        }
        
        $this->standardHours = round($standardHours, 1);
        $this->overtimeHours = round($overtimeHours, 1);
    }

    private function loadLeaveBalance()
    {
        $userId = Auth::id();
        $employee = Employee::where('user_id', $userId)->first();
        
        if (!$employee) {
            return;
        }
        
        // This is a placeholder implementation - you would need to adjust based on your actual leave management system
        // Typically, you would query the employee's leave balance from your leave management tables
        
        // For now, setting some default values
        $this->casualLeave = 10;
        $this->sickLeave = 7;
        $this->earnedLeave = 5;
    }

    private function loadUpcomingHolidays()
    {
        $userId = Auth::id();
        $employee = Employee::where('user_id', $userId)->first();
        
        if (!$employee) {
            return;
        }
        
        $today = Carbon::today();
        $endOfYear = Carbon::today()->endOfYear();
        
        $holidays = Holiday::where('firm_id', $employee->firm_id)
            ->where('is_inactive', 0)
            ->whereNull('deleted_at')
            ->get();
        
        $upcomingHolidays = [];
        
        foreach ($holidays as $holiday) {
            $holidayStart = Carbon::parse($holiday->start_date);
            
            // If it's an annual holiday and the date has passed this year, set it to next year
            if ($holiday->repeat_annually && $holidayStart->lt($today)) {
                $holidayStart->year($today->year + 1);
            }
            
            // Only include future holidays
            if ($holidayStart->gte($today) && $holidayStart->lte($endOfYear)) {
                $daysAway = $today->diffInDays($holidayStart);
                
                $upcomingHolidays[] = [
                    'title' => $holiday->holiday_title,
                    'date' => $holidayStart->format('M d, Y'),
                    'days_away' => $daysAway == 0 ? 'Today' : ($daysAway == 1 ? 'Tomorrow' : $daysAway . ' days')
                ];
            }
        }
        
        // Sort by date (closest first)
        usort($upcomingHolidays, function ($a, $b) {
            return Carbon::parse($a['date'])->lt(Carbon::parse($b['date'])) ? -1 : 1;
        });
        
        // Limit to 5 upcoming holidays
        $this->upcomingHolidays = array_slice($upcomingHolidays, 0, 5);
    }

    private function loadRecentActivities()
    {
        $userId = Auth::id();
        $employee = Employee::where('user_id', $userId)->first();
        
        if (!$employee) {
            return;
        }
        
        // Get attendance records with their punches
        $attendanceRecords = EmpAttendance::with(['punches' => function($query) {
            $query->orderBy('punch_datetime', 'asc');
        }])
        ->where('employee_id', $employee->id)
        ->orderBy('work_date', 'desc')
        ->limit(10)
        ->get();
        
        $recentActivities = [];
        
        foreach ($attendanceRecords as $record) {
            $workDate = Carbon::parse($record->work_date);
            $today = Carbon::today();
            $yesterday = Carbon::yesterday();
            
            if ($workDate->isSameDay($today)) {
                $dateLabel = 'Today';
            } elseif ($workDate->isSameDay($yesterday)) {
                $dateLabel = 'Yesterday';
            } else {
                $dateLabel = $workDate->format('l');
            }
            
            // Get first punch in and last punch out
            $firstPunchIn = $record->punches->where('in_out', 'in')->first();
            $lastPunchOut = $record->punches->where('in_out', 'out')->last();
            
            $checkIn = $firstPunchIn ? Carbon::parse($firstPunchIn->punch_datetime)->format('h:i A') : 'N/A';
            $checkOut = $lastPunchOut ? Carbon::parse($lastPunchOut->punch_datetime)->format('h:i A') : 'N/A';
            
            $hours = 'N/A';
            if ($firstPunchIn && $lastPunchOut) {
                $checkInTime = Carbon::parse($firstPunchIn->punch_datetime);
                $checkOutTime = Carbon::parse($lastPunchOut->punch_datetime);
                $hours = round($checkOutTime->diffInMinutes($checkInTime) / 60, 1);
            }
            
            $status = $record->attendance_status_main;
            if ($status === 'P') {
                if ($record->is_late) {
                    $status = 'Late';
                } else {
                    $status = 'Present';
                }
            } elseif ($status === 'A') {
                $status = 'Absent';
            } elseif ($status === 'L') {
                $status = 'Leave';
            } else {
                $status = 'Unknown';
            }

            // Get all punches for the day
            $allPunches = $record->punches->map(function($punch) {
                $geoLocation = 'Location not available';
                
                if ($punch->punch_geo_location) {
                    $location = is_array($punch->punch_geo_location) ? $punch->punch_geo_location : json_decode($punch->punch_geo_location, true);
                    if ($location && isset($location['latitude']) && isset($location['longitude'])) {
                        $geoLocation = "Lat: {$location['latitude']}, Long: {$location['longitude']}";
                    }
                }
                
                return [
                    'time' => Carbon::parse($punch->punch_datetime)->format('h:i A'),
                    'type' => ucfirst($punch->in_out),
                    'location' => $geoLocation
                ];
            })->toArray();
            
            $recentActivities[] = [
                'date_label' => $dateLabel,
                'date' => $workDate->format('M d, Y'),
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'hours' => $hours,
                'status' => $status,
                'all_punches' => $allPunches
            ];
        }
        
        $this->recentActivities = $recentActivities;
    }

    private function prepareChartData()
    {
        $userId = Auth::id();
        $employee = Employee::where('user_id', $userId)->first();
        
        if (!$employee) {
            return;
        }
        
        $date = Carbon::parse($this->selectedDate);
        $startOfMonth = $date->copy()->startOfMonth();
        $endOfMonth = $date->copy()->endOfMonth();
        
        // Get all attendance records for the month
        $attendanceRecords = EmpAttendance::where('employee_id', $employee->id)
            ->whereBetween('work_date', [$startOfMonth->format('Y-m-d'), $endOfMonth->format('Y-m-d')])
            ->get()
            ->keyBy('work_date');
        
        // Get employee's work shift to determine working days
        $empWorkShift = EmpWorkShift::where('employee_id', $employee->id)
            ->where(function($query) use ($startOfMonth) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', $startOfMonth);
            })
            ->where('start_date', '<=', $endOfMonth)
            ->with(['work_shift.work_shifts_algos'])
            ->first();

        $chartData = collect();
        $currentDate = $startOfMonth->copy();
        
        while ($currentDate->lte($endOfMonth)) {
            $dateKey = $currentDate->format('Y-m-d');
            $record = $attendanceRecords->get($dateKey);
            
            $isWorkingDay = true;
            if ($empWorkShift) {
                $workShiftAlgo = $empWorkShift->work_shift->work_shifts_algos()
                    ->where('is_inactive', 0)
                    ->first();

                if ($workShiftAlgo) {
                    $weekOffPattern = json_decode($workShiftAlgo->week_off_pattern, true) ?? [];
                    $isWorkingDay = $this->isWorkingDay($currentDate, $weekOffPattern, $empWorkShift);
                }
            }
            
            $data = [
                'date' => $currentDate->format('M d'),
                'present' => false,
                'late' => false,
                'absent' => false
            ];
            
            if ($isWorkingDay) {
                if ($record) {
                    if ($record->attendance_status_main === 'P') {
                        if ($record->is_late) {
                            $data['late'] = true;
                        } else {
                            $data['present'] = true;
                        }
                    } elseif ($record->attendance_status_main === 'A') {
                        $data['absent'] = true;
                    }
                } else {
                    // If it's a working day but no record exists and the date is in the past
                    if ($currentDate->lt(Carbon::today())) {
                        $data['absent'] = true;
                    }
                }
            }
            
            $chartData->push($data);
            $currentDate->addDay();
        }
        
        $this->chartData = $chartData;
    }

    private function isWorkingDay($date, $weekOffPattern, $empWorkShift)
    {
        if (empty($weekOffPattern)) {
            return !$date->isWeekend();
        }

        $type = $weekOffPattern['type'] ?? '';
        
        switch ($type) {
            case 'fixed_weekly':
                return !in_array($date->dayOfWeek, $weekOffPattern['fixed_weekly']['off_days'] ?? []);
                
            case 'rotating':
                $cycle = $weekOffPattern['rotating']['cycle'] ?? [];
                if (!empty($cycle)) {
                    $daysFromStart = $date->diffInDays($empWorkShift->start_date);
                    $cycleLength = count($cycle);
                    $cyclePosition = ($daysFromStart + ($weekOffPattern['rotating']['offset'] ?? 0)) % $cycleLength;
                    return $cycle[$cyclePosition] !== 1;
                }
                break;
                
            case 'holiday_calendar':
                // Check against holiday calendar
                if (isset($weekOffPattern['holiday_calendar']['id'])) {
                    $isHoliday = Holiday::where('holiday_calendar_id', $weekOffPattern['holiday_calendar']['id'])
                        ->where('start_date', '<=', $date)
                        ->where(function($query) use ($date) {
                            $query->whereNull('end_date')
                                ->orWhere('end_date', '>=', $date);
                        })
                        ->exists();
                    return !$isHoliday;
                }
                break;
                
            case 'combined':
                // Check both fixed weekly and holiday calendar
                $isWeekOff = in_array($date->dayOfWeek, $weekOffPattern['fixed_weekly']['off_days'] ?? []);
                if ($isWeekOff) {
                    return false;
                }
                
                if (isset($weekOffPattern['holiday_calendar']['id'])) {
                    $isHoliday = Holiday::where('holiday_calendar_id', $weekOffPattern['holiday_calendar']['id'])
                        ->where('start_date', '<=', $date)
                        ->where(function($query) use ($date) {
                            $query->whereNull('end_date')
                                ->orWhere('end_date', '>=', $date);
                        })
                        ->exists();
                    return !$isHoliday;
                }
                break;
        }
        
        // Check exceptions
        if (isset($weekOffPattern['exceptions']) && in_array($date->format('Y-m-d'), $weekOffPattern['exceptions'])) {
            return false;
        }
        
        return true;
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Attendance/blades/employee-attendance.blade.php'));
    }
}
