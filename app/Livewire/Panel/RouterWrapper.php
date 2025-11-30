<?php

namespace App\Livewire\Panel;

use Livewire\Component;
use App\Services\MenuCoordinator;

class RouterWrapper extends Component
{
    public ?string $routeComponent = null;
    public ?string $selectedWire = null;
    
    protected $listeners = ['wireSelected' => 'updateWire'];

    public function mount(?string $component = null)
    {
        \Log::info('RouterWrapper mount', [
            'component_param' => $component,
            'session_selectedWire' => session('selectedWire'),
            'MenuCoordinator_getSelectedWire' => MenuCoordinator::getSelectedWire(),
        ]);
        
        $this->routeComponent = $component;
        $this->selectedWire = MenuCoordinator::getSelectedWire();
        
        // If no wire is selected and no route component, use default
        if (!$this->selectedWire && !$this->routeComponent) {
            $this->selectedWire = session('defaultwire', 'panel.dashboard');
        }
        
        \Log::info('RouterWrapper mount final', [
            'routeComponent' => $this->routeComponent,
            'selectedWire' => $this->selectedWire,
        ]);
        
        // Sync menu context from query params if provided
        if ($component) {
            $this->syncMenuContext();
        }
    }
    
    protected function syncMenuContext(): void
    {
        $moduleId = request()->query('module');
        $appId = request()->query('app');
        
        // Update session state for sidebar highlighting
        if ($appId) {
            session(['selectedAppId' => (int) $appId]);
        }
        
        // If moduleId not in query params, try to find it from the wire
        if (!$moduleId && $this->routeComponent) {
            $moduleId = MenuCoordinator::findModuleIdForWire($this->routeComponent);
        }
        
        if ($moduleId) {
            $moduleId = (int) $moduleId;
            session(['selectedModuleId' => $moduleId]);
            // Dispatch moduleSelected to sync Topmenu (Requirement 8.1)
            $this->dispatch('moduleSelected', $moduleId);
        }
        
        // Update selectedWire in session to match route component
        if ($this->routeComponent) {
            MenuCoordinator::selectWire($this->routeComponent);
        }
    }

    public function updateWire(string $wire): void
    {
        \Log::info('RouterWrapper updateWire called', [
            'wire' => $wire,
            'currentRoute' => request()->route()->getName(),
            'routeComponent' => $this->routeComponent,
        ]);
        
        // If we're on a route-based screen, don't redirect
        // The route should stay as-is
        if ($this->routeComponent) {
            \Log::info('RouterWrapper staying on route-based URL', [
                'routeComponent' => $this->routeComponent,
            ]);
            return;
        }
        
        // Otherwise redirect to /panel for wire-based navigation
        \Log::info('RouterWrapper redirecting to panel', ['wire' => $wire]);
        $this->redirect(route('panel'), navigate: true);
    }
    
    protected function getComponentKey(): string
    {
        $component = $this->routeComponent ?? $this->selectedWire ?? 'default';
        return ($this->routeComponent ? 'route-' : 'wire-') . md5($component);
    }

    public function render()
    {
        $component = $this->routeComponent ?? $this->selectedWire;
        $isValid = $component ? MenuCoordinator::wireExists($component) : false;
        
        return view('livewire.panel.router-wrapper', [
            'componentKey' => $this->getComponentKey(),
            'componentToRender' => $isValid ? $component : null,
            'errorMessage' => !$isValid && $component ? "Route not found: {$component}" : null,
        ]);
    }
}
