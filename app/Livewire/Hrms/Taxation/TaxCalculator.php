<?php

namespace App\Livewire\Hrms\Taxation;

use App\Services\IncomeTaxCalculator;
use Livewire\Component;

class TaxCalculator extends Component
{
    public $form = [
        'pan' => '',
        'name' => '',
        'assessment_year' => IncomeTaxCalculator::AY_2025_26,
        'age_type' => IncomeTaxCalculator::AGE_BELOW_60,
        'advanced' => false,
        'allow_deductions' => false,
        // incomes
        'income_salary' => 0,
        'income_house_property' => 0,
        'income_capital_gains' => 0,
        'income_business' => 0,
        'income_other_sources' => 0,
        // deductions (optional)
        'deductions' => 0,
        // special rates (advanced) or user supplied tax
        'special_tax_user' => 0,
        'stcg_111a' => 0,
        'ltcg_112a' => 0,
        'other_ltcg' => 0,
        'lottery' => 0,
        // payments and interests
        'tds_tcs_mat_amt' => 0,
        'self_assessment_advance_tax' => 0,
        'interest_234a' => 0,
        'interest_234b' => 0,
        'interest_234c' => 0,
        'fee_234f' => 0,
    ];

    public $result = [];

    public function mount()
    {
        $this->recalculate();
    }

    public function updated($name)
    {
        $this->recalculate();
    }

    private function recalculate(): void
    {
        // PAN validation is UI-side; computation proceeds regardless
        $this->result = IncomeTaxCalculator::compute($this->form);
    }

    public function toggleAdvanced()
    {
        $this->form['advanced'] = !$this->form['advanced'];
    }

    public function toggleAllowDeductions()
    {
        $this->form['allow_deductions'] = !$this->form['allow_deductions'];
        $this->recalculate();
    }

    public function getAssessmentYearsProperty()
    {
        return IncomeTaxCalculator::getAssessmentYears();
    }

    public function getAgeTypesProperty()
    {
        return IncomeTaxCalculator::getAgeTypes();
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Taxation/tax-calculator.blade.php'));
    }
}

