<?php

namespace App\Livewire\Panel;

use Livewire\Component;
use Livewire\Attributes\On;
use App\Services\MenuCoordinator;

class Topmenu extends Component
{
    public $moduleWires = [];
    public $selectedWire = '';
    public $currentModuleId = null;

    public function mount()
    {
        $this->syncFromSession();
    }

    /**
     * Sync state from session
     */
    protected function syncFromSession(): void
    {
        // Read from session (already set by blade template for route-based navigation)
        $this->selectedWire = MenuCoordinator::getSelectedWire() ?? '';
        $this->currentModuleId = MenuCoordinator::getSelectedModuleId();
        
        // Load module wires
        if ($this->currentModuleId) {
            $this->moduleWires = MenuCoordinator::getModuleWires($this->currentModuleId);
        }
    }

    /** 
     * Listen for wireSelected events to update active state
     */
    #[On('wireSelected')]
    public function onWireSelected($wire): void
    {
        $this->selectedWire = $wire;
        
        // Also update module if wire belongs to a different module
        $wireModuleId = MenuCoordinator::findModuleIdForWire($wire);
        if ($wireModuleId && $wireModuleId !== $this->currentModuleId) {
            $this->currentModuleId = $wireModuleId;
            $this->moduleWires = MenuCoordinator::getModuleWires($wireModuleId);
        }
    }

    /**
     * Handle module selection from Leftmenu (user clicked on a module)
     */
    #[On('moduleSelected')]
    public function loadComponents($moduleId)
    {
        if (!$moduleId) {
            $this->moduleWires = [];
            return;
        }
        
        $this->currentModuleId = $moduleId;
        $this->moduleWires = MenuCoordinator::getModuleWires($moduleId);
        
        // Get current wire from session
        $currentWire = MenuCoordinator::getSelectedWire();
        
        // Check if current wire exists in this module's wires
        $wireExistsInModule = collect($this->moduleWires)->contains('wire', $currentWire);
        
        // If current wire is in this module, keep it selected
        if ($wireExistsInModule && !empty($currentWire)) {
            $this->selectedWire = $currentWire;
            return;
        }
        
        // User clicked on a different module - select first wire
        if (!empty($this->moduleWires)) {
            $firstWire = $this->moduleWires[0]['wire'] ?? null;
            if ($firstWire) {
                $this->selectedWire = $firstWire;
                MenuCoordinator::selectWire($firstWire);
                $this->dispatch('wireSelected', $firstWire);
            }
        }
    }

    /**
     * Handle wire click from UI
     */
    public function selectWire($wire)
    {
        // Update local state immediately
        $this->selectedWire = $wire;
        MenuCoordinator::selectWire($wire);
        
        // Check if route-based - redirect
        if (MenuCoordinator::isRouteBased($wire)) {
            $url = MenuCoordinator::getRouteUrl($wire);
            if ($url) {
                return $this->redirect($url, navigate: true);
            }
        }
        
        // Wire-based - dispatch event
        $this->dispatch('wireSelected', $wire);
    }

    public function render()
    {
        return view('livewire.panel.topmenu');
    }
}
