<?php

namespace App\Livewire\Hrms\Payroll;

use App\Models\Hrms\SalaryComponentsEmployee;
use App\Models\Hrms\PayrollComponentsEmployeesTrack;
use App\Models\Hrms\PayrollStepPayrollSlotCmd;
use Livewire\Component;
use Illuminate\Support\Facades\Session;
use Flux;

class OverRideUnkonwnComponent extends Component
{
    public $payrollSlotId;
    public $staticUnknownComponents = [];
    public $componentAmounts = [];

    public function mount($payrollSlotId)
    {
        $this->payrollSlotId = $payrollSlotId;
        $this->loadStaticUnknownComponents();
    }

    public function loadStaticUnknownComponents()
    {
        // Fetch all static unknown components
        $this->staticUnknownComponents = SalaryComponentsEmployee::where('firm_id', Session::get('firm_id'))
            ->where('amount_type', 'static_unknown')
            ->with(['salary_component', 'employee'])
            ->get();

        // Initialize component amounts array
        foreach ($this->staticUnknownComponents as $component) {
            $this->componentAmounts[$component->id] = 0;
        }
    }

    public function saveComponentAmounts()
    {
        try {
            foreach ($this->componentAmounts as $componentId => $amount) {
                if ($amount > 0) {
                    $component = SalaryComponentsEmployee::find($componentId);
                    
                    // Create entry in PayrollComponentsEmployeesTrack
                    PayrollComponentsEmployeesTrack::create([
                        'firm_id' => Session::get('firm_id'),
                        'payroll_slot_id' => $this->payrollSlotId,
                        'payroll_slots_cmd_id' => 2, // You might need to adjust this based on your logic
                        'employee_id' => $component->employee_id,
                        'salary_template_id' => $component->salary_template_id,
                        'salary_component_group_id' => $component->salary_component_group_id,
                        'salary_component_id' => $component->salary_component_id,
                        'sequence' => $component->sequence,
                        'nature' => $component->nature,
                        'component_type' => $component->component_type,
                        'amount_type' => $component->amount_type,
                        'taxable' => $component->taxable,
                        'calculation_json' => $component->calculation_json,
                        'salary_period_from' => now(), // You should get this from payroll slot
                        'salary_period_to' => now(), // You should get this from payroll slot
                        'user_id' => auth()->id(),
                        'amount_full' => $amount,
                        'amount_payable' => $amount,
                        'amount_paid' => 0,
                        'salary_cycle_id' => null // You should get this from payroll slot
                    ]);
                }
            }

            // Create log entry in PayrollStepPayrollSlotCmd
            PayrollStepPayrollSlotCmd::create([
                'firm_id' => Session::get('firm_id'),
                'payroll_step_payroll_slot_id' => $this->payrollSlotId,
                'payroll_step_status' => 'completed',
                'step_remarks' => 'Static unknown components updated successfully',
                'user_id' => auth()->id()
            ]);

            // Emit event to close modal
            $this->dispatch('closeModal');

            Flux::toast(
                variant: 'success',
                heading: 'Success',
                text: 'Component amounts saved successfully',
            );

        } catch (\Exception $e) {
            Flux::toast(
                variant: 'error',
                heading: 'Error',
                text: 'Failed to save component amounts: ' . $e->getMessage(),
            );
        }
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Payroll/blades/over-ride-unkonwn-component.blade.php'));
    }
} 