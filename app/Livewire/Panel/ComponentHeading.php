<?php

namespace App\Livewire\Panel;

use App\Models\Saas\Module;
use App\Models\Saas\Component as ComponentModel;
use Livewire\Component;

class ComponentHeading extends Component
{
    public $component_det;
    public function mount()
    {
        $this->component_det = ComponentModel::where('wire', session('selectedWire'))->first();

    }
    public function render()
    {
        return view('livewire.panel.component-heading');
    }
}
