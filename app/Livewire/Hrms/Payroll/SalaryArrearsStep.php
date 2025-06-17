<?php

namespace App\Livewire\Hrms\Payroll;

use App\Models\Hrms\SalaryArrear;
use App\Models\Hrms\Employee;
use App\Models\Hrms\PayrollSlot;
use App\Models\Hrms\SalaryComponent;
use Livewire\Component;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;

class SalaryArrearsStep extends Component
{
    public $payrollSlotId;
    public $salaryArrears;

    public function mount($payrollSlotId)
    {
        $this->payrollSlotId = $payrollSlotId;
        $this->loadSalaryArrears();
    }

    protected function loadSalaryArrears()
    {
        // Get the current payroll slot details
        $currentPayrollSlot = PayrollSlot::findOrFail($this->payrollSlotId);

        $this->salaryArrears = SalaryArrear::where('firm_id', Session::get('firm_id'))
            ->where('total_amount', '>', DB::raw('paid_amount')) // Only show if not fully paid
            ->whereHas('disburseWefPayrollSlot', function ($query) use ($currentPayrollSlot) {
                // The disbursement should start in a payroll slot whose 'from_date' is less than or equal to the current slot's 'from_date'
                $query->where('from_date', '<=', $currentPayrollSlot->from_date);
            })
            ->with(['employee', 'disburseWefPayrollSlot', 'salary_component'])
            ->get();
    }

    public function render()
    {
        return view('livewire.hrms.payroll.salary-arrears-step');
    }
} 