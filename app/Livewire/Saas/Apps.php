<?php

namespace App\Livewire\Saas;

use App\Models\Saas\App;
use App\Models\Saas\Panel;
use Livewire\Component;
use Livewire\WithPagination;
use Flux;

class Apps extends Component
{
    use WithPagination;

    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $statuses;
    public array $listsForFields = [];

    // Add filter properties
    public $filters = [
        'search_name' => '',
        'search_code' => '',
        'search_route' => '',
        'panel_id' => '',
        'is_active' => '',
    ];

    public $formData = [
        'id' => null,
        'name' => '',
        'code' => '',
        'description' => '',
        'icon' => '',
        'route' => '',
        'color' => '',
        'tooltip' => '',
        'order' => 0,
        'badge' => '',
        'custom_css' => '',
        'is_inactive' => 0,
    ];

    public $isEditing = false;
    public $selectedPanels = [];
    public $selectedAppId = null;

    public function mount()
    {
        $this->resetPage();
        $this->refreshStatuses();
        $this->initListsForFields();
    }

    public function refreshStatuses()
    {
        $this->statuses = App::pluck('is_inactive', 'id')
            ->mapWithKeys(fn($val, $key) => [$key => (bool)$val])
            ->toArray();
    }

    protected function initListsForFields(): void
    {
        $this->listsForFields['panels'] = Panel::where('is_inactive', false)
            ->pluck('name', 'id')
            ->toArray();
    }

    #[\Livewire\Attributes\Computed]
    public function list()
    {
        return App::query()
            ->when($this->filters['search_name'], function($query) {
                $query->where('name', 'like', '%' . $this->filters['search_name'] . '%');
            })
            ->when($this->filters['search_code'], function($query) {
                $query->where('code', 'like', '%' . $this->filters['search_code'] . '%');
            })
            ->when($this->filters['search_route'], function($query) {
                $query->where('route', 'like', '%' . $this->filters['search_route'] . '%');
            })
            ->when($this->filters['is_active'] !== '', function($query) {
                $query->where('is_inactive', $this->filters['is_active'] === 'inactive');
            })
            ->when($this->filters['panel_id'], function ($query) {
                $query->whereHas('app_modules.modules.components.panels', function ($q) {
                    $q->where('panels.id', $this->filters['panel_id']);
                });
            })
            ->with('app_modules.modules.components.panels') // Optional: eager load
            ->when($this->sortBy, fn($query) => $query->orderBy($this->sortBy, $this->sortDirection))
            ->paginate(5);

    }

    public function store()
    {
        $validatedData = $this->validate([
            'formData.name' => 'required|string|max:255',
            'formData.code' => 'nullable|string|max:255',
            'formData.description' => 'nullable|string',
            'formData.icon' => 'nullable|string|max:255',
            'formData.route' => 'nullable|string|max:255',
            'formData.color' => 'nullable|string|max:255',
            'formData.tooltip' => 'nullable|string|max:255',
            'formData.order' => 'required|integer',
            'formData.badge' => 'nullable|string|max:255',
            'formData.custom_css' => 'nullable|string',
            'formData.is_inactive' => 'boolean',
            'selectedPanels' => 'array',
        ]);

        // Convert empty strings to null
        $validatedData['formData'] = collect($validatedData['formData'])
            ->map(fn($val) => $val === '' ? null : $val)
            ->toArray();

        if ($this->isEditing) {
            $app = App::findOrFail($this->formData['id']);
            $app->update($validatedData['formData']);
            $toastMsg = 'App updated successfully';
        } else {
            $app = App::create($validatedData['formData']);
            $toastMsg = 'App added successfully';
        }

        $this->resetForm();
        $this->refreshStatuses();
        $this->modal('mdl-app')->close();
        Flux::toast(
            variant: 'success',
            heading: 'Changes saved.',
            text: $toastMsg,
        );
    }

    public function resetForm()
    {
        $this->reset(['formData', 'selectedPanels']);
        $this->formData['is_inactive'] = 0;
        $this->formData['order'] = 0;
        $this->isEditing = false;
    }
    public function clearFilters()
    {
        $this->reset('filters');
        $this->resetPage();
    }
    public function edit($id)
    {
        $app = App::with('panels')->findOrFail($id);
        $this->formData = $app->toArray();
        $this->isEditing = true;
        $this->modal('mdl-app')->show();
    }

    public function delete($id)
    {
        App::findOrFail($id)->delete();
        Flux::toast(
            variant: 'success',
            heading: 'App Deleted.',
            text: 'App has been deleted successfully',
        );
    }

    public function toggleStatus($appId)
    {
        $app = App::find($appId);
        $app->is_inactive = !$app->is_inactive;
        $app->save();

        $this->statuses[$appId] = $app->is_inactive;
        $this->refreshStatuses();
    }

    public function showModuleSync($appId)
    {
        $this->selectedAppId = $appId;
        $this->modal('module-sync')->show();
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Saas/blades/apps.blade.php'));
    }
}
