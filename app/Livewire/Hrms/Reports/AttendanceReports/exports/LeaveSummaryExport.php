<?php

namespace App\Livewire\Hrms\Reports\AttendanceReports\Exports;

use App\Models\Hrms\EmpLeaveBalance;
use App\Models\Hrms\Employee;
use App\Models\Hrms\LeaveType;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Carbon\Carbon;

class LeaveSummaryExport implements FromCollection, WithHeadings, WithMapping
{
    protected $filters;
    protected $today;
    protected Collection $leaveTypes;
    protected Collection $employees;
    protected array $balancesByEmployee = [];

    public function __construct($filters)
    {
        $this->filters = $filters ?? [];
        $this->today = Carbon::today();

        $this->leaveTypes = LeaveType::query()
            ->where('firm_id', session('firm_id'))
            ->where(function ($q) {
                $q->whereNull('is_inactive')->orWhere('is_inactive', false);
            })
            ->orderBy('leave_title')
            ->get(['id', 'leave_title', 'leave_code']);
    }

    public function collection()
    {
        $query = Employee::with([
            'emp_job_profile.department',
            'emp_job_profile.employment_type',
            'emp_job_profile.joblocation',
        ])->where('firm_id', session('firm_id'));

        if (!empty($this->filters['employee_id'])) {
            $query->whereIn('id', (array) $this->filters['employee_id']);
        }

        if (!empty($this->filters['department_id'])) {
            $query->whereHas('emp_job_profile.department', function ($q) {
                $q->whereIn('id', (array) $this->filters['department_id']);
            });
        }

        if (!empty($this->filters['joblocation_id'])) {
            $query->whereHas('emp_job_profile.joblocation', function ($q) {
                $q->whereIn('id', (array) $this->filters['joblocation_id']);
            });
        }

        if (!empty($this->filters['employment_type_id'])) {
            $query->whereHas('emp_job_profile.employment_type', function ($q) {
                $q->whereIn('id', (array) $this->filters['employment_type_id']);
            });
        }

        $this->employees = $query->get();

        // Preload balances for all selected employees for current date window
        $employeeIds = $this->employees->pluck('id')->all();
        if (!empty($employeeIds)) {
            $balances = EmpLeaveBalance::query()
                ->where('firm_id', session('firm_id'))
                ->whereIn('employee_id', $employeeIds)
                ->whereDate('period_start', '<=', $this->today)
                ->whereDate('period_end', '>=', $this->today)
                ->get([
                    'employee_id',
                    'leave_type_id',
                    'allocated_days',
                    'consumed_days',
                    'carry_forwarded_days',
                    'lapsed_days',
                    'balance',
                ]);

            foreach ($balances as $row) {
                $this->balancesByEmployee[$row->employee_id][$row->leave_type_id] = [
                    'allocated' => (float) ($row->allocated_days ?? 0),
                    'carry_forwarded' => (float) ($row->carry_forwarded_days ?? 0),
                    'consumed' => (float) ($row->consumed_days ?? 0),
                    'lapsed' => (float) ($row->lapsed_days ?? 0),
                    'balance' => (float) ($row->balance ?? 0),
                ];
            }
        }

        return $this->employees;
    }

    public function headings(): array
    {
        $headers = [
            'Employee Code', 'Employee Name', 'Location', 'Department', 'Employment Type',
        ];

        foreach ($this->leaveTypes as $lt) {
            $title = $lt->leave_title;
            $headers[] = $title . ' - Allocated';
            $headers[] = $title . ' - Carry Fwd';
            $headers[] = $title . ' - Consumed';
            $headers[] = $title . ' - Lapsed';
            $headers[] = $title . ' - Balance';
        }

        $headers[] = 'Total Allocated';
        $headers[] = 'Total Carry Fwd';
        $headers[] = 'Total Consumed';
        $headers[] = 'Total Lapsed';
        $headers[] = 'Total Balance';

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

        $totals = [
            'allocated' => 0.0,
            'carry_forwarded' => 0.0,
            'consumed' => 0.0,
            'lapsed' => 0.0,
            'balance' => 0.0,
        ];

        $employeeBalances = $this->balancesByEmployee[$employee->id] ?? [];

        foreach ($this->leaveTypes as $lt) {
            $b = $employeeBalances[$lt->id] ?? [
                'allocated' => 0.0,
                'carry_forwarded' => 0.0,
                'consumed' => 0.0,
                'lapsed' => 0.0,
                'balance' => 0.0,
            ];

            $row[] = $b['allocated'];
            $row[] = $b['carry_forwarded'];
            $row[] = $b['consumed'];
            $row[] = $b['lapsed'];
            $row[] = $b['balance'];

            $totals['allocated'] += $b['allocated'];
            $totals['carry_forwarded'] += $b['carry_forwarded'];
            $totals['consumed'] += $b['consumed'];
            $totals['lapsed'] += $b['lapsed'];
            $totals['balance'] += $b['balance'];
        }

        $row[] = $totals['allocated'];
        $row[] = $totals['carry_forwarded'];
        $row[] = $totals['consumed'];
        $row[] = $totals['lapsed'];
        $row[] = $totals['balance'];

        return $row;
    }
}


