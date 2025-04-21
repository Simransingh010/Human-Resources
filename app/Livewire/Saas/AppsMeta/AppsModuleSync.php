<?php

namespace App\Livewire\Saas\AppsMeta;

use Livewire\Component;
use App\Models\Saas\App;
use App\Models\Saas\AppModule;
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
        $this->selectedModules = $this->app->app_modules()->select('app_modules.id')->pluck('id')->toArray();
        $this->initListsForFields();
    }

    public function save()
    {
        // Update existing modules to unset their app_id if they're not selected
        $this->app->app_modules()->sync($this->selectedModules);;

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
        $this->listsForFields['modulelist'] = AppModule::where('is_inactive', false)
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
        return view('livewire.saas.apps-meta.apps-module-sync');
    }
} 