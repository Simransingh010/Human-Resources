<?php

namespace App\Livewire\Panel;

use Livewire\Component;
use Livewire\Attributes\On;
use App\Services\MenuCoordinator;

class RouterWrapper extends Component
{
    public ?string $routeComponent = null;
    public ?string $selectedWire = null;

    public function mount(?string $component = null)
    {
        $this->routeComponent = $component;
        
        // Session is already set by the blade template, just read it
        $this->selectedWire = $component ?? MenuCoordinator::getSelectedWire();
        
        // Fallback to default wire
        if (!$this->selectedWire) {
            $this->selectedWire = session('defaultwire', 'panel.dashboard');
        }
    }

    /**
     * Handle wire selection from Topmenu or Leftmenu
     */
    #[On('wireSelected')]
    public function updateWire(string $wire): void
    {
        // Ignore if same wire (prevent loops)
        if ($this->selectedWire === $wire) {
            $this->dispatch('navigation-ended');
            return;
        }
        
        // If on a route-based page and receiving the same route component, ignore
        if ($this->routeComponent && $this->routeComponent === $wire) {
            $this->dispatch('navigation-ended');
            return;
        }
        
        // Check if new wire is route-based
        if (MenuCoordinator::isRouteBased($wire)) {
            $url = MenuCoordinator::getRouteUrl($wire);
            if ($url) {
                $this->redirect($url, navigate: true);
                return;
            }
        }
        
        // If currently on a route-based page and going to wire-based, redirect to /panel
        if ($this->routeComponent) {
            MenuCoordinator::selectWire($wire);
            $this->redirect(route('panel'), navigate: true);
            return;
        }
        
        // Wire-based navigation on /panel - update in place
        $this->selectedWire = $wire;
        $this->dispatch('navigation-ended');
    }
    
    protected function getComponentKey(): string
    {
        $component = $this->routeComponent ?? $this->selectedWire ?? 'default';
        return 'component-' . md5($component);
    }

    public function render()
    {
        $component = $this->routeComponent ?? $this->selectedWire;
        $isValid = false;
        $errorMessage = null;
        
        if ($component) {
            try {
                $isValid = MenuCoordinator::wireExists($component);
                if (!$isValid) {
                    $errorMessage = "Component not found: {$component}";
                }
            } catch (\Exception $e) {
                $errorMessage = "Error loading component: {$e->getMessage()}";
            }
        }
        
        return view('livewire.panel.router-wrapper', [
            'componentKey' => $this->getComponentKey(),
            'componentToRender' => $isValid ? $component : null,
            'errorMessage' => $errorMessage,
        ]);
    }
}
