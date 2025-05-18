<?php

namespace App\Http\Livewire\Payroll;

use Livewire\Component;
use App\Models\Hrms\SalaryComponent;

class CalculationRuleModal extends Component
{
    public $show = false;
    public $rule = [];
    public $components = [];

    protected $listeners = ['openRuleModal' => 'open'];

    public function mount()
    {
        // load your existing salary‐component keys/titles for the “component” selector
        $this->components = SalaryComponent::pluck('title','key')->toArray();
        $this->resetRule();
    }

    public function resetRule()
    {
        $this->rule = [
            'type'    => 'constant',
            'value'   => 0,
            'operator'=> '+',
            'operands'=> [],
            'name'    => 'round',
            'args'    => [],
            'if'      => ['type'=>'constant','value'=>0],
            'then'    => ['type'=>'constant','value'=>0],
            'else'    => ['type'=>'constant','value'=>0],
            'key'     => null,
        ];
    }

    public function open($existing = null)
    {
        if ($existing) {
            $this->rule = $existing;
        } else {
            $this->resetRule();
        }

        $this->show = true;
    }

    public function addOperand()
    {
        $this->rule['operands'][] = ['type'=>'constant','value'=>0];
    }

    public function removeOperand($i)
    {
        array_splice($this->rule['operands'], $i, 1);
    }

    public function addArg()
    {
        $this->rule['args'][] = ['type'=>'constant','value'=>0];
    }

    public function removeArg($i)
    {
        array_splice($this->rule['args'], $i, 1);
    }

    public function save()
    {
        // validate if you like...
        $this->emitUp('ruleSaved', $this->rule);
        $this->show = false;
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Payroll/SalaryComponentMeta/blades/calculation-rule-modal.blade.php'));
    }
}
