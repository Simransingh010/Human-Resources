<?php

namespace App\Livewire\Saas\PanelMeta;

use Livewire\Component;
use App\Models\Saas\Panel;
use App\Models\Saas\Component as ComponentModel;
use Flux;

class ComponentSync extends Component
{
    public Panel $panel;
    public array $selectedComponents = [];
    public array $listsForFields = [];
    public array $groupedComponents = [];

    public function mount($panelId)
    {
        $this->panel = Panel::findOrFail($panelId);
        $this->selectedApps = $this->panel->components()->select('components.id')->pluck('id')->toArray();
        $this->initListsForFields();
    }

    public function save()
    {
        $this->panel->components()->sync($this->selectedComponents);
        Flux::modal('component-sync')->close();

        Flux::toast(
            variant: 'success',
            heading: 'Changes saved.',
            text: 'Apps updated successfully!',
        );
    }
    protected function initListsForFields(): void
    {
        $components = ComponentModel::with(['modules.apps'])
            ->where('is_inactive', false)
            ->get();

        $grouped = [];
        $added = [];

        foreach ($components as $component) {
            foreach ($component->modules as $module) {
                foreach ($module->apps as $app) {
                    $key = "{$app->id}_{$module->id}_{$component->id}";

                    if (!isset($added[$key])) {
                        $grouped[$app->name][$module->name][] = [
                            'id'   => $component->id,
                            'name' => $component->name,
                            'wire' => $component->wire,
                        ];
                        $added[$key] = true;
                    }
                }
            }
        }

        $this->groupedComponents = $grouped;

    }
    public function selectApp($appName)
    {
        $ids = collect($this->groupedComponents[$appName] ?? [])
            ->flatten(1)
            ->pluck('id')
            ->unique()
            ->values()
            ->all();

        $this->selectedComponents = array_unique(array_merge($this->selectedComponents, $ids));
    }

    public function deselectApp($appName)
    {
        $ids = collect($this->groupedComponents[$appName] ?? [])
            ->flatten(1)
            ->pluck('id')
            ->unique()
            ->values()
            ->all();

        $this->selectedComponents = array_values(array_diff($this->selectedComponents, $ids));
    }

    public function toggleModule($appName, $moduleName)
    {
        $ids = collect($this->groupedComponents[$appName][$moduleName] ?? [])
            ->pluck('id')
            ->unique()
            ->values()
            ->all();

        $allSelected = !array_diff($ids, $this->selectedComponents);

        if ($allSelected) {
            $this->selectedComponents = array_values(array_diff($this->selectedComponents, $ids));
        } else {
            $this->selectedComponents = array_unique(array_merge($this->selectedComponents, $ids));
        }
    }


    public function render()
    {
        return view()->file(app_path('Livewire/Saas/PanelMeta/blades/component-sync.blade.php'));
    }
}
