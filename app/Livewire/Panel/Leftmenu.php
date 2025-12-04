<?php

namespace App\Livewire\Panel;

use Livewire\Component;
use Livewire\Attributes\On;
use App\Services\MenuCoordinator;
use Illuminate\Support\Facades\Session;

class Leftmenu extends Component
{
    public $apps = [];
    public $modules = [];
    public $selectedAppId = null;
    public $selectedModuleId = null;
    public $firmLogo = null;
    public $firmWideLogo = null;
    public $firmSquareLogo = null;
    public $firmName = null;
    public $firmShortName = null;

    public function mount()
    {
        $this->apps = MenuCoordinator::getApps();
        
        // Read from session (already set by blade template for route-based navigation)
        $this->selectedAppId = MenuCoordinator::getSelectedAppId();
        $this->selectedModuleId = MenuCoordinator::getSelectedModuleId();
        
        // If no app selected, use first app
        if (!$this->selectedAppId && !empty($this->apps)) {
            $this->selectedAppId = $this->apps[0]['id'];
            session(['selectedAppId' => $this->selectedAppId]);
        }
        
        // Load modules for selected app
        if ($this->selectedAppId) {
            $this->modules = MenuCoordinator::getAppModules($this->selectedAppId);
        }
        
        // If no module selected and we have modules, select first
        // But DON'T dispatch events - just set session
        if (!$this->selectedModuleId && !empty($this->modules)) {
            $this->selectedModuleId = $this->modules[0]['id'];
            session(['selectedModuleId' => $this->selectedModuleId]);
        }

        // Load firm branding
        $this->loadFirmBranding();
    }

    protected function loadFirmBranding(): void
    {
        $user = auth()->user();
        if ($user) {
            $firm = $user->firms()->where('firms.id', Session::get('firm_id'))->first();
            if ($firm) {
                $this->firmLogo = $firm->getMedia('squareLogo')->first()?->getUrl();
                $this->firmWideLogo = $firm->getMedia('wideLogo')->first()?->getUrl();
                $this->firmSquareLogo = $firm->getMedia('squareLogo')->first()?->getUrl();
                $this->firmName = $firm->name;
                $this->firmShortName = $firm->short_name;
            }
        }
    }

    /**
     * Listen for wireSelected to sync sidebar highlighting
     */
    #[On('wireSelected')]
    public function onWireSelected($wire): void
    {
        $moduleId = MenuCoordinator::findModuleIdForWire($wire);
        if ($moduleId && $moduleId !== $this->selectedModuleId) {
            $this->selectedModuleId = $moduleId;
            session(['selectedModuleId' => $moduleId]);
        }
    }

    /**
     * Handle app selection from UI (user click)
     */
    public function selectApp($appId)
    {
        $this->selectedAppId = $appId;
        session(['selectedAppId' => $appId]);
        
        $this->modules = MenuCoordinator::getAppModules($appId);

        if (!empty($this->modules)) {
            $firstModuleId = $this->modules[0]['id'];
            $this->selectedModuleId = $firstModuleId;
            session(['selectedModuleId' => $firstModuleId]);
            $this->dispatch('moduleSelected', $firstModuleId);
        } else {
            MenuCoordinator::resetAll();
            $this->dispatch('wireSelected', session('defaultwire'));
        }
    }

    /**
     * Handle module selection from UI (user click)
     */
    public function selectModule($moduleId)
    {
        if (!$moduleId) {
            return;
        }

        $this->selectedModuleId = $moduleId;
        session(['selectedModuleId' => $moduleId]);
        
        // Dispatch to Topmenu to load wires for this module
        $this->dispatch('moduleSelected', $moduleId);
    }

    public function render()
    {
        return view('livewire.panel.leftmenu');
    }
}
