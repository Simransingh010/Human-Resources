<?php

namespace App\Livewire\Hrms\Payroll;

use App\Models\Hrms\Employee;
use App\Models\Hrms\EmployeesSalaryExecutionGroup;
use App\Models\Hrms\PayrollComponentsEmployeesTrack;
use App\Models\Hrms\PayrollSlot;
use App\Models\Hrms\SalaryComponent;
use App\Models\Hrms\SalaryComponentsEmployee;
use App\Models\Hrms\EmployeeTaxRegime;
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

        // Use fixed TDS component ID as requested
        $tdsComponentId = 58;
        if (!$tdsComponentId) {
            return [
                'annual_applicable' => 0,
                'ytd_deducted' => 0,
                'remaining' => 0,
                'per_month_plan' => 0,
                'months' => [],
            ];
        }

        // Current month taxable earnings: pick latest slot in FY that has ANY track for this employee
        $latestSlot = PayrollSlot::query()
            ->where('payroll_slots.firm_id', $firmId)
            ->whereBetween('payroll_slots.from_date', [$fyStart, $fyEnd])
            ->join('payroll_components_employees_tracks as pcet', function ($join) use ($employeeId, $firmId) {
                $join->on('pcet.payroll_slot_id', '=', 'payroll_slots.id')
                    ->where('pcet.employee_id', '=', $employeeId)
                    ->where('pcet.firm_id', '=', $firmId);
            })
            ->select('payroll_slots.*')
            ->orderBy('payroll_slots.from_date', 'desc')
            ->first();

        $currentMonthlyEarnings = 0;
        if ($latestSlot) {
            $currentMonthlyEarnings = PayrollComponentsEmployeesTrack::where('firm_id', $firmId)
                ->where('employee_id', $employeeId)
                ->where('payroll_slot_id', $latestSlot->id)
                ->where('nature', 'earning')
                ->where('taxable', true)
                ->sum('amount_payable');
        }

        $annualIncome = $this->getProjectedAnnualIncome($employeeId, $currentMonthlyEarnings, $latestSlot?->to_date ?? $fyStart);

        // Use proper tax calculation with EmployeeTaxRegime and TaxBracket
        $annualApplicable = $this->calculateProperTax($employeeId, $annualIncome);

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

        $processedSlots = $slots->where('payroll_slot_status', 'CM');
        $remainingSlots = $slots->reject(fn($s) => $s->payroll_slot_status === 'CM' && $s->to_date <= Carbon::now());
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
            'annual_salary' => $annualIncome,
            'annual_applicable' => $annualApplicable,
            'ytd_deducted' => (float) $ytdDeducted,
            'remaining' => (float) $remaining,
            'per_month_plan' => (float) $perMonthPlan,
            'months' => $months,
            'per_month_plan' => (float) $perMonthPlan,
            'ytd_deducted' => (float) $ytdDeducted,
            'remaining' => (float) $remaining,
        ];
    }

    protected function roundOffTax($amount)
    {
        return round($amount / 10) * 10;
    }

    protected function getActualYTDEarnings($employeeId, $fyStart, $currentSlotToDate)
    {
        return PayrollComponentsEmployeesTrack::where('employee_id', $employeeId)
            ->where('firm_id', (int) session('firm_id'))
            ->where('nature', 'earning')
            ->where('taxable', true)
            ->whereBetween('salary_period_from', [$fyStart, $currentSlotToDate])
            ->sum('amount_payable');

    }




    protected function getRemainingMonthsInFY($currentSlotToDate, $fyEnd)
    {
        $current = Carbon::parse($currentSlotToDate);
        $fyEnd = Carbon::parse($fyEnd);
        if ($current->gt($fyEnd)) return 0;
        return $current->diffInMonths($fyEnd);
    }

    protected function getProjectedAnnualIncome($employeeId, $currentMonthlyEarnings, $currentSlotToDate)
    {
        $fyStart = session('fy_start');
        $fyEnd = session('fy_end');
        $actualYTDEarnings = $this->getActualYTDEarnings($employeeId, $fyStart, $currentSlotToDate);

        $remainingMonths = (int) round($this->getRemainingMonthsInFY($currentSlotToDate, $fyEnd));
        $projectedRemainingEarnings = $currentMonthlyEarnings * $remainingMonths;
        

        return $actualYTDEarnings + $projectedRemainingEarnings - 75000;
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

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Payroll/tds-check-screen.blade.php'));
    }
}


