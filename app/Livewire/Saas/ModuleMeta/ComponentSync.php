<?php

namespace App\Livewire\Saas\ModuleMeta;

use Livewire\Component;

use App\Models\Saas\Component as ComponentModel;
use App\Models\Saas\Module;
use Illuminate\Support\Collection;
use Flux;

class ComponentSync extends Component
{
    public Module $module;
    public array $selectedComponents = [];
    public array $listsForFields = [];

    public function mount($moduleId)
    {
        $this->module = Module::findOrFail($moduleId);
        $this->selectedComponents = $this->module->components()
            ->pluck('components.id')
            ->toArray();
        $this->initListsForFields();
    }

    public function save()
    {
        $this->module->components()->sync($this->selectedComponents);
        Flux::modal('component-sync')->close();

        Flux::toast(
            variant: 'success',
            heading: 'Changes saved.',
            text: 'Components updated successfully!',
        );
    }

    protected function initListsForFields(): void
    {
        $this->listsForFields['componentlist'] = ComponentModel::query()
            ->where('is_inactive', false)
            ->get()
            ->mapWithKeys(function ($component) {
                return [
                    (string)$component->id => [
                        'id' => (string)$component->id,
                        'name' => $component->name,
                        'code' => $component->code,
                        'description' => $component->description
                    ]
                ];
            })
            ->toArray();
    }

    public function render()
    {
        return view()->file(
            app_path('Livewire/Saas/ModuleMeta/blades/component-sync.blade.php')
        );
    }
}
