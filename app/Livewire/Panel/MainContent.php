<?php

namespace App\Livewire\Panel;

use Livewire\Component;
use App\Services\MenuCoordinator;

class MainContent extends Component
{
    public $selectedWire;

    protected $listeners = ['wireSelected' => 'updateWire'];

    public function mount()
    {
        $this->selectedWire = MenuCoordinator::getSelectedWire();
    }

    public function updateWire($wire)
    {
        $this->selectedWire = $wire;
    }

    public function render()
    {
        return view('livewire.panel.main-content');
    }
}
