<?php

namespace App\Livewire\Saas\PanelMeta;

use Livewire\Component;
use App\Models\Saas\Panel;
use App\Models\Saas\AppModule;
use Flux;

class ModuleSync extends Component
{
    public Panel $panel;
    public array $selectedModules = [];
    public array $listsForFields = [];

    public function mount($panelId)
    {
        $this->panel = Panel::findOrFail($panelId);
        $this->selectedModules = $this->panel->app_modules()->select('app_modules.id')->pluck('id')->toArray();
        $this->initListsForFields();
    }

    public function save()
    {
        $this->panel->app_modules()->sync($this->selectedModules);
        Flux::modal('module-sync')->close();

        Flux::toast(
            variant: 'success',
            heading: 'Changes saved.',
            text: 'Modules updated successfully!',
        );
    }

    protected function initListsForFields(): void
    {
        $this->listsForFields['modulelist'] = AppModule::with('app')
            ->where('is_inactive', false)
            ->get()
            ->groupBy('app.name')
            ->map(function ($modules) {
                return $modules->mapWithKeys(function ($module) {
                    return [
                        $module->id => $module->name . ' (' . $module->code . ')'
                    ];
                });
            })
            ->toArray();
    }

    public function render()
    {
        return view('livewire.saas.panel-meta.module-sync');
    }
} 