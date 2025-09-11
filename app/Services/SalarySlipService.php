<?php

namespace App\Services;

use App\Models\Hrms\PayrollComponentsEmployeesTrack;
use App\Models\Hrms\Employee;
use App\Models\Hrms\PayrollSlot;
use App\Models\Saas\Firm;
use Illuminate\Support\Facades\Storage;

class SalarySlipService
{
    public function getSalarySlipData($employeeId, $payrollSlotId, $firmId)
    {
        // Load employee with job profile and personal details
        $employee = Employee::with('emp_personal_detail', 'emp_job_profile.department', 'emp_job_profile.designation', 'bank_account')
            ->where('firm_id', $firmId)
            ->findOrFail($employeeId);

        // Get firm logos (base64 for PDF)
        $firm = Firm::find($firmId);
        $firmSquareLogoData = null;
        $firmWideLogoData = null;
        if ($firm) {
            $squareMedia = $firm->getFirstMedia('squareLogo');
            if ($squareMedia) {
                $firmSquareLogoData = 'data:' . $squareMedia->mime_type . ';base64,' . base64_encode(file_get_contents($squareMedia->getPath()));
            }
            $wideMedia = $firm->getFirstMedia('wideLogo');
            if ($wideMedia) {
                $firmWideLogoData = 'data:' . $wideMedia->mime_type . ';base64,' . base64_encode(file_get_contents($wideMedia->getPath()));
            }
        }

        // Get salary components for the payroll slot
        $rawComponents = PayrollComponentsEmployeesTrack::where('employee_id', $employeeId)
            ->where('firm_id', $firmId)
            ->where('payroll_slot_id', $payrollSlotId)
            ->with(['salary_component.salary_component_group:id,title'])
            ->get();

        $grouped = [];
        $ungrouped = [];
        foreach ($rawComponents as $component) {
            $groupId = $component->salary_component->salary_component_group_id ?? null;
            $nature = $component->nature;
            if ($groupId) {
                $groupTitle = $component->salary_component->salary_component_group?->title ?? 'Other';
                $grouped[$nature][$groupId]['title'] = $groupTitle;
                $grouped[$nature][$groupId]['amount'] = ($grouped[$nature][$groupId]['amount'] ?? 0) + $component->amount_payable;
            } else {
                $ungrouped[$nature][] = [
                    'title' => $component->salary_component->title,
                    'amount' => $component->amount_payable,
                    'nature' => $nature,
                    'component_type' => $component->component_type,
                    'amount_type' => $component->amount_type
                ];
            }
        }

        $salaryComponents = [];
        $totalEarnings = 0;
        $totalDeductions = 0;
        // Add grouped earnings
        if (!empty($grouped['earning'])) {
            foreach ($grouped['earning'] as $group) {
                $salaryComponents[] = [
                    'title' => $group['title'],
                    'amount' => $group['amount'],
                    'nature' => 'earning',
                ];
                $totalEarnings += $group['amount'];
            }
        }
        // Add ungrouped earnings
        if (!empty($ungrouped['earning'])) {
            foreach ($ungrouped['earning'] as $comp) {
                $salaryComponents[] = $comp;
                $totalEarnings += $comp['amount'];
            }
        }
        // Add grouped deductions
        if (!empty($grouped['deduction'])) {
            foreach ($grouped['deduction'] as $group) {
                $salaryComponents[] = [
                    'title' => $group['title'],
                    'amount' => $group['amount'],
                    'nature' => 'deduction',
                ];
                $totalDeductions += $group['amount'];
            }
        }
        // Add ungrouped deductions
        if (!empty($ungrouped['deduction'])) {
            foreach ($ungrouped['deduction'] as $comp) {
                $salaryComponents[] = $comp;
                $totalDeductions += $comp['amount'];
            }
        }

        // Sort by nature (earnings first), then title
        $salaryComponents = collect($salaryComponents)->sortBy([
            ['nature', 'desc'],
            ['title', 'asc']
        ])->values()->all();

        $netSalary = $totalEarnings - $totalDeductions;
        $netSalaryInWords = $this->numberToWords($netSalary);

        return [
            'selectedEmployee' => $employee,
            'salaryComponents' => $salaryComponents,
            'totalEarnings' => $totalEarnings,
            'totalDeductions' => $totalDeductions,
            'netSalary' => $netSalary,
            'rawComponents' => $rawComponents,
            'firmSquareLogo' => $firmSquareLogoData,
            'firmWideLogo' => $firmWideLogoData,
            'netSalaryInWords' => $netSalaryInWords,
        ];
    }

    protected function numberToWords($number)
    {
        $f = new \NumberFormatter("en", \NumberFormatter::SPELLOUT);
        return ucfirst($f->format($number)) . ' Rupees Only';
    }
} 