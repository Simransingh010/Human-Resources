<?php

namespace App\Livewire\Saas;

use App\Models\Saas\Componentcluster;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Flux;

class Componentclusters extends Component
{
    use WithPagination;

    public $perPage = 10;
    public $sortBy = 'name';
    public $sortDirection = 'asc';
    public $isEditing = false;

    // Field configuration for form and table
    public array $fieldConfig = [
        'name' => ['label' => 'Name', 'type' => 'text'],
        'code' => ['label' => 'Code', 'type' => 'text'],
        'description' => ['label' => 'Description', 'type' => 'textarea'],
        'icon' => ['label' => 'Icon', 'type' => 'text'],
        'color' => ['label' => 'Color', 'type' => 'text'],
        'tooltip' => ['label' => 'Tooltip', 'type' => 'text'],
        'order' => ['label' => 'Order', 'type' => 'number'],
        'badge' => ['label' => 'Badge', 'type' => 'text'],
        'custom_css' => ['label' => 'Custom CSS', 'type' => 'textarea'],
        'parent_componentcluster_id' => ['label' => 'Parent Component Cluster', 'type' => 'select', 'listKey' => 'componentclusters'],
        'is_inactive' => ['label' => 'Inactive', 'type' => 'switch'],
    ];

    // Filter fields configuration
    public array $filterFields = [
        'name' => ['label' => 'Name', 'type' => 'text'],
        'code' => ['label' => 'Code', 'type' => 'text'],
        'icon' => ['label' => 'Icon', 'type' => 'text'],
        'order' => ['label' => 'Order', 'type' => 'number'],
        'parent_componentcluster_id' => ['label' => 'Parent Component Cluster', 'type' => 'select', 'listKey' => 'componentclusters'],
    ];

    public array $listsForFields = [];
    public array $filters = [];
    public array $visibleFields = [];
    public array $visibleFilterFields = [];

    public $formData = [
        'id' => null,
        'name' => '',
        'code' => '',
        'description' => '',
        'icon' => '',
        'color' => '',
        'tooltip' => '',
        'order' => null,
        'badge' => '',
        'custom_css' => '',
        'parent_componentcluster_id' => null,
        'is_inactive' => false,
    ];

    public function mount()
    {
        $this->resetPage();
        $this->initListsForFields();
        
        // Set default visible fields
        $this->visibleFields = ['name', 'code', 'icon', 'order', 'parent_componentcluster_id'];
        $this->visibleFilterFields = ['name', 'code', 'parent_componentcluster_id'];
        
        // Initialize filters
        $this->filters = array_fill_keys(array_keys($this->filterFields), '');
    }

    protected function initListsForFields(): void
    {
        $this->listsForFields['componentclusters'] = Componentcluster::pluck('name', 'id')
            ->toArray();
    }

    public function applyFilters()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->filters = array_fill_keys(array_keys($this->filterFields), '');
        $this->resetPage();
    }

    public function toggleColumn(string $field)
    {
        if (in_array($field, $this->visibleFields)) {
            $this->visibleFields = array_filter(
                $this->visibleFields,
                fn($f) => $f !== $field
            );
        } else {
            $this->visibleFields[] = $field;
        }
    }

    public function toggleFilterColumn(string $field)
    {
        if (in_array($field, $this->visibleFilterFields)) {
            $this->visibleFilterFields = array_filter(
                $this->visibleFilterFields,
                fn($f) => $f !== $field
            );
        } else {
            $this->visibleFilterFields[] = $field;
        }
    }

    #[Computed]
    public function list()
    {
        return Componentcluster::query()
            ->with('componentcluster')
            ->when($this->filters['name'], fn($query, $value) => 
                $query->where('name', 'like', "%{$value}%"))
            ->when($this->filters['code'], fn($query, $value) => 
                $query->where('code', 'like', "%{$value}%"))
            ->when($this->filters['icon'], fn($query, $value) => 
                $query->where('icon', 'like', "%{$value}%"))
            ->when($this->filters['order'], fn($query, $value) => 
                $query->where('order', $value))
            ->when($this->filters['parent_componentcluster_id'], fn($query, $value) => 
                $query->where('parent_componentcluster_id', $value))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);
    }

    protected function rules()
    {
        return [
            'formData.name' => 'required|string|max:255',
            'formData.code' => 'nullable|string|max:255',
            'formData.description' => 'nullable|string',
            'formData.icon' => 'nullable|string|max:255',
            'formData.color' => 'nullable|string|max:255',
            'formData.tooltip' => 'nullable|string|max:255',
            'formData.order' => 'nullable|integer',
            'formData.badge' => 'nullable|string|max:255',
            'formData.custom_css' => 'nullable|string',
            'formData.parent_componentcluster_id' => 'nullable|exists:componentclusters,id',
            'formData.is_inactive' => 'boolean'
        ];
    }

    public function store()
    {
        $validatedData = $this->validate();

        $validatedData['formData'] = collect($validatedData['formData'])
            ->map(fn($val) => $val === '' ? null : $val)
            ->toArray();

        if ($this->isEditing) {
            $componentcluster = Componentcluster::findOrFail($this->formData['id']);
            $componentcluster->update($validatedData['formData']);
            $toastMsg = 'Component cluster updated successfully';
        } else {
            Componentcluster::create($validatedData['formData']);
            $toastMsg = 'Component cluster added successfully';
        }

        $this->resetForm();
        $this->modal('mdl-componentcluster')->close();
        Flux::toast(
            variant: 'success',
            heading: 'Changes saved.',
            text: $toastMsg,
        );
    }

    public function resetForm()
    {
        $this->reset(['formData']);
        $this->formData['is_inactive'] = false;
        $this->formData['order'] = null;
        $this->isEditing = false;
    }

    public function edit($id)
    {
        $this->isEditing = true;
        $componentcluster = Componentcluster::findOrFail($id);
        $this->formData = $componentcluster->toArray();
        $this->modal('mdl-componentcluster')->show();
    }

    public function delete($id)
    {
        // Check if component cluster has related records
        $componentcluster = Componentcluster::findOrFail($id);
        if ($componentcluster->components()->count() > 0 || $componentcluster->componentclusters()->count() > 0) {
            Flux::toast(
                variant: 'error',
                heading: 'Cannot Delete',
                text: 'This component cluster has related records and cannot be deleted.',
            );
            return;
        }

        $componentcluster->delete();
        Flux::toast(
            variant: 'success',
            heading: 'Record Deleted.',
            text: 'Component cluster has been deleted successfully',
        );
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Saas/blades/componentclusters.blade.php'));
    }
} 