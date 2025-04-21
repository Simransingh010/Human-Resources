<?php

namespace App\Livewire\Panel;

use Livewire\Component;
use App\Services\MenuCoordinator;

class Topmenu extends Component
{
    public $moduleWires = [];
    public $selectedWire = '';

    protected $listeners = ['moduleSelected' => 'loadComponents'];

    public function mount()
    {
        $moduleId = MenuCoordinator::getSelectedModuleId() ?? '';
        $this->loadComponents($moduleId);
    }

    public function loadComponents($moduleId)
    {
        $this->moduleWires = MenuCoordinator::getModuleWires($moduleId);
        $wire = MenuCoordinator::selectModule($moduleId);
        $this->selectedWire = $wire;
        $this->dispatch('wireSelected', $wire);
    }

    public function selectWire($wire)
    {
        MenuCoordinator::selectWire($wire);
        $this->selectedWire = $wire;
        $this->dispatch('wireSelected', $wire);
    }

    public function render()
    {
        return view('livewire.panel.topmenu');
    }
}
