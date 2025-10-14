<?php

namespace App\Livewire\Hrms\Taxation;

use App\Models\Hrms\Employee;
use App\Models\Hrms\EmployeesSalaryExecutionGroup;
use App\Models\Hrms\PayrollComponentsEmployeesTrack;
use App\Models\Hrms\PayrollSlot;
use App\Models\Hrms\EmployeeJobProfile;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;
use Livewire\Attributes\Computed;

class EmployeeSalaryDetails extends Component
{
    public array $filters = [
        'employees' => '',
        'email' => '',
        'phone' => '',
    ];

    public string $viewMode = 'table';

    public $allEmployees;

    public int $perPage = 50;
    public int $page = 1;

    public function updating($name, $value)
    {
        if (str_starts_with($name, 'filters.')) {
            $this->page = 1;
            // also refresh cached filtered collection reference
            $this->refreshEmployeesCache();
        }
    }

    public function mount(): void
    {
        $this->loadAllEmployees();
    }

    protected function loadAllEmployees(): void
    {
        $firmId = (int) session('firm_id');
        $cacheKey = "salary_employees_{$firmId}";

        $this->allEmployees = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($firmId) {
            return Employee::query()
                ->where('employees.firm_id', $firmId)
                ->leftJoin('employee_job_profiles as ejp', function($j) {
                    $j->on('ejp.employee_id', '=', 'employees.id')
                      ->whereNull('ejp.deleted_at');
                })
                ->select(['employees.id', 'employees.fname', 'employees.mname', 'employees.lname', 'employees.email', 'employees.phone', 'ejp.doh'])
                ->orderBy('employees.fname')
                ->get();
        });
    }

    public function refreshEmployeesCache(): void
    {
        $firmId = (int) session('firm_id');
        $cacheKey = "salary_employees_{$firmId}";
        Cache::forget($cacheKey);
        $this->loadAllEmployees();
    }

    #[Computed]
    public function filteredEmployees()
    {
        if (!$this->allEmployees) {
            $this->loadAllEmployees();
        }

        $nameQuery = strtolower(trim($this->filters['employees'] ?? ''));
        $emailQuery = strtolower(trim($this->filters['email'] ?? ''));
        $phoneQuery = strtolower(trim($this->filters['phone'] ?? ''));

        return $this->allEmployees->filter(function ($emp) use ($nameQuery, $emailQuery, $phoneQuery) {
            $matchesName = true;
            $matchesEmail = true;
            $matchesPhone = true;

            if ($nameQuery !== '') {
                $fullName = strtolower(trim(($emp->fname ?? '') . ' ' . ($emp->mname ?? '') . ' ' . ($emp->lname ?? '')));
                $matchesName = str_contains($fullName, $nameQuery);
            }

            if ($emailQuery !== '') {
                $email = strtolower((string) ($emp->email ?? ''));
                $matchesEmail = str_contains($email, $emailQuery);
            }

            if ($phoneQuery !== '') {
                $phone = strtolower((string) ($emp->phone ?? ''));
                $matchesPhone = str_contains($phone, $phoneQuery);
            }

            return $matchesName && $matchesEmail && $matchesPhone;
        })->values();
    }

    #[Computed]
    public function employeesPage()
    {
        $collection = $this->filteredEmployees();
        $offset = max(0, ($this->page - 1) * $this->perPage);
        return $collection->slice($offset, $this->perPage)->values();
    }

    #[Computed]
    public function totalEmployeesCount(): int
    {
        return $this->filteredEmployees()->count();
    }

    public function nextPage(): void
    {
        $total = $this->totalEmployeesCount();
        if ($this->page * $this->perPage < $total) {
            $this->page += 1;
        }
    }

    public function prevPage(): void
    {
        if ($this->page > 1) {
            $this->page -= 1;
        }
    }

    public function getEmployeeSalaryMonths(int $employeeId): array
    {
        $firmId = (int) session('firm_id');
        $fyStart = Carbon::parse(session('fy_start'));
        $fyEnd = Carbon::parse(session('fy_end'));

        $cacheKey = "emp_salary_months_{$firmId}_{$employeeId}_{$fyStart->format('Ymd')}_{$fyEnd->format('Ymd')}";
        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($firmId, $employeeId, $fyStart, $fyEnd) {
            return $this->buildEmployeeSalaryMonths($firmId, $employeeId, $fyStart, $fyEnd);
        });
    }

    protected function buildEmployeeSalaryMonths(int $firmId, int $employeeId, Carbon $fyStart, Carbon $fyEnd): array
    {
        // Employee joining date (Date of Hire)
        $doh = EmployeeJobProfile::where('employee_id', $employeeId)
            ->where('firm_id', $firmId)
            ->value('doh');
        $doh = $doh ? Carbon::parse($doh) : $fyStart;

        // Execution group slots in FY
        $executionGroupId = EmployeesSalaryExecutionGroup::where('firm_id', $firmId)
            ->where('employee_id', $employeeId)
            ->value('salary_execution_group_id');

        $slots = PayrollSlot::where('firm_id', $firmId)
            ->when($executionGroupId, fn($q) => $q->where('salary_execution_group_id', $executionGroupId))
            ->whereBetween('from_date', [$fyStart, $fyEnd])
            ->orderBy('from_date')
            ->get();

        // Determine a fallback projected monthly earning as average of completed months
        $completed = $slots->where('payroll_slot_status', 'CM');
        $completedSum = 0;
        $completedCount = 0;
        foreach ($completed as $slot) {
            // Sum taxable earnings for slot
            $sum = PayrollComponentsEmployeesTrack::where('firm_id', $firmId)
                ->where('employee_id', $employeeId)
                ->where('payroll_slot_id', $slot->id)
                ->where('nature', 'earning')
                ->where('taxable', true)
                ->sum('amount_payable');
            if ($sum > 0) {
                $completedSum += $sum;
                $completedCount += 1;
            }
        }
        $avgMonthly = $completedCount > 0 ? round($completedSum / $completedCount) : 0;

        $months = [];
        foreach ($slots as $slot) {
            // Respect joining date: ignore slots entirely before joining month
            if (Carbon::parse($slot->to_date)->lt($doh)) {
                continue;
            }
            $label = strtoupper(Carbon::parse($slot->from_date)->format('M')) . ' ' . Carbon::parse($slot->from_date)->format('Y');
            $actual = PayrollComponentsEmployeesTrack::where('firm_id', $firmId)
                ->where('employee_id', $employeeId)
                ->where('payroll_slot_id', $slot->id)
                ->where('nature', 'earning')
                ->where('taxable', true)
                ->sum('amount_payable');
            // Simple breakup: taxable earnings vs (planned estimate)
            $earningBreakup = $actual > 0 ? [
                'taxable_earnings' => (float) $actual,
            ] : [];
            $planned = 0;
            if ($actual == 0 && ($slot->payroll_slot_status !== 'CM' || Carbon::parse($slot->to_date)->gt(now()))) {
                $planned = $avgMonthly;
            }
            $months[] = [
                'label' => $label,
                'actual' => (float) $actual,
                'planned' => (float) $planned,
                'status' => $slot->payroll_slot_status,
                'breakup' => $earningBreakup,
            ];
        }

        // Summary
        $ytdPaid = collect($months)->sum('actual');
        $remainingPlanned = collect($months)->where('actual', 0)->sum('planned');

        return [
            'ytd_paid' => (float) $ytdPaid,
            'remaining_planned' => (float) $remainingPlanned,
            'months' => $months,
        ];
    }

    protected function monthsHeader(): array
    {
        $fyStart = Carbon::parse(session('fy_start'))->startOfMonth();
        $labels = [];
        for ($i = 0; $i < 12; $i++) {
            $labels[] = strtoupper($fyStart->copy()->addMonths($i)->format('M')) . ' ' . $fyStart->copy()->addMonths($i)->format('Y');
        }
        return $labels;
    }

    #[Computed]
    public function tableRows()
    {
        $labels = $this->monthsHeader();
        $rows = [];
        foreach ($this->employeesPage as $emp) {
            $summary = $this->getEmployeeSalaryMonths($emp->id);
            $monthsByLabel = collect($summary['months'])->keyBy('label');
            $cells = [];
            foreach ($labels as $label) {
                $cell = $monthsByLabel->get($label);
                if ($cell) {
                    $cells[] = $cell;
                } else {
                    $cells[] = [
                        'label' => $label,
                        'actual' => 0.0,
                        'planned' => 0.0,
                        'status' => '-',
                        'breakup' => [],
                    ];
                }
            }
            $rows[] = [
                'emp' => $emp,
                'ytd_paid' => $summary['ytd_paid'],
                'remaining_planned' => $summary['remaining_planned'],
                'cells' => $cells,
            ];
        }
        return [
            'labels' => $labels,
            'rows' => $rows,
        ];
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Taxation/employee-salary-records.blade.php'), [
            'tableRows' => $this->tableRows,
        ]);
    }
}

