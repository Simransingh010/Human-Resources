<?php

namespace App\Livewire\Saas;

use App\Models\Saas\Panel;
use Livewire\Component;
use Livewire\WithPagination;
use Flux;

class Panels extends Component
{
    use WithPagination;
    public $selectedPanelId = null;
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $statuses;
    public array $listsForFields = [];
    public $formData = [
        'id' => null,
        'name' => '',
        'code' => '',
        'description' => '',
        'panel_type' => '',
        'is_inactive' => 0,
    ];

    public $isEditing = false;
    public $appOpen = [];
    public $moduleOpen = [];
    public $componentOpen = [];

    public function mount()
    {
//        debugTreeStructure();
        $this->resetPage();
        $this->refreshStatuses();
        $this->initListsForFields();
        $this->appOpen = [];
        $this->moduleOpen = [];
        $this->componentOpen = [];
    }

    public function refreshStatuses()
    {
        $this->statuses = Panel::pluck('is_inactive', 'id')
            ->mapWithKeys(fn($val, $key) => [$key => (bool)$val])
            ->toArray();
    }

    #[\Livewire\Attributes\Computed]
    public function list()
    {
        return Panel::query()
            ->when($this->sortBy, fn($query) => $query->orderBy($this->sortBy, $this->sortDirection))
            ->paginate(5);
    }

    public function store()
    {
        $validatedData = $this->validate([
            'formData.name' => 'required|string|max:255',
            'formData.code' => 'nullable|string|max:255',
            'formData.description' => 'nullable|string',
            'formData.panel_type' => 'required|string|max:255',
            'formData.is_inactive' => 'boolean',
        ]);

        // Convert empty strings to null
        $validatedData['formData'] = collect($validatedData['formData'])
            ->map(fn($val) => $val === '' ? null : $val)
            ->toArray();

        if ($this->isEditing) {
            $panel = Panel::findOrFail($this->formData['id']);
            $panel->update($validatedData['formData']);
            $toastMsg = 'Panel updated successfully';
        } else {
            Panel::create($validatedData['formData']);
            $toastMsg = 'Panel added successfully';
        }

        $this->resetForm();
        $this->refreshStatuses();
        $this->modal('mdl-panel')->close();
        Flux::toast(
            variant: 'success',
            heading: 'Changes saved.',
            text: $toastMsg,
        );
    }

    public function resetForm()
    {
        $this->reset(['formData']);
        $this->formData['is_inactive'] = 0;
        $this->isEditing = false;
    }

    public function edit($id)
    {
        $this->formData = Panel::findOrFail($id)->toArray();
        $this->isEditing = true;
        $this->modal('mdl-panel')->show();
    }

    public function delete($id)
    {
        Panel::findOrFail($id)->delete();
        Flux::toast(
            variant: 'success',
            heading: 'Panel Deleted.',
            text: 'Panel has been deleted successfully',
        );
    }

    public function toggleStatus($panelId)
    {
        $panel = Panel::find($panelId);
        $panel->is_inactive = !$panel->is_inactive;
        $panel->save();

        $this->statuses[$panelId] = $panel->is_inactive;
        $this->refreshStatuses();
    }

    /**
     * @return array
     */
    protected function initListsForFields(): void
    {
            $this->listsForFields['panel_type'] = Panel::PANEL_TYPE_SELECT;
    }

    public function showAppSync($panelId)
    {
        $this->selectedPanelId = $panelId;
        $this->modal('app-sync')->show();
    }

    public function showModuleSync($panelId)
    {
        $this->selectedPanelId = $panelId;
        $this->modal('module-sync')->show();
    }

    public function showComponentSync($panelId)
    {
        $this->selectedPanelId = $panelId;
        $this->modal('component-sync')->show();
    }

    /**
     * Get the hierarchy for the selected panel only: Apps → Modules → Components → Actions
     */
    public function getPanelTreeHierarchyProperty()
    {
        if (!$this->selectedPanelId) {
            return collect();
        }
        
        // Get the panel with its directly associated components
        $panel = \App\Models\Saas\Panel::with([
            'components.modules.apps',
            'components.actions'
        ])->find($this->selectedPanelId);
        
        if (!$panel) return collect();
        
        // Group components by their app and module
        $grouped = [];
        
        foreach ($panel->components as $component) {
            foreach ($component->modules as $module) {
                foreach ($module->apps as $app) {
                    if (!isset($grouped[$app->id])) {
                        $grouped[$app->id] = [
                            'id' => $app->id,
                            'name' => $app->name,
                            'type' => 'app',
                            'modules' => []
                        ];
                    }
                    
                    if (!isset($grouped[$app->id]['modules'][$module->id])) {
                        $grouped[$app->id]['modules'][$module->id] = [
                            'id' => $module->id,
                            'name' => $module->name,
                            'type' => 'module',
                            'components' => []
                        ];
                    }
                    
                    // Only add component if not already added
                    $componentExists = false;
                    foreach ($grouped[$app->id]['modules'][$module->id]['components'] as $existingComponent) {
                        if ($existingComponent['id'] === $component->id) {
                            $componentExists = true;
                            break;
                        }
                    }
                    
                    if (!$componentExists) {
                        $grouped[$app->id]['modules'][$module->id]['components'][] = [
                            'id' => $component->id,
                            'name' => $component->name,
                            'type' => 'component',
                            'actions' => $component->actions->map(function($action) {
                                return [
                                    'id' => $action->id,
                                    'name' => $action->name,
                                    'type' => 'action',
                                ];
                            }),
                        ];
                    }
                }
            }
        }
        
        // Convert to array format expected by the view
        return collect($grouped)->map(function($app) {
            $app['modules'] = collect($app['modules'])->values();
            return $app;
        })->values();
    }

    public function showPanelStructure($panelId)
    {
        $this->selectedPanelId = $panelId;
        $this->modal('panel-structure')->show();
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Saas/blades/panels.blade.php'), [
            'panelTreeHierarchy' => $this->panelTreeHierarchy,
        ]);
    }
}
