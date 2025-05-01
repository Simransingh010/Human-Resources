<?php

namespace App\Livewire\Saas\AppsMeta;

use Livewire\Component;
use App\Models\Saas\App;
use App\Models\Saas\AppModule;
use App\Models\Saas\Module;
use Livewire\WithPagination;
use Flux;

class AppsModuleSync extends Component
{
    public App $app;
    public array $selectedModules = [];
    public array $listsForFields = [];

    public function mount($appId)
    {
        $this->app = App::findOrFail($appId);
        $this->selectedModules = $this->app->modules()->pluck('modules.id')->toArray();
        $this->initListsForFields();
    }

    public function save()
    {
        $this->app->modules()->sync($this->selectedModules);

        Flux::modal('module-sync')->close();

        Flux::toast(
            variant: 'success',
            heading: 'Changes saved.',
            text: 'Modules updated successfully!',
        );
    }

    protected function initListsForFields(): void
    {
        // Get all modules that either belong to this app or don't belong to any app
        $this->listsForFields['modulelist'] = Module::where('is_inactive', false)
            ->get()
            ->mapWithKeys(function ($module) {
                return [
                    $module->id => $module->name . ' (' . $module->code . ')'
                ];
            })
            ->toArray();
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Saas/AppsMeta/blades/apps-module-sync.blade.php'));
    }
} 