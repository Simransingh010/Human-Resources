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
        
        // Sync menu context from query params if provided (only for route-based)
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
        }
        
        // Update selectedWire in session to match route component
        // But DON'T dispatch moduleSelected - that causes the loop!
        if ($this->routeComponent) {
            MenuCoordinator::selectWire($this->routeComponent);
        }
    }

    /**
     * Handle wire selection from Topmenu or other components
     * 
     * Edge cases handled:
     * 1. Wire-based on /panel → Wire-based: Update selectedWire, re-render component
     * 2. Wire-based on /panel → Route-based: Redirect to route URL
     * 3. Route-based → Same route: Do nothing (prevent loop)
     * 4. Route-based → Different route: Redirect to new route URL
     * 5. Route-based → Wire-based: Redirect to /panel
     */
    public function updateWire(string $wire): void
    {
        \Log::info('RouterWrapper updateWire called', [
            'wire' => $wire,
            'routeComponent' => $this->routeComponent,
            'selectedWire' => $this->selectedWire,
        ]);
        
        // Case 3: Already on this route-based screen - do nothing (prevent loop)
        if ($this->routeComponent && $this->routeComponent === $wire) {
            \Log::info('RouterWrapper: Already on this route, skipping', ['wire' => $wire]);
            return;
        }
        
        // Check if the NEW wire is route-based
        $isNewWireRouteBased = MenuCoordinator::isRouteBased($wire);
        
        // Case 2 & 4: New wire is route-based - redirect to its URL
        if ($isNewWireRouteBased) {
            $newRouteUrl = MenuCoordinator::getRouteUrl($wire);
            if ($newRouteUrl) {
                \Log::info('RouterWrapper: Navigating to route-based URL', [
                    'wire' => $wire,
                    'url' => $newRouteUrl,
                ]);
                $this->redirect($newRouteUrl, navigate: true);
                return;
            }
        }
        
        // Case 5: On route-based screen, new wire is wire-based - go to /panel
        if ($this->routeComponent) {
            \Log::info('RouterWrapper: Leaving route for wire-based navigation', [
                'from' => $this->routeComponent,
                'to' => $wire,
            ]);
            $this->redirect(route('panel'), navigate: true);
            return;
        }
        
        // Case 1: On /panel with wire-based navigation - update component in-place
        \Log::info('RouterWrapper: Updating wire in-place on /panel', ['wire' => $wire]);
        $this->selectedWire = $wire;
        
        // Dispatch navigation-ended to hide loading overlay
        $this->dispatch('navigation-ended');
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
