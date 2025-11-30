<?php

namespace App\Livewire\Panelr;

use Livewire\Component;
use App\Services\MenuCoordinator;
use Illuminate\Support\Facades\Session;

class Leftmenur extends Component
{
    public $apps = [];
    public $modules = [];
    public $selectedAppId = null;
    public $selectedModuleId = null;
    public $firmLogo = null;
    public $firmWideLogo = null;
    public $firmSquareLogo = null;

    public function mount()
    {
        $this->apps = MenuCoordinator::getApps();
        $this->selectedAppId = MenuCoordinator::getSelectedAppId() ?? $this->apps[0]['id'] ?? null;
        $this->modules = MenuCoordinator::getAppModules($this->selectedAppId);

        // Get the firm's logo through user relationship
        $user = auth()->user();
        if ($user) {
            $firm = $user->firms()->where('firms.id', Session::get('firm_id'))->first();
            if ($firm) {
                $this->firmLogo = $firm->getMedia('squareLogo')->first()?->getUrl();
                $this->firmWideLogo = $firm->getMedia('wideLogo')->first()?->getUrl();
                $this->firmSquareLogo = $firm->getMedia('squareLogo')->first()?->getUrl();
            }
        }

        // Set selected module from current route
        $currentRoute = request()->route()->getName();
        foreach ($this->modules as $module) {
            foreach ($module['wires'] ?? [] as $wire) {
                if (isset($wire['route']) && $wire['route'] === $currentRoute) {
                    $this->selectedModuleId = $module['id'];
                    break 2;
                }
            }
        }
    }

    public function selectApp($appId)
    {
        $this->selectedAppId = $appId;
        $this->modules = MenuCoordinator::getAppModules($appId);

        // Redirect to first component of first module
        if (!empty($this->modules)) {
            $firstModule = $this->modules[0];
            if (!empty($firstModule['wires'])) {
                $firstWire = $firstModule['wires'][0];
                if (isset($firstWire['route'])) {
                    return redirect()->route($firstWire['route']);
                }
            }
        }
    }

    public function selectModule($moduleId)
    {
        $this->selectedModuleId = $moduleId;
        
        // Find first wire in module and redirect
        foreach ($this->modules as $module) {
            if ($module['id'] == $moduleId) {
                if (!empty($module['wires'])) {
                    $firstWire = $module['wires'][0];
                    if (isset($firstWire['route'])) {
                        return redirect()->route($firstWire['route']);
                    }
                }
                break;
            }
        }
    }

    public function render()
    {
        return view('livewire.panelr.leftmenur');
    }
}
