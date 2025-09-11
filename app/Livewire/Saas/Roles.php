<?php

namespace App\Livewire\Saas;

use App\Models\Saas\Role;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;
use Flux;

class Roles extends Component
{
    use WithPagination;

    public $perPage = 10;
    public $sortBy = 'name';
    public $sortDirection = 'asc';
    public $isEditing = false;
    public $selectedRoleId = null;
    public $firmId = null; // Add this property to accept firm_id parameter
    // Field configuration for form and table
    public array $fieldConfig = [
        'name' => ['label' => 'Role Name', 'type' => 'text'],
        'description' => ['label' => 'Description', 'type' => 'textarea'],
        'is_inactive' => ['label' => 'Inactive', 'type' => 'switch'],
    ];

    // Filter fields configuration
    public array $filterFields = [
        'name' => ['label' => 'Role Name', 'type' => 'text'],
        'description' => ['label' => 'Description', 'type' => 'text'],
        'is_inactive' => ['label' => 'Status', 'type' => 'select', 'listKey' => 'status'],
    ];

    public array $listsForFields = [];
    public array $filters = [];
    public array $visibleFields = [];
    public array $visibleFilterFields = [];

    public $formData = [
        'id' => null,
        'firm_id' => null,
        'name' => '',
        'description' => '',
        'is_inactive' => false,
    ];

    public function mount($firmId = null)
    {
        $this->firmId = $firmId;
        $this->resetPage();
        $this->initListsForFields();
        
        // Set default visible fields
        $this->visibleFields = ['name', 'description', 'is_inactive'];
        $this->visibleFilterFields = ['name', 'description', 'is_inactive'];
        
        // Initialize filters
        $this->filters = array_fill_keys(array_keys($this->filterFields), '');
    }

    protected function initListsForFields(): void
    {
        $this->listsForFields['status'] = [
            '0' => 'Active',
            '1' => 'Inactive'
        ];
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
        $query = Role::query();
        
        // If firm_id is provided, show firm-specific roles, otherwise show global roles
        if ($this->firmId !== null) {
            $query->where('firm_id', $this->firmId);
        } else {
            $query->whereNull('firm_id'); // Only show global roles
        }
        
        return $query
            ->when($this->filters['name'], fn($query, $value) => 
                $query->where('name', 'like', "%{$value}%"))
            ->when($this->filters['description'], fn($query, $value) => 
                $query->where('description', 'like', "%{$value}%"))
            ->when($this->filters['is_inactive'] !== '', fn($query, $value) => 
                $query->where('is_inactive', $value))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);
    }

    protected function rules()
    {
        return [
            'formData.name' => 'required|string|max:255',
            'formData.description' => 'nullable|string',
            'formData.is_inactive' => 'boolean',
        ];
    }

    public function store()
    {
        $validatedData = $this->validate();

        $validatedData['formData'] = collect($validatedData['formData'])
            ->map(fn($val) => $val === '' ? null : $val)
            ->toArray();

        // Set firm_id based on the current context
        if ($this->firmId !== null) {
            $validatedData['formData']['firm_id'] = $this->firmId;
        } else {
            $validatedData['formData']['firm_id'] = null; // Global role
        }

        if ($this->isEditing) {
            $role = Role::findOrFail($this->formData['id']);
            $role->update($validatedData['formData']);
            $toastMsg = $this->firmId !== null ? 'Firm role updated successfully' : 'Global role updated successfully';
        } else {
            Role::create($validatedData['formData']);
            $toastMsg = $this->firmId !== null ? 'Firm role added successfully' : 'Global role added successfully';
        }

        $this->resetForm();
        $this->modal('mdl-role')->close();
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
        $this->isEditing = false;
    }

    public function edit($id)
    {
        $this->isEditing = true;
        $role = Role::findOrFail($id);
        $this->formData = $role->toArray();
        $this->modal('mdl-role')->show();
    }

    public function delete($id)
    {
        // Check if role has related records
        $role = Role::findOrFail($id);
        if ($role->users()->count() > 0 || $role->actions()->count() > 0) {
            Flux::toast(
                variant: 'error',
                heading: 'Cannot Delete',
                text: 'This role has related users or actions and cannot be deleted.',
            );
            return;
        }

        $role->delete();
        Flux::toast(
            variant: 'success',
            heading: 'Record Deleted.',
            text: 'Role has been deleted successfully',
        );
    }

    public function showRoleActionSync($roleId)
    {
        $this->selectedRoleId = $roleId;
        $this->modal('role-action-sync')->show();
    }

    public function render()
    {
        return View::file(app_path('Livewire/Saas/blades/roles.blade.php'));
    }
}
