<?php

namespace App\Livewire\Hrms\Payroll;

use App\Models\Hrms\Employee;
use App\Models\Hrms\EmployeesSalaryExecutionGroup;
use App\Models\Hrms\PayrollComponentsEmployeesTrack;
use App\Models\Hrms\PayrollSlot;
use App\Models\Hrms\SalaryComponent;
use App\Models\Hrms\SalaryComponentsEmployee;
use App\Models\Hrms\EmployeeTaxRegime;
use App\Models\Hrms\EmployeeJobProfile;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;
use Livewire\Attributes\Computed;

class TdsCheckScreen extends Component
{
    public array $filters = [
        'employees' => '',
        'email' => '',
        'phone' => '',
    ];

    public string $viewMode = 'table';

    /**
     * Cached base employees list for fast in-memory filtering
     */
    public $allEmployees;

    /**
     * In-memory pagination controls
     */
    public int $perPage = 100;
    public int $page = 1;

    public function updating($name, $value)
    {
        if (str_starts_with($name, 'filters.')) {
            $this->page = 1;
        }
    }

    public function mount(): void
    {
        $this->loadAllEmployees();
    }

    protected function loadAllEmployees(): void
    {
        $firmId = (int) session('firm_id');
        $cacheKey = "tds_employees_{$firmId}";

        $this->allEmployees = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($firmId) {
            return Employee::query()
                ->where('firm_id', $firmId)
                ->select(['id', 'fname', 'mname', 'lname', 'email', 'phone'])
                ->orderBy('fname')
                ->get();
        });
    }

    public function refreshEmployeesCache(): void
    {
        $firmId = (int) session('firm_id');
        $cacheKey = "tds_employees_{$firmId}";
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

    public function getEmployeeTdsSummary(int $employeeId): array
    {
        $firmId = (int) session('firm_id');
        $fyStart = Carbon::parse(session('fy_start'));
        $fyEnd = Carbon::parse(session('fy_end'));

        // Resolve TDS component dynamically by type for this firm
        $tdsComponent = SalaryComponent::where('firm_id', $firmId)
            ->where('component_type', 'tds')
            ->first();
        $tdsComponentId = $tdsComponent?->id;
        if (!$tdsComponentId) {
            return [
                'annual_salary' => 0,
                'standard_deduction' => 0,
                'taxable_income' => 0,
                'annual_applicable' => 0,
                'ytd_deducted' => 0,
                'remaining' => 0,
                'per_month_plan' => 0,
                'months' => [],
            ];
        }

        // Use the same calculation method as EmployeeSalaryDetails
        $salaryData = $this->buildEmployeeSalaryMonths($firmId, $employeeId, $fyStart, $fyEnd);
        
        // Calculate annual salary from YTD paid + remaining planned
        $annualSalary = $salaryData['ytd_paid'] + $salaryData['remaining_planned'];
        
        // Apply standard deduction
        $standardDeduction = 75000;
        $taxableIncome = max(0, $annualSalary - $standardDeduction);

        // Use proper tax calculation with EmployeeTaxRegime and TaxBracket (with breakdown)
        $taxCalc = $this->calculateProperTaxWithBreakdown($employeeId, $taxableIncome);
        $annualApplicable = $taxCalc['total'] ?? 0;

        $ytdDeducted = PayrollComponentsEmployeesTrack::join('payroll_slots', 'payroll_components_employees_tracks.payroll_slot_id', '=', 'payroll_slots.id')
            ->where('payroll_components_employees_tracks.firm_id', $firmId)
            ->where('payroll_components_employees_tracks.employee_id', $employeeId)
            ->where('payroll_components_employees_tracks.salary_component_id', $tdsComponentId)
            ->whereBetween('payroll_components_employees_tracks.salary_period_from', [$fyStart, $fyEnd])
            ->where('payroll_slots.payroll_slot_status', 'CM')
            ->sum('payroll_components_employees_tracks.amount_payable');

        $remaining = max(0, $annualApplicable - $ytdDeducted);

        // Build months list from employee's execution group slots
        $executionGroupId = EmployeesSalaryExecutionGroup::where('firm_id', $firmId)
            ->where('employee_id', $employeeId)
            ->value('salary_execution_group_id');

        $slots = PayrollSlot::where('firm_id', $firmId)
            ->when($executionGroupId, fn($q) => $q->where('salary_execution_group_id', $executionGroupId))
            ->whereBetween('from_date', [$fyStart, $fyEnd])
            ->orderBy('from_date')
            ->get();

        // Determine remaining slots strictly AFTER the last completed slot in FY
        $lastCompletedSlot = $slots
            ->filter(fn($s) => $s->payroll_slot_status === 'CM')
            ->sortBy('from_date')
            ->last();

        $remainingSlots = $slots
            ->filter(function ($s) use ($lastCompletedSlot) {
                if ($lastCompletedSlot) {
                    // Include slots that start after the last completed slot ends
                    return Carbon::parse($s->from_date)->gt(Carbon::parse($lastCompletedSlot->to_date));
                }
                return true; // if no completed slot, all slots are remaining
            })
            ->filter(fn($s) => $s->payroll_slot_status !== 'CM'); // Only include non-completed slots

        $remainingCount = max(1, $remainingSlots->count());

        $perMonthPlan = $this->roundOffTax($remainingCount > 0 ? ($remaining / $remainingCount) : 0);

        $months = [];
        foreach ($slots as $slot) {
            $label = strtoupper(Carbon::parse($slot->from_date)->format('M')) . ' ' . Carbon::parse($slot->from_date)->format('Y');
            $actual = PayrollComponentsEmployeesTrack::where('firm_id', $firmId)
                ->where('employee_id', $employeeId)
                ->where('payroll_slot_id', $slot->id)
                ->where('salary_component_id', $tdsComponentId)
                ->sum('amount_payable');
            $planned = ($actual == 0 && ($slot->payroll_slot_status !== 'CM' || $slot->to_date > Carbon::now())) ? $perMonthPlan : 0;
            $months[] = [
                'label' => $label,
                'actual' => (float) $actual,
                'planned' => (float) $planned,
            ];
        }

        return [
            'annual_salary' => (float) $annualSalary,
            'standard_deduction' => (float) $standardDeduction,
            'taxable_income' => (float) $taxableIncome,
            'annual_applicable' => (float) $annualApplicable,
            'tax_breakdown' => $taxCalc,
            'ytd_deducted' => (float) $ytdDeducted,
            'remaining' => (float) $remaining,
            'per_month_plan' => (float) $perMonthPlan,
            'months' => $months,
        ];
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
            // Sum earnings for slot including taxable earnings and employee contributions
            $employeeContributionIds = SalaryComponent::where('firm_id', $firmId)
                ->where('component_type', 'employee_contribution')
                ->pluck('id');

            $sum = PayrollComponentsEmployeesTrack::where('firm_id', $firmId)
                ->where('employee_id', $employeeId)
                ->where('payroll_slot_id', $slot->id)
                ->where('nature', 'earning')
                ->where(function ($q) use ($employeeContributionIds) {
                    $q->where('taxable', true)
                      ->orWhereIn('salary_component_id', $employeeContributionIds);
                })
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
            $employeeContributionIds = SalaryComponent::where('firm_id', $firmId)
                ->where('component_type', 'employee_contribution')
                ->pluck('id');

            $actual = PayrollComponentsEmployeesTrack::where('firm_id', $firmId)
                ->where('employee_id', $employeeId)
                ->where('payroll_slot_id', $slot->id)
                ->where('nature', 'earning')
                ->where(function ($q) use ($employeeContributionIds) {
                    $q->where('taxable', true)
                      ->orWhereIn('salary_component_id', $employeeContributionIds);
                })
                ->sum('amount_payable');
            
            $planned = 0;
            if ($actual == 0 && ($slot->payroll_slot_status !== 'CM' || Carbon::parse($slot->to_date)->gt(now()))) {
                // Build a projected breakup from employee's active earning components (taxable) for this month
                $fromDate = Carbon::parse($slot->from_date)->startOfDay();
                $toDate = Carbon::parse($slot->to_date)->endOfDay();

                $employeeContributionIds = SalaryComponent::where('firm_id', $firmId)
                    ->where('component_type', 'employee_contribution')
                    ->pluck('id');

                $components = SalaryComponentsEmployee::query()
                    ->where('firm_id', $firmId)
                    ->where('employee_id', $employeeId)
                    ->where('nature', 'earning')
                    ->where(function ($q) use ($employeeContributionIds) {
                        $q->where('taxable', true)
                          ->orWhereIn('salary_component_id', $employeeContributionIds);
                    })
                    // component is active in the month window (date range overlaps)
                    ->whereDate('effective_from', '<=', $toDate)
                    ->where(function ($q) use ($fromDate) {
                        $q->whereNull('effective_to')
                          ->orWhereDate('effective_to', '>=', $fromDate);
                    })
                    ->get();

                foreach ($components as $comp) {
                    $amount = (float) ($comp->amount ?? 0);
                    if ($amount <= 0) {
                        continue;
                    }
                    $planned += $amount;
                }

                // Fallback to average if no active components found
                if ($planned <= 0 && $avgMonthly > 0) {
                    $planned = $avgMonthly;
                }
            }
            
            $months[] = [
                'label' => $label,
                'actual' => (float) $actual,
                'planned' => (float) $planned,
                'status' => $slot->payroll_slot_status,
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

    protected function roundOffTax($amount)
    {
        return round($amount / 10) * 10;
    }


    protected function calculateProperTax(int $employeeId, float $annualIncome): float
    {
        // 1. Get employee's tax regime
        $employeeTaxRegime = EmployeeTaxRegime::where('employee_id', $employeeId)
            ->where('firm_id', session('firm_id'))
            ->where(function ($query) {
                $query->whereNull('effective_to')
                    ->orWhere('effective_to', '>', now());
            })
            ->with('tax_regime.tax_brackets')
            ->first();

        if (!$employeeTaxRegime) {
            return 0; // No tax regime found
        }

        // 2. Get tax brackets for the regime
        $taxBrackets = $employeeTaxRegime->tax_regime->tax_brackets()
            ->where('type', 'SLAB')
            ->orderBy('income_from')
            ->get();

        // 3. Calculate tax for each slab using absolute taxable income
        $totalTax = 0;
        $totalTaxableIncome = $annualIncome;

        foreach ($taxBrackets as $bracket) {
            $slabFrom = $bracket->income_from;
            $slabTo = $bracket->income_to ?? PHP_FLOAT_MAX;

            if ($totalTaxableIncome > $slabFrom) {
                $incomeInThisSlab = min($totalTaxableIncome, $slabTo) - $slabFrom;
                if ($incomeInThisSlab > 0) {
                    $taxForSlab = round(($incomeInThisSlab * $bracket->rate) / 100);
                    $totalTax += $taxForSlab;
                }
            }
        }

        // 4. Calculate health and education cess
        $health_education_cess = 0.04 * $totalTax;

        // 5. Return total tax including cess
        return $totalTax + $health_education_cess;
    }

    protected function calculateProperTaxWithBreakdown(int $employeeId, float $annualIncome): array
    {
        $employeeTaxRegime = EmployeeTaxRegime::where('employee_id', $employeeId)
            ->where('firm_id', session('firm_id'))
            ->where(function ($query) {
                $query->whereNull('effective_to')
                    ->orWhere('effective_to', '>', now());
            })
            ->with('tax_regime.tax_brackets')
            ->first();

        if (!$employeeTaxRegime) {
            return [
                'total' => 0,
                'cess' => 0,
                'slabs' => [],
            ];
        }

        $taxBrackets = $employeeTaxRegime->tax_regime->tax_brackets()
            ->where('type', 'SLAB')
            ->orderBy('income_from')
            ->get();

        $totalTax = 0;
        $totalTaxableIncome = $annualIncome;
        $slabBreakdown = [];

        foreach ($taxBrackets as $bracket) {
            $slabFrom = $bracket->income_from;
            $slabTo = $bracket->income_to ?? PHP_FLOAT_MAX;

            $incomeInThisSlab = 0;
            $taxForSlab = 0;
            if ($totalTaxableIncome > $slabFrom) {
                $incomeInThisSlab = min($totalTaxableIncome, $slabTo) - $slabFrom;
                if ($incomeInThisSlab > 0) {
                    $taxForSlab = round(($incomeInThisSlab * $bracket->rate) / 100);
                    $totalTax += $taxForSlab;
                }
            }

            $slabBreakdown[] = [
                'from' => (float) $slabFrom,
                'to' => $slabTo === PHP_FLOAT_MAX ? null : (float) $slabTo,
                'rate' => (float) $bracket->rate,
                'income_in_slab' => (float) $incomeInThisSlab,
                'tax_for_slab' => (float) $taxForSlab,
            ];
        }

        $cess = round(0.04 * $totalTax);

        return [
            'total' => (float) ($totalTax + $cess),
            'cess' => (float) $cess,
            'slabs' => $slabBreakdown,
        ];
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Payroll/tds-check-screen.blade.php'));
    }
}


