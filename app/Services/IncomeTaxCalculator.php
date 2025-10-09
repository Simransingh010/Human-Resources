<?php

namespace App\Services;

class IncomeTaxCalculator
{
    public const AY_2024_25 = '2024-25';
    public const AY_2025_26 = '2025-26';

    public const AGE_BELOW_60 = 'below_60';
    public const AGE_60_TO_79 = '60_79';
    public const AGE_80_PLUS = '80_plus';

    public static function getAssessmentYears(): array
    {
        return [self::AY_2024_25, self::AY_2025_26];
    }

    public static function getAgeTypes(): array
    {
        return [
            self::AGE_BELOW_60 => 'Below 60',
            self::AGE_60_TO_79 => '60-79 (Senior Citizen)',
            self::AGE_80_PLUS => '80 and above (Super Senior Citizen)',
        ];
    }

    public static function getStandardDeduction(string $ay, float $salaryPlusPension): float
    {
        $cap = $ay === self::AY_2025_26 ? 75000.0 : 50000.0;
        return max(0.0, min($cap, $salaryPlusPension));
    }

    public static function compute(array $input): array
    {
        $ay = $input['assessment_year'] ?? self::AY_2025_26;
        $ageType = $input['age_type'] ?? self::AGE_BELOW_60;

        $salary = (float) ($input['income_salary'] ?? 0);
        $house = (float) ($input['income_house_property'] ?? 0);
        $capitalGains = (float) ($input['income_capital_gains'] ?? 0);
        $business = (float) ($input['income_business'] ?? 0);
        $other = (float) ($input['income_other_sources'] ?? 0);

        $grossTotalIncome = $salary + $house + $capitalGains + $business + $other;

        // Only standard deduction in new regime (simplified)
        $standardDeduction = self::getStandardDeduction($ay, $salary);
        $chapterViaDeductions = (float) ($input['deductions'] ?? 0); // optional advanced; default 0
        $allowDeductions = (bool) ($input['allow_deductions'] ?? false);
        $totalDeductions = $standardDeduction + ($allowDeductions ? $chapterViaDeductions : 0.0);

        $taxableIncome = max(0.0, $grossTotalIncome - $totalDeductions);

        // Split regular income vs. special rated portion if advanced mode is used
        $advanced = (bool) ($input['advanced'] ?? false);
        $specialTaxUser = (float) ($input['special_tax_user'] ?? 0);

        $specialComponents = [
            'stcg_111a' => (float) ($input['stcg_111a'] ?? 0),
            'ltcg_112a' => (float) ($input['ltcg_112a'] ?? 0),
            'other_ltcg' => (float) ($input['other_ltcg'] ?? 0),
            'lottery' => (float) ($input['lottery'] ?? 0),
        ];

        // For simplicity, treat all capital gains in income_capital_gains as regular unless advanced provided
        $regularIncome = $taxableIncome;
        $taxAtSpecialRates = 0.0;
        if ($advanced) {
            // Remove advanced special incomes from regular base (they will be taxed separately)
            $specialBase = $specialComponents['stcg_111a'] + $specialComponents['ltcg_112a'] + $specialComponents['other_ltcg'] + $specialComponents['lottery'];
            $regularIncome = max(0.0, $taxableIncome - $specialBase);
            $taxAtSpecialRates += self::taxOnSpecial($specialComponents);
        } else {
            // user-supplied special tax amount
            $taxAtSpecialRates += max(0.0, $specialTaxUser);
        }

        $taxAtNormalRates = self::taxNewRegime($ay, $regularIncome);
        $taxBeforeRebate = $taxAtNormalRates + $taxAtSpecialRates;

        // Rebate u/s 87A (assume resident individual and new regime). Apply on regular slab tax; conservative approach: do not rebate special-tax portion.
        $rebateThreshold = self::rebateThreshold($ay);
        $rebate = 0.0;
        if ($regularIncome <= $rebateThreshold) {
            $rebate = min($taxAtNormalRates, $taxAtNormalRates); // full rebate of slab tax
        } else {
            // Marginal relief around rebate threshold (only on slab portion)
            $excess = $regularIncome - $rebateThreshold;
            if ($taxAtNormalRates > $excess) {
                $rebate = $taxAtNormalRates - $excess; // reduce tax to excess
            }
        }

        $netTaxAfterRebate = max(0.0, $taxBeforeRebate - $rebate);

        // Surcharge (basic implementation on total tax before cess). Note: surcharge caps for special incomes are complex; omitted here for clarity.
        $surcharge = self::surchargeOnTax($netTaxAfterRebate, $regularIncome + ($advanced ? array_sum($specialComponents) : 0.0));

        $cess = 0.04 * ($netTaxAfterRebate + $surcharge);

        $totalTaxOnIncome = $netTaxAfterRebate + $surcharge + $cess;

        $tdsTcsMatAmt = (float) ($input['tds_tcs_mat_amt'] ?? 0);
        $selfAssessmentAdvanceTax = (float) ($input['self_assessment_advance_tax'] ?? 0);

        $interest234A = (float) ($input['interest_234a'] ?? 0);
        $interest234B = (float) ($input['interest_234b'] ?? 0);
        $interest234C = (float) ($input['interest_234c'] ?? 0);
        $fee234F = (float) ($input['fee_234f'] ?? 0);

        $taxAndInterestPayable = max(0.0, $totalTaxOnIncome - ($tdsTcsMatAmt + $selfAssessmentAdvanceTax))
            + $interest234A + $interest234B + $interest234C + $fee234F;

        return [
            'gross_total_income' => self::round2($grossTotalIncome),
            'standard_deduction' => self::round2($standardDeduction),
            'deductions' => self::round2($allowDeductions ? $chapterViaDeductions : 0.0),
            'taxable_income' => self::round2($taxableIncome),
            'tax_at_normal_rates' => self::round2($taxAtNormalRates),
            'tax_at_special_rates' => self::round2($taxAtSpecialRates),
            'tax_before_rebate' => self::round2($taxBeforeRebate),
            'rebate_87a' => self::round2($rebate),
            'net_tax_after_rebate' => self::round2($netTaxAfterRebate),
            'surcharge' => self::round2($surcharge),
            'cess' => self::round2($cess),
            'total_tax_on_income' => self::round2($totalTaxOnIncome),
            'balance_tax_payable' => self::round2(max(0.0, $totalTaxOnIncome - ($tdsTcsMatAmt + $selfAssessmentAdvanceTax))),
            'interest_234a' => self::round2($interest234A),
            'interest_234b' => self::round2($interest234B),
            'interest_234c' => self::round2($interest234C),
            'fee_234f' => self::round2($fee234F),
            'total_tax_and_interest_payable' => self::round2($taxAndInterestPayable),
        ];
    }

    private static function rebateThreshold(string $ay): float
    {
        if ($ay === self::AY_2025_26) {
            return 1200000.0; // AY 2025-26 threshold
        }
        return 700000.0; // AY 2024-25 threshold
    }

    private static function taxNewRegime(string $ay, float $regularIncome): float
    {
        // AY-specific slabs
        if ($ay === self::AY_2025_26) {
            $slabs = [
                [0, 400000, 0.00],
                [400000, 800000, 0.05],
                [800000, 1200000, 0.10],
                [1200000, 1600000, 0.15],
                [1600000, 2000000, 0.20],
                [2000000, PHP_INT_MAX, 0.30],
            ];
        } else {
            // AY 2024-25
            $slabs = [
                [0, 300000, 0.00],
                [300000, 600000, 0.05],
                [600000, 900000, 0.10],
                [900000, 1200000, 0.15],
                [1200000, 1500000, 0.20],
                [1500000, PHP_INT_MAX, 0.30],
            ];
        }
        $tax = 0.0;
        $remaining = $regularIncome;
        $prevUpper = 0.0;
        foreach ($slabs as $slab) {
            [$lower, $upper, $rate] = $slab;
            $bandLower = (float) $lower;
            $bandUpper = (float) $upper;
            $bandRate = (float) $rate;
            if ($regularIncome <= $bandLower) {
                break;
            }
            $taxableBand = min($regularIncome, $bandUpper) - $bandLower;
            if ($taxableBand > 0) {
                $tax += $taxableBand * $bandRate;
            }
            if ($regularIncome <= $bandUpper) {
                break;
            }
            $prevUpper = $bandUpper;
        }
        return $tax;
    }

    private static function taxOnSpecial(array $special): float
    {
        // Simplified: STCG 111A @15%, LTCG 112A @10% over exemption not modeled, Other LTCG @20%, Lottery @30%.
        // Note: Real calculators consider exemptions/indexation etc. This is simplified per brief.
        $tax = 0.0;
        $tax += 0.15 * max(0.0, (float) ($special['stcg_111a'] ?? 0));
        $tax += 0.10 * max(0.0, (float) ($special['ltcg_112a'] ?? 0));
        $tax += 0.20 * max(0.0, (float) ($special['other_ltcg'] ?? 0));
        $tax += 0.30 * max(0.0, (float) ($special['lottery'] ?? 0));
        return $tax;
    }

    private static function surchargeOnTax(float $tax, float $totalIncome): float
    {
        if ($totalIncome <= 5000000) return 0.0; // up to 50L
        if ($totalIncome <= 10000000) return 0.10 * $tax; // 10%
        if ($totalIncome <= 20000000) return 0.15 * $tax; // 15%
        if ($totalIncome <= 50000000) return 0.25 * $tax; // 25%
        return 0.37 * $tax; // 37% for ultra-high income (note: new regime debate; using ITD table)
    }

    private static function round2(float $v): float
    {
        return round($v, 2);
    }
}


