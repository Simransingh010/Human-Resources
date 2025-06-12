<?php

namespace App\Livewire\Hrms\Payroll;

use App\Models\Hrms\SalaryAdvance;
use App\Models\Hrms\Employee;
use App\Models\Hrms\PayrollSlot;
use Livewire\Component;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;

class SalaryAdvancesStep extends Component
{
    public $payrollSlotId;
    public $salaryAdvances;

    public function mount($payrollSlotId)
    {
        $this->payrollSlotId = $payrollSlotId;
        $this->loadSalaryAdvances();
    }

    protected function loadSalaryAdvances()
    {
        // Get the current payroll slot details
        $currentPayrollSlot = PayrollSlot::findOrFail($this->payrollSlotId);

        $this->salaryAdvances = SalaryAdvance::where('firm_id', Session::get('firm_id'))
            ->where('amount', '>', DB::raw('recovered_amount')) // Only show if not fully recovered
            ->whereHas('recoveryWefPayrollSlot', function ($query) use ($currentPayrollSlot) {
                // The recovery should start in a payroll slot whose 'from_date' is less than or equal to the current slot's 'from_date'
                $query->where('from_date', '<=', $currentPayrollSlot->from_date);
            })
            ->with(['employee', 'disbursePayrollSlot', 'recoveryWefPayrollSlot'])
            ->get();
    }

    public function render()
    {
        return view('livewire.hrms.payroll.salary-advances-step');
    }
} 