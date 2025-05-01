<?php

namespace App\Livewire\Hrms\Reports\AttendanceReports\Exports;

use App\Models\Hrms\EmpAttendance;
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

        foreach ($this->dateRange as $date) {
            $headers[] = Carbon::parse($date)->format('d-M');
        }

        $headers[] = 'Total Present';

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
        $totalPresent = 0;

        foreach ($this->dateRange as $date) {
            $status = $attendances[$date]->attendance_status_main ?? '';
            if ($status === 'P') $totalPresent++;
            $row[] = $status;
        }

        $row[] = $totalPresent;

//        dd($row);

        return $row;
    }
}
