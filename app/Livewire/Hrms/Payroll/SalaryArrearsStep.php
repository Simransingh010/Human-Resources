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
    public $groupedSalaryArrears = [];
    public $modalEmployeeId = null;
    public $modalEmployeeDetails = [];

    public function mount($payrollSlotId)
    {
        $this->payrollSlotId = $payrollSlotId;
        $this->loadSalaryArrears();
    }

    protected function loadSalaryArrears()
    {
        // Get the current payroll slot details
        $currentPayrollSlot = PayrollSlot::findOrFail($this->payrollSlotId);

        $salaryArrears = SalaryArrear::where('firm_id', Session::get('firm_id'))
            ->where('total_amount', '>', DB::raw('paid_amount')) // Only show if not fully paid
            ->whereHas('disburseWefPayrollSlot', function ($query) use ($currentPayrollSlot) {
                // The disbursement should start in a payroll slot whose 'from_date' is less than or equal to the current slot's 'from_date'
                $query->where('from_date', '<=', $currentPayrollSlot->from_date);
            })
            ->with(['employee', 'disburseWefPayrollSlot', 'salary_component'])
            ->get();

        // Group by employee
        $grouped = [];
        foreach ($salaryArrears as $arrear) {
            $empId = $arrear->employee->id;
            if (!isset($grouped[$empId])) {
                $grouped[$empId] = [
                    'employee' => $arrear->employee,
                    'total_amount' => 0,
                    'paid_amount' => 0,
                    'arrears' => [],
                ];
            }
            $grouped[$empId]['total_amount'] += $arrear->total_amount;
            $grouped[$empId]['paid_amount'] += $arrear->paid_amount;
            $grouped[$empId]['arrears'][] = $arrear;
        }
        $this->groupedSalaryArrears = $grouped;
    }

    public function showEmployeeArrearsModal($employeeId)
    {
        $this->modalEmployeeId = $employeeId;
        $this->modalEmployeeDetails = $this->groupedSalaryArrears[$employeeId] ?? [];
    }

    public function closeEmployeeArrearsModal()
    {
        $this->modalEmployeeId = null;
        $this->modalEmployeeDetails = [];
    }

    public function render()
    {
        return view('livewire.hrms.payroll.salary-arrears-step', [
            'groupedSalaryArrears' => $this->groupedSalaryArrears,
            'modalEmployeeId' => $this->modalEmployeeId,
            'modalEmployeeDetails' => $this->modalEmployeeDetails,
        ]);
    }
} 