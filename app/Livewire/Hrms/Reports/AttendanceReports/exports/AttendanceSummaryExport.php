<?php

namespace App\Livewire\Hrms\Reports\AttendanceReports\Exports;

use App\Models\Hrms\EmpAttendance;
use App\Models\Hrms\EmpAttendanceStatuses;
use App\Models\Hrms\Employee;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Carbon\Carbon;

class AttendanceSummaryExport implements FromCollection, WithHeadings, WithMapping
{
    protected $filters;
    protected $dateRange = [];
    protected $data = [];
    protected $start;
    protected $end;
    protected array $firmStatusLabels = [];
    
    // Preserve the default order used originally for summary columns
    protected array $defaultStatusOrder = [
        'P', 'A', 'L', 'WFR', 'HD', 'PW', 'OD', 'H', 'W', 'S', 'POW', 'LM', 'NM', 'CW'
    ];
    
    // Define all attendance statuses with their labels
    protected $statuses = [
        'P' => 'Present',
        'A' => 'Absent',
        'HD' => 'Half Day',
        'PW' => 'Present (Work From Home)',
        'L' => 'Leave',
        'WFR' => 'Week Off',
        'CW' => 'Compensatory Work',
        'OD' => 'On Duty',
        'H' => 'Holiday',
        'W' => 'Weekend',
        'S' => 'Sunday',
        'POW' => 'Present (Overtime Work)',
        'LM' => 'Late Marked',
        'NM' => 'Not Marked'
    ];

    public function __construct($filters)
    {
        $this->filters = $filters;

        if ($this->filters['date_range']) {
            try {
                $this->start = Carbon::parse($this->filters['date_range']['start'])->startOfDay();
                $this->end = Carbon::parse($this->filters['date_range']['end'])->endOfDay();
            } catch (\Exception $e) {
                \Log::error("Invalid date range: {$this->filters['date_range']}");
            }
        }

        // Extend statuses with firm-specific attendance main codes (active)
        try {
            $firmId = session('firm_id');
            if ($firmId) {
                $firmMainCodes = EmpAttendanceStatuses::byFirm($firmId)
                    ->active()
                    ->pluck('attendance_status_main')
                    ->unique()
                    ->values()
                    ->all();

                // Load firm-specific status labels keyed by status ID for label-wise totals
                $this->firmStatusLabels = EmpAttendanceStatuses::byFirm($firmId)
                    ->active()kernel
                    ->orderBy('attendance_status_label')
                    ->pluck('attendance_status_label', 'id')
                    ->toArray();

                // Build a label map using EmpAttendance constants as source of truth, fallback to model options, then code itself
                $labelFromEmpAttendance = EmpAttendance::ATTENDANCE_STATUS_MAIN_SELECT ?? [];
                $labelFromStatusesModel = EmpAttendanceStatuses::ATTENDANCE_STATUS_MAIN_OPTIONS ?? [];

                foreach ($firmMainCodes as $code) {
                    if (!isset($this->statuses[$code])) {
                        $label = $labelFromEmpAttendance[$code] ?? ($labelFromStatusesModel[$code] ?? $code);
                        $this->statuses[$code] = $label;
                    }
                }
            }
        } catch (\Throwable $t) {
            // Non-fatal: if anything goes wrong, keep default statuses
            \Log::warning('AttendanceSummaryExport: unable to extend statuses for firm: ' . $t->getMessage());
        }

        for ($date = $this->start->copy(); $date->lte($this->end); $date->addDay()) {
            $this->dateRange[] = $date->format('Y-m-d');
        }
    }

    public function collection_old()
    {
        $query = Employee::with([
            'emp_job_profile.department',
            'emp_job_profile.employment_type',
            'emp_job_profile.joblocation',
            'emp_attendances' => fn($q) => $q
                ->whereBetween('work_date', [$this->start, $this->end])
        ])
            ->where('firm_id', session('firm_id'));

//        dd($this->filters);
        if (!empty($this->filters['employee_id'])) {
            $query->where('id', $this->filters['employee_id']);
        }

        return $this->data = $query->get();
    }

    public function collection()
    {
        $query = Employee::with([
            'emp_job_profile.department',
            'emp_job_profile.employment_type',
            'emp_job_profile.joblocation',
            'emp_attendances' => fn($q) => $q
                ->whereBetween('work_date', [$this->filters['date_range']['start'], $this->filters['date_range']['end']])
        ])
            ->where('firm_id', session('firm_id'));

        // Apply employee_id filter (multiple values)
        if (!empty($this->filters['employee_id'])) {
            $query->whereIn('id', $this->filters['employee_id']);
        }

        // Apply department_id filter (multiple values)
        if (!empty($this->filters['department_id'])) {
            $query->whereHas('emp_job_profile.department', function ($q) {
                $q->whereIn('id', $this->filters['department_id']);
            });
        }

        // Apply joblocation_id filter (multiple values)
        if (!empty($this->filters['joblocation_id'])) {
            $query->whereHas('emp_job_profile.joblocation', function ($q) {
                $q->whereIn('id', $this->filters['joblocation_id']);
            });
        }

        // Apply employment_type_id filter (multiple values)
        if (!empty($this->filters['employment_type_id'])) {
            $query->whereHas('emp_job_profile.employment_type', function ($q) {
                $q->whereIn('id', $this->filters['employment_type_id']);
            });
        }

        return $this->data = $query->get();
    }

    public function headings(): array
    {
        $headers = [
            'Employee Code', 'Employee Name', 'Location', 'Department', 'Employment Type',
        ];

        // Add date columns
        foreach ($this->dateRange as $date) {
            $dt = Carbon::parse($date);
            $headers[] = '[' . $dt->format('D') . '] ' . $dt->format('d-M-Y');
        }

        // Add summary columns
        $headers[] = 'Total Days';
        $headers[] = 'Total Present';
        $headers[] = 'Total Absent';
        $headers[] = 'Total Leave';
        $headers[] = 'Total Week Off';
        $headers[] = 'Total Half Day';
        $headers[] = 'Total Work From Home';
        $headers[] = 'Total On Duty';
        $headers[] = 'Total Holiday';
        $headers[] = 'Total Weekend';
        $headers[] = 'Total Sunday';
        $headers[] = 'Total Overtime';
        $headers[] = 'Total Late Marked';
        $headers[] = 'Total Not Marked';
        $headers[] = 'Total Compensatory Work';

        // Append totals for each active firm-specific status label (granular statuses)
        foreach ($this->firmStatusLabels as $statusId => $label) {
            $headers[] = 'Total ' . $label;
        }

        return $headers;
    }

    public function map($employee): array
    {
        $job = $employee->emp_job_profile;
        $row = [
            $job->employee_code ?? '',
            trim("{$employee->fname} {$employee->lname}"),
            $job->joblocation->name ?? '',
            $job->department->title ?? '',
            $job->employment_type->title ?? '',
        ];

        $attendances = $employee->emp_attendances->keyBy(fn($a) => $a->work_date->format('Y-m-d'));
        
        // Initialize counters
        $statusCounts = array_fill_keys(array_keys($this->statuses), 0);
        $labelCounts = array_fill_keys(array_keys($this->firmStatusLabels), 0);
        $totalDays = count($this->dateRange);

        // Process each date
        foreach ($this->dateRange as $date) {
            $attendance = $attendances[$date] ?? null;
            $status = $attendance ? $attendance->attendance_status_main : 'NM';
            
            // Add status to row for date column
            $row[] = $status;
            
            // Count statuses
            if (isset($statusCounts[$status])) {
                $statusCounts[$status]++;
            }

            // Count label-wise (by specific EmpAttendanceStatuses id)
            if ($attendance && $attendance->emp_attendance_status_id && isset($labelCounts[$attendance->emp_attendance_status_id])) {
                $labelCounts[$attendance->emp_attendance_status_id]++;
            }
        }

        // Add summary counts
        $row[] = $totalDays; // Total Days
        $row[] = $statusCounts['P'] ?? 0; // Total Present
        $row[] = $statusCounts['A'] ?? 0; // Total Absent
        $row[] = $statusCounts['L'] ?? 0; // Total Leave
        $row[] = $statusCounts['WFR'] ?? 0; // Total Week Off
        $row[] = $statusCounts['HD'] ?? 0; // Total Half Day
        $row[] = $statusCounts['PW'] ?? 0; // Total Work From Home
        $row[] = $statusCounts['OD'] ?? 0; // Total On Duty
        $row[] = $statusCounts['H'] ?? 0; // Total Holiday
        $row[] = $statusCounts['W'] ?? 0; // Total Weekend
        $row[] = $statusCounts['S'] ?? 0; // Total Sunday
        $row[] = $statusCounts['POW'] ?? 0; // Total Overtime
        $row[] = $statusCounts['LM'] ?? 0; // Total Late Marked
        $row[] = $statusCounts['NM'] ?? 0; // Total Not Marked
        $row[] = $statusCounts['CW'] ?? 0; // Total Compensatory Work

        // Append label-wise totals for each active firm-specific status
        foreach ($this->firmStatusLabels as $statusId => $label) {
            $row[] = $labelCounts[$statusId] ?? 0;
        }

        return $row;
    }
}
