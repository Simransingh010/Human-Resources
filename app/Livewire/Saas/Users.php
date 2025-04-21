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

    public array $listsForFields = [];
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

    public $isEditing = false;
    public $modal = false;
    public $panels;
    public $selectedUserId = null;

    public function mount()
    {
        $this->refreshStatuses();
        $this->initListsForFields();
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

    }

    #[\Livewire\Attributes\Computed]
    public function list()
    {
        return User::query()
            ->when($this->sortBy, fn($query) => $query->orderBy($this->sortBy, $this->sortDirection))
            ->paginate(5);
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
