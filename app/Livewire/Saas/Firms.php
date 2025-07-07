<?php

namespace App\Livewire\Saas;

use App\Models\Saas\Agency;
use App\Models\Saas\Firm;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Flux;

class Firms extends Component
{
    use WithPagination, WithFileUploads;
    public $selectedId = null;
    public $selectedUserId = null;
    public $selectedPanelId = null;
    public array $listsForFields = [];
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $statuses;
    public $setMasterStatuses;
    public $formData = [
        'id' => null,
        'name' => '',
        'short_name' => '',
        'firm_type' => '',
        'agency_id' => '',
        'parent_firm_id' => '',
        'is_master_firm' => 0,
        'is_inactive'=> 0,
    ];

    public $isEditing = false;
    public $modal = false;

    // Add public properties for file uploads
    public $favicon;
    public $squareLogo;
    public $wideLogo;

    public $selectedComponentIds = [];
    public $availablePanels = [];
    public $availableComponents = [];
    public $availableModules = [];

    public $selectedFirmForUsers = null;
    public $isEditingUser = false;
    public $newUserFormData = [
        'name' => '',
        'email' => '',
        'password' => '',
        'phone' => '',
        'passcode' => '',
    ];

    // For panel assignment UI
    public $assignedPanelIds = [];

    public $selectedPanelName = '';

    public function mount()
    {
        $this->refreshStatuses();
        $this->refreshSetMasterStatus();
        $this->initListsForFields();
        $this->loadPanels();
        $this->loadAssignedPanels();
    }

    #[\Livewire\Attributes\Computed]
    public function list()
    {
        return Firm::query()
            ->with('agency', 'firm', 'panels')
            ->when($this->sortBy, fn($query) => $query->orderBy($this->sortBy, $this->sortDirection))
            ->paginate(20);
    }

    #[\Livewire\Attributes\Computed]
    public function firmUsers()
    {
        if ($this->selectedFirmForUsers) {
            return $this->selectedFirmForUsers->users()
                ->with('panels')  // Include panels relationship
                ->where('role_main', 'L1_firm')
                ->get();
        }
        return collect();
    }

    public function store()
    {
        $validatedData = $this->validate([
            'formData.name' => 'required|string|max:255',
            'formData.short_name' => 'nullable|string|max:255',
            'formData.firm_type' => 'nullable|string|max:255',
            'formData.agency_id' => 'nullable|integer|max:255',
            'formData.parent_firm_id' => 'nullable|integer|exists:firms,id',
            'formData.is_master_firm' => 'boolean',
            'formData.is_inactive' => 'boolean',
            'favicon' => 'nullable|image|max:1024',
            'squareLogo' => 'nullable|image|max:1024',
            'wideLogo' => 'nullable|image|max:1024',
        ]);

        // Convert empty strings to null
        $validatedData['formData'] = collect($validatedData['formData'])
            ->map(fn($val) => $val === '' ? null : $val)
            ->toArray();

        if ($this->isEditing) {
            // Editing: Update the record
            $firm = Firm::findOrFail($this->formData['id']);
            $firm->update($validatedData['formData']);
            $toastMsg = 'Record updated successfully';
        } else {
            $firm = Firm::create($validatedData['formData']);
            $toastMsg = 'Record added successfully';
        }

        // Handle file uploads
        if ($this->favicon) {
            $firm->addMedia($this->favicon->getRealPath())->toMediaCollection('favicon');
        }
        if ($this->squareLogo) {
            $firm->addMedia($this->squareLogo->getRealPath())->toMediaCollection('squareLogo');
        }
        if ($this->wideLogo) {
            $firm->addMedia($this->wideLogo->getRealPath())->toMediaCollection('wideLogo');
        }

        // Reset the form and editing state after saving
        $this->resetForm();
        $this->refreshStatuses();
        $this->refreshSetMasterStatus();
        $this->modal('mdl-firm')->close();
        Flux::toast(
            variant: 'success',
            heading: 'Changes saved.',
            text: $toastMsg,
        );
    }

    public function edit($id)
    {
        $this->formData = Firm::findOrFail($id)->toArray();
        $this->isEditing = true;
        $this->selectedComponentIds = [];
        $this->loadPanels();
        $this->availableComponents = [];
        $this->loadAssignedPanels($id);
        $this->modal('panel-component-access')->show();
    }

    public function delete($id)
    {
        Firm::findOrFail($id)->delete();
        Flux::toast(
            variant: 'success',
            heading: 'Record Deleted.',
            text: 'Record has been deleted successfully',
        );
    }

    public function resetForm()
    {
        $this->reset(['formData']);
        $this->formData['is_master_firm'] = 0; // or false
        $this->formData['is_inactive'] = 0; // or false
        $this->isEditing = false;
    }

    public function refreshStatuses()
    {
        $this->statuses = Firm::pluck('is_inactive', 'id')
            ->mapWithKeys(fn($val, $key) => [$key => (bool)$val])
            ->toArray();
    }
    public function toggleStatus($firmId)
    {
        $firm = Firm::find($firmId);
        $firm->is_inactive = !$firm->is_inactive;
        $firm->save();

        $this->statuses[$firmId] = $firm->is_inactive;
        $this->refreshStatuses();
    }

    public function refreshSetMasterStatus()
    {
        $this->setMasterStatuses = Firm::pluck('is_master_firm', 'id')
            ->mapWithKeys(fn($val, $key) => [$key => (bool)$val])
            ->toArray();
    }
    public function toggleSetMasterStatus($firmId)
    {
        $firm = Firm::find($firmId);
        $firm->is_master_firm = !$firm->is_master_firm;
        $firm->save();

        $this->setMasterStatuses[$firmId] = $firm->is_master_firm;
        $this->refreshSetMasterStatus();
    }

    protected function initListsForFields(): void
    {
        $this->listsForFields['agencylist'] = Agency::pluck('name', 'id')->toArray();
        $this->listsForFields['firmlist'] = Firm::pluck('name', 'id')->toArray();
        $this->listsForFields['firm_type'] = Firm::FIRM_TYPE_SELECT;
    }

    public function showAppAccess($firmId)
    {
        $this->selectedId = $firmId;
        $this->modal('app-access')->show();
    }

    public function removeLogo($collection)
    {
        $firm = Firm::findOrFail($this->formData['id']);
        $firm->clearMediaCollection($collection);
        Flux::toast(
            variant: 'success',
            heading: 'Logo Removed.',
            text: 'Logo has been removed successfully',
        );
    }

    public function openPanelComponentModal($firmId)
    {
        $this->selectedId = $firmId;
        $this->selectedPanelId = null;
        $this->selectedComponentIds = [];
        $this->loadPanels();
        $this->loadAssignedPanels($firmId);
        $this->availableComponents = [];

        $this->modal('panel-component-access')->show();
    }

    public function openFirmUsersModal($firmId)
    {
        $this->selectedFirmForUsers = Firm::find($firmId);
        $this->modal('firm-users-list-modal')->show();
    }

    public function openFirmUserAddModal()
    {
        $this->resetUserForm();
        $this->modal('firm-user-add-edit-modal')->show();
    }

    public function editFirmUser($userId)
    {
        $user = User::findOrFail($userId);
        $this->selectedUserId = $userId;
        $this->newUserFormData = [
            'name' => $user->name,
            'email' => $user->email,
            'password' => '', // Don't pre-fill password
            'phone' => $user->phone,
            'passcode' => $user->passcode,
        ];
        $this->isEditingUser = true;
        $this->modal('firm-user-add-edit-modal')->show();
    }

    public function storeFirmUser()
    {
        if (!$this->selectedFirmForUsers) {
            return;
        }

        $rules = [
            'newUserFormData.name' => 'required|string|max:255',
            'newUserFormData.email' => 'required|email|unique:users,email' . ($this->isEditingUser ? ',' . $this->selectedUserId : ''),
            'newUserFormData.password' => $this->isEditingUser ? 'nullable|string|min:6' : 'required|string|min:6',
            'newUserFormData.phone' => 'nullable|string',
            'newUserFormData.passcode' => 'nullable|string',
        ];

        $validatedData = $this->validate($rules)['newUserFormData'];

        if ($this->isEditingUser) {
            $user = User::find($this->selectedUserId);
            $updateData = $validatedData;
            if (!empty($updateData['password'])) {
                $updateData['password'] = Hash::make($updateData['password']);
            } else {
                unset($updateData['password']);
            }
            $user->update($updateData);
            $toastMsg = 'User updated successfully.';
        } else {
            $createData = $validatedData;
            $createData['role_main'] = 'L1_firm';
            $createData['password'] = Hash::make($createData['password']);
            $user = User::create($createData);
            $this->selectedFirmForUsers->users()->attach($user->id);
            $toastMsg = 'User created and assigned to firm.';
        }

        $this->modal('firm-user-add-edit-modal')->close();
        $this->resetUserForm();

        Flux::toast(
            variant: 'success',
            heading: 'Success',
            text: $toastMsg
        );
    }
    
    public function removeUserFromFirm($userId)
    {
        if ($this->selectedFirmForUsers) {
            $this->selectedFirmForUsers->users()->detach($userId);
            Flux::toast(
                variant: 'success',
                heading: 'User Removed',
                text: 'User has been removed from the firm.'
            );
        }
    }
    
    public function resetUserForm()
    {
        $this->reset(['newUserFormData', 'isEditingUser', 'selectedUserId']);
    }

    public function closePanelComponentModal()
    {
        $this->selectedPanelId = null;
        $this->selectedComponentIds = [];
        $this->availablePanels = [];
        $this->availableComponents = [];
        $this->modal('panel-component-access')->close();
    }

    public function loadPanels()
    {
        $this->availablePanels = \App\Models\Saas\Panel::all();
    }

    // Load assigned panels for the current firm (for assignedPanelIds)
    public function loadAssignedPanels($firmId = null)
    {
        $firmId = $firmId ?: ($this->formData['id'] ?? null);
        if ($firmId) {
            $this->assignedPanelIds = \App\Models\Saas\FirmPanel::where('firm_id', $firmId)->pluck('panel_id')->toArray();
        } else {
            $this->assignedPanelIds = [];
        }
    }

    // Save panel assignments for the firm
    public function savePanelAssignments()
    {
        $firmId = $this->selectedId;
        if (!$firmId) return;
        // Get currently assigned panels before sync
        $firm = Firm::findOrFail($firmId);
        $currentPanelIds = $firm->panels()->pluck('panels.id')->toArray();
        // Sync the firm_panel table
        $firm->panels()->sync($this->assignedPanelIds);
        $this->loadAssignedPanels($firmId);

        // Remove all component assignments for panels that were unassigned
        $removedPanels = array_diff($currentPanelIds, $this->assignedPanelIds);
        if (!empty($removedPanels)) {
            \App\Models\Saas\ComponentPanel::where('firm_id', $firmId)
                ->whereIn('panel_id', $removedPanels)
                ->delete();
        }
        Flux::modal('panel-component-acces')->close();
        \Flux::toast(
            variant: 'success',
            heading: 'Panels Updated',
            text: 'Panel assignments have been updated for this firm.'
        );
    }

    public function updatedSelectedPanelId($value)
    {
        $this->loadPanelComponents($value);
    }

    public function loadPanelComponents($panelId)
    {
        $panel = \App\Models\Saas\Panel::find($panelId);
        if (!$panel) {
            $this->availableComponents = [];
            $this->availableModules = [];
            $this->selectedComponentIds = [];
            return;
        }

        // Check if this panel is already assigned to the firm
        $isAssigned = \App\Models\Saas\FirmPanel::where('firm_id', $this->selectedId)
            ->where('panel_id', $panelId)
            ->exists();

        $components = $panel->components()->with('modules')->get();

        if ($isAssigned) {
            // EDIT MODE: Load components specifically for this firm-panel combo
            $this->selectedComponentIds = \App\Models\Saas\ComponentPanel::where('panel_id', $panelId)
                ->where('firm_id', $this->selectedId)
                ->pluck('component_id')->toArray();
        } else {
            // ASSIGN MODE: Start with no components selected.
            $this->selectedComponentIds = [];
        }

        // Group by module name for the view
        $grouped = [];
        foreach ($components as $component) {
            foreach ($component->modules as $module) {
                $grouped[$module->name][] = $component;
            }
        }
        $this->availableModules = $grouped;
        $this->availableComponents = $components;
    }

    public function toggleModuleComponents($moduleName)
    {
        $moduleComponents = $this->availableModules[$moduleName] ?? [];
        if (empty($moduleComponents)) {
            return;
        }
        $componentIdsInModule = collect($moduleComponents)->pluck('id')->all();
        $allSelected = !array_diff($componentIdsInModule, $this->selectedComponentIds);
        if ($allSelected) {
            $this->selectedComponentIds = array_values(array_diff($this->selectedComponentIds, $componentIdsInModule));
        } else {
            $this->selectedComponentIds = array_values(array_unique(array_merge($this->selectedComponentIds, $componentIdsInModule)));
        }
    }

    public function openPanelComponentsModal($panelId)
    {
        $panel = \App\Models\Saas\Panel::find($panelId);
        if (!$panel) return;
        $this->selectedPanelId = $panelId;
        $this->selectedPanelName = $panel->name;
        $this->loadPanelComponents($panelId);
        $this->modal('panel-components-modal')->show();
    }

    public function closePanelComponentsModal()
    {
        $this->selectedPanelId = null;
        $this->selectedPanelName = '';
        $this->selectedComponentIds = [];
        $this->availableModules = [];
        $this->modal('panel-components-modal')->close();
    }

    public function savePanelComponentSync()
    {
        $panelId = $this->selectedPanelId;
        $firmId = $this->selectedId;
        if (!$panelId || !$firmId) return;

        // Check if this panel is already assigned to the firm
        $isAssigned = \App\Models\Saas\FirmPanel::where('firm_id', $firmId)
            ->where('panel_id', $panelId)
            ->exists();

        if ($isAssigned) {
            // EDIT MODE: Sync the components for this firm-panel combo
            \App\Models\Saas\ComponentPanel::where('panel_id', $panelId)
                ->where('firm_id', $firmId)
                ->delete();
        } else {
            // ASSIGN MODE: Create the link in firm_panel
            \App\Models\Saas\FirmPanel::create([
                'firm_id' => $firmId,
                'panel_id' => $panelId,
            ]);
        }

        // Insert new component assignments
        foreach ($this->selectedComponentIds as $componentId) {
            \App\Models\Saas\ComponentPanel::create([
                'panel_id' => $panelId,
                'firm_id' => $firmId,
                'component_id' => $componentId,
            ]);
        }

        $this->closePanelComponentsModal();
        \Flux::toast(
            variant: 'success',
            heading: 'Panel Components Synced',
            text: 'Panel components have been updated for this firm.'
        );
    }

    public function showPanelSync($userId)
    {
        $this->selectedUserId = $userId;
        $this->modal('panel-sync')->show();
    }

    public function showComponentSync($userId, $panelId)
    {
        $this->selectedUserId = $userId;
        $this->selectedPanelId = $panelId;
        $this->modal('component-sync')->show();
    }

    public function showModuleSync($userId)
    {
        $this->selectedUserId = $userId;
        $this->modal('module-sync')->show();
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Saas/blades/firms.blade.php'));
    }

}
