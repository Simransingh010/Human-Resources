<?php

namespace App\Livewire\Saas;

use App\Models\User;
use App\Models\Saas\Panel;
use App\Models\Saas\Agency;
use App\Models\Saas\Firm;
use Livewire\Component;
use Livewire\WithPagination;
use Flux;

class Users extends Component
{
    use WithPagination;

    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $statuses;
    public $formData = [
        'id' => null,
        'name' => '',
        'email' => '',
        'password' => '',
        'passcode' => '',
        'phone' => '',
        'is_inactive' => 0,
    ];

    // Field configuration for form and table
    public array $fieldConfig = [
        'name' => ['label' => 'Name', 'type' => 'text'],
        'email' => ['label' => 'Email', 'type' => 'text'],
        'phone' => ['label' => 'Phone', 'type' => 'text'],
        'passcode' => ['label' => 'Passcode', 'type' => 'text'],
        'is_inactive' => ['label' => 'Status', 'type' => 'switch'],
    ];

    // Filter fields configuration
    public array $filterFields = [
        'name' => ['label' => 'Name', 'type' => 'text'],
        'email' => ['label' => 'Email', 'type' => 'text'],
        'phone' => ['label' => 'Phone', 'type' => 'text'],
        'is_inactive' => ['label' => 'Status', 'type' => 'select', 'listKey' => 'status'],
    ];

    public array $listsForFields = [];
    public array $filters = [];
    public array $visibleFields = [];
    public array $visibleFilterFields = [];

    public $isEditing = false;
    public $modal = false;
    public $panels;
    public $selectedUserId = null;

    public function mount()
    {
        $this->refreshStatuses();
        $this->initListsForFields();
        
        // Set default visible fields
        $this->visibleFields = ['name', 'email', 'phone', 'is_inactive'];
        $this->visibleFilterFields = ['name', 'email', 'phone'];
        
        // Initialize filters
        $this->filters = array_fill_keys(array_keys($this->filterFields), '');
    }

    public function refreshStatuses()
    {
        $this->statuses = User::pluck('is_inactive', 'id')
            ->mapWithKeys(fn($val, $key) => [$key => (bool)$val])
            ->toArray();
    }

    protected function initListsForFields(): void
    {
        $this->listsForFields['panellist'] = Panel::pluck('name', 'id')->toArray();
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

    #[\Livewire\Attributes\Computed]
    public function list()
    {
        return User::query()
            ->when($this->filters['name'], fn($query, $value) => 
                $query->where('name', 'like', "%{$value}%"))
            ->when($this->filters['email'], fn($query, $value) => 
                $query->where('email', 'like', "%{$value}%"))
            ->when($this->filters['phone'], fn($query, $value) => 
                $query->where('phone', 'like', "%{$value}%"))
            ->when($this->filters['is_inactive'] !== '', fn($query) => 
                $query->where('is_inactive', $this->filters['is_inactive']))
            ->when($this->sortBy, fn($query) => $query->orderBy($this->sortBy, $this->sortDirection))
            ->paginate(100);
    }

    public function store()
    {


        $validatedData = $this->validate([
            'formData.name' => 'required|string|max:255',
            'formData.email' => 'nullable|string|max:255',
            'formData.password' => 'nullable|string|max:255',
            'formData.passcode' => 'nullable|string|max:255',
            'formData.phone' => 'nullable|string|max:9999999999',
            'formData.is_inactive' => 'boolean',
            'panels' => ['array'],
            'panels.*' => ['exists:panels,id'],

        ]);

        // Convert empty strings to null
        $validatedData['formData'] = collect($validatedData['formData'])
            ->map(fn($val) => $val === '' ? null : $val)
            ->toArray();

        if ($this->isEditing) {
            $user = User::findOrFail($this->formData['id']);
            $user->update($validatedData['formData']);
            $toastMsg = 'Record updated successfully';
        } else {
            $user = User::create($validatedData['formData']);
            $toastMsg = 'Record added successfully';
        }

        $user->panels()->sync($this->panels);

        // Reset the form and editing state after saving
        $this->resetForm();
        $this->refreshStatuses();
        $this->modal('mdl-user')->close();
        Flux::toast(
            variant: 'success',
            heading: 'Changes saved.',
            text: $toastMsg,
        );

    }

    public function resetForm()
    {
        $this->reset(['formData']);
        $this->formData['is_inactive'] = 0; // or false
        $this->isEditing = false;
    }

    public function edit($id)
    {
        $this->formData = User::findOrFail($id)->toArray();
        $this->isEditing = true;
        $this->modal('mdl-user')->show();

    }
    public function showmodal_panelSync($userId)
    {
        $this->selectedUserId = $userId;
        $this->modal('panel-sync')->show();
    }
    public function showmodal_firmSync($userId)
    {
        $this->selectedUserId = $userId;
        $this->modal('firm-sync')->show();
    }
    public function showmodal_permissionGroupSync($userId)
    {
        $this->selectedUserId = $userId;
        $this->modal('permission-group-sync')->show();
    }
    public function showmodal_permissionSync($userId)
    {
        $this->selectedUserId = $userId;
        $this->modal('permission-sync')->show();
    }
    public function delete($id)
    {
        User::findOrFail($id)->delete();
        Flux::toast(
            variant: 'success',
            heading: 'Record Deleted.',
            text: 'Record has been deleted successfully',
        );
    }

    public function toggleStatus($firmId)
    {
        $firm = User::find($firmId);
        $firm->is_inactive = !$firm->is_inactive;
        $firm->save();

        $this->statuses[$firmId] = $firm->is_inactive;
        $this->refreshStatuses();
    }
    public function render()
    {
        return view()->file(app_path('Livewire/Saas/blades/users.blade.php'));
    }

}
