<?php

namespace App\Livewire\Panel;

use Livewire\Component;
use App\Services\MenuCoordinator;
use App\Models\Saas\Firm;
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

    public function mount()
    {
        $this->apps = MenuCoordinator::getApps();
        $this->selectedAppId = MenuCoordinator::getSelectedAppId() ?? $this->apps[0]['id'] ?? null;
        $firstModuleId = MenuCoordinator::selectApp($this->selectedAppId);
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

        if ($firstModuleId) {
            $this->dispatch('moduleSelected', $firstModuleId);
        }
    }

    public function selectApp($appId)
    {
        $this->selectedAppId = $appId;
        $this->modules = MenuCoordinator::getAppModules($appId);

        if (!empty($this->modules)) {
            $firstModuleId = $this->modules[0]['id'];
            $this->dispatch('moduleSelected', $firstModuleId); // Load Topmenu
        } else {
            MenuCoordinator::resetAll();
            MenuCoordinator::selectWire(session('defaultwire'));
            $this->dispatch('moduleSelected', null); // ❗ Clear topmenu
            $this->dispatch('wireSelected', session('defaultwire'));
        }
    }

    public function selectWire($wire)
    {
        MenuCoordinator::selectWire($wire);
        $this->selectedWire = $wire;
        $this->dispatch('wireSelected', $wire);
    }

//    public function selectApp($appId)
//    {
//        $this->selectedAppId = $appId;
//        $firstModuleId = MenuCoordinator::selectApp($appId);
//        $this->modules = MenuCoordinator::getAppModules($appId);
//
//        if ($firstModuleId) {
//            $this->dispatch('moduleSelected', $firstModuleId);
//        }
//    }

    public function selectModule($moduleId)
    {
        if (!$moduleId) {
            MenuCoordinator::resetAll();
            $this->dispatch('wireSelected', session('defaultwire'));
            return;
        }

        // Save and update selections via MenuCoordinator
        session(['selectedModuleId' => $moduleId]);
        $wire = MenuCoordinator::selectModule($moduleId);

        // Dispatch to Topmenu and MainContent components
        $this->dispatch('moduleSelected', $moduleId); // Refresh Topmenu
        $this->dispatch('wireSelected', $wire);       // Refresh MainContent
    }


    public function render()
    {
        return view('livewire.panel.leftmenu');
    }
}
