<?php

namespace App\Livewire\Saas;

use App\Models\Saas\Agency;
use App\Models\Saas\Firm;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
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
    public $faviconUrl = '';
    public $squareLogoUrl = '';
    public $wideLogoUrl = '';

    public $selectedComponentIds = [];
    public $availablePanels = [];
    public $availableComponents = [];
    public $availableModules = [];
    public $availableAppModules = [];
    public $selectedAppName = null;

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

    // --- Role Modal State ---
    public $showRoleModal = false;
    public $roleModalUserId = null;
    public $roleModalUserName = '';
    public $roleModalSelectedRoles = [];
    public $roleModalAvailableRoles = [];
    public $previousRoleModalSelectedRoles = [];

    // --- Action Modal State ---
    public $showActionModal = false;
    public $actionModalUserId = null;
    public $actionModalUserName = '';
    public $actionModalSelectedActions = [];
    public $actionModalActionScopes = [];
    public $actionModalAvailableActions = [];
    public $actionModalGroupedActions = [];
    public $actionModalAppList = [];
    public $actionModalSelectedApp = null;
    public $previousActionModalSelectedActions = [];

    public $firmRolesModalFirmId = null;

    // --- Filters/Search ---
    public array $filters = [
        'q' => '',
        'firm_type' => '',
        'agency_id' => '',
        'status' => '', // '', 'active', 'inactive'
    ];

    public function openFirmRolesModal($firmId)
    {
        $this->firmRolesModalFirmId = $firmId;
        $this->modal('firm-roles-modal')->show();
    }

    public function mount()
    {
        $this->refreshStatuses();
        $this->refreshSetMasterStatus();
        $this->initListsForFields();
        $this->loadPanels();
        $this->loadAssignedPanels();
        $this->filters = $this->filters + [
            'q' => $this->filters['q'] ?? '',
            'firm_type' => $this->filters['firm_type'] ?? '',
            'agency_id' => $this->filters['agency_id'] ?? '',
            'status' => $this->filters['status'] ?? '',
        ];
    }

    #[\Livewire\Attributes\Computed]
    public function list()
    {
        $page = request('page', 1);
        $cacheKey = 'firms_list_' . md5(json_encode($this->filters) . '|' . $this->sortBy . '|' . $this->sortDirection . '|' . $page);
        return Cache::remember($cacheKey, 60, function () {
            return Firm::query()
                ->with(['agency', 'firm', 'panels', 'media'])
                ->when($this->filters['q'] ?? null, function ($query, $value) {
                    $query->where(function ($q) use ($value) {
                        $q->where('name', 'like', "%{$value}%")
                          ->orWhere('short_name', 'like', "%{$value}%");
                    });
                })
                ->when($this->filters['firm_type'] ?? null, fn($q, $val) => $q->where('firm_type', $val))
                ->when($this->filters['agency_id'] ?? null, fn($q, $val) => $q->where('agency_id', $val))
                ->when($this->filters['status'] ?? null, function ($q, $val) {
                    if ($val === 'active') { $q->where('is_inactive', false); }
                    if ($val === 'inactive') { $q->where('is_inactive', true); }
                })
                ->when($this->sortBy, fn($query) => $query->orderBy($this->sortBy, $this->sortDirection))
                ->paginate(20);
        });
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
            'formData.agency_id' => 'nullable|integer|exists:agencies,id',
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
        Cache::flush(); // Invalidate list caches after changes
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
        $firm = Firm::with('media')->findOrFail($id);
        $this->formData = $firm->toArray();
        $this->isEditing = true;
        $this->selectedId = $id;
        // Preload logo URLs for preview without DB hits in Blade
        $this->faviconUrl = $firm->getFirstMediaUrl('favicon') ?: '';
        $this->squareLogoUrl = $firm->getFirstMediaUrl('squareLogo') ?: '';
        $this->wideLogoUrl = $firm->getFirstMediaUrl('wideLogo') ?: '';
        $this->modal('mdl-firm')->show();
    }

    public function delete($id)
    {
        Firm::findOrFail($id)->delete();
        Cache::flush();
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
        $this->favicon = null;
        $this->squareLogo = null;
        $this->wideLogo = null;
        $this->faviconUrl = '';
        $this->squareLogoUrl = '';
        $this->wideLogoUrl = '';
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
        Cache::flush();
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
        Cache::flush();
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
        // Reflect removal immediately in previews
        if ($collection === 'favicon') { $this->faviconUrl = ''; }
        if ($collection === 'squareLogo') { $this->squareLogoUrl = ''; }
        if ($collection === 'wideLogo') { $this->wideLogoUrl = ''; }
        Flux::toast(
            variant: 'success',
            heading: 'Logo Removed.',
            text: 'Logo has been removed successfully',
        );
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
        Flux::modal('panel-component-access')->close();
        \Flux::toast(
            variant: 'success',
            heading: 'Panels Updated',
            text: 'Panel assignments have been updated for this firm.'
        );
        Cache::flush();
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
            $this->availableAppModules = [];
            $this->selectedComponentIds = [];
            return;
        }
        $firmId = $this->selectedId;
        $isAssigned = \App\Models\Saas\FirmPanel::where('firm_id', $firmId)
            ->where('panel_id', $panelId)
            ->exists();
        $components = $panel->components()->with('modules')->get();
        if ($isAssigned) {
            $this->selectedComponentIds = \App\Models\Saas\ComponentPanel::where('panel_id', $panelId)
                ->where('firm_id', $firmId)
                ->pluck('component_id')->toArray();
        } else {
            $this->selectedComponentIds = [];
        }
        // Build $availableAppModules: [AppName => [ModuleName => [Component, ...], ...], ...]
        $apps = \App\Models\Saas\App::with([
            'modules.components' => function ($q) use ($panelId) {
                $q->whereHas('panels', function ($q2) use ($panelId) {
                    $q2->where('panels.id', $panelId);
                });
            }
        ])->where('is_inactive', false)->get();
        $grouped = [];
        foreach ($apps as $app) {
            foreach ($app->modules as $module) {
                $componentsArr = [];
                foreach ($module->components as $component) {
                    $componentsArr[] = [
                        'id' => $component->id,
                        'name' => $component->name,
                    ];
                }
                if (!empty($componentsArr)) {
                    $grouped[$app->name][$module->name] = $componentsArr;
                }
            }
        }
        $this->availableAppModules = $grouped;
        if (empty($this->selectedAppName) && !empty($grouped)) {
            $this->selectedAppName = array_key_first($grouped);
        }
        // For backward compatibility, also set availableModules and availableComponents
        $groupedModules = [];
        $processedComponents = [];
        foreach ($components as $component) {
            if (in_array($component->id, $processedComponents)) continue;
            $processedComponents[] = $component->id;
            if ($component->modules->isNotEmpty()) {
                $firstModule = $component->modules->first();
                $groupedModules[$firstModule->name][] = $component;
            }
        }
        $this->availableModules = $groupedModules;
        $this->availableComponents = $components->unique('id');
    }

    public function toggleAppComponents($appName)
    {
        $ids = [];
        foreach ($this->availableAppModules[$appName] ?? [] as $moduleComponents) {
            foreach ($moduleComponents as $comp) {
                $ids[] = is_array($comp) ? $comp['id'] : $comp->id;
            }
        }
        $allSelected = !array_diff($ids, $this->selectedComponentIds);
        if ($allSelected) {
            $this->selectedComponentIds = array_values(array_diff($this->selectedComponentIds, $ids));
        } else {
            $this->selectedComponentIds = array_unique(array_merge($this->selectedComponentIds, $ids));
        }
    }

    public function toggleModuleComponents($appName, $moduleName)
    {
        $moduleComponents = $this->availableAppModules[$appName][$moduleName] ?? [];
        $ids = [];
        foreach ($moduleComponents as $comp) {
            $ids[] = is_array($comp) ? $comp['id'] : $comp->id;
        }
        $allSelected = !array_diff($ids, $this->selectedComponentIds);
        if ($allSelected) {
            $this->selectedComponentIds = array_values(array_diff($this->selectedComponentIds, $ids));
        } else {
            $this->selectedComponentIds = array_unique(array_merge($this->selectedComponentIds, $ids));
        }
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
        Cache::flush();
    }

    // --- Filters helpers ---
    public function applyFilters()
    {
        $this->resetPage();
        Cache::flush();
    }

    public function clearFilters()
    {
        $this->filters = [
            'q' => '',
            'firm_type' => '',
            'agency_id' => '',
            'status' => '',
        ];
        $this->resetPage();
        Cache::flush();
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

    // --- Role Modal Methods ---
    public function openRoleModal($userId)
    {
        $firmId = $this->selectedFirmForUsers?->id;
        $user = User::findOrFail($userId);
        $this->roleModalUserId = $userId;
        $this->roleModalUserName = $user->name;
        $this->roleModalAvailableRoles = \App\Models\Saas\Role::where('firm_id', $firmId)->pluck('name', 'id')->toArray();
        $this->roleModalSelectedRoles = \App\Models\Saas\RoleUser::where('user_id', $userId)->where('firm_id', $firmId)->pluck('role_id')->map(fn($id) => (string)$id)->toArray();
        $this->previousRoleModalSelectedRoles = $this->roleModalSelectedRoles;
        $this->showRoleModal = true;
        Flux::modal('user-role-modal')->show();
    }
    public function closeRoleModal()
    {
        $this->showRoleModal = false;
        $this->roleModalUserId = null;
        $this->roleModalUserName = '';
        $this->roleModalSelectedRoles = [];
        $this->roleModalAvailableRoles = [];
    }
    public function saveUserRoles()
    {
        $firmId = $this->selectedFirmForUsers?->id;
        $userId = $this->roleModalUserId;
        $selectedRoles = $this->roleModalSelectedRoles;
        $roleUserIds = [];
        foreach ($selectedRoles as $roleId) {
            $roleUser = \App\Models\Saas\RoleUser::firstOrCreate([
                'user_id' => $userId,
                'role_id' => $roleId,
                'firm_id' => $firmId,
            ]);
            $roleUserIds[] = $roleUser->id;
        }
        \App\Models\Saas\RoleUser::where('user_id', $userId)
            ->where('firm_id', $firmId)
            ->whereNotIn('role_id', $selectedRoles)
            ->delete();
        $this->syncUserActions($userId, $firmId);
        $this->closeRoleModal();
        Flux::toast(
            variant: 'success',
            heading: 'Roles updated.',
            text: 'Roles and permissions have been updated for the user.',
        );
        Flux::modal('user-role-modal')->close();
    }
    // --- Action Modal Methods ---
    public function openActionModal($userId)
    {
        $firmId = $this->selectedFirmForUsers?->id;
        $user = User::findOrFail($userId);
        $this->actionModalUserId = $userId;
        $this->actionModalUserName = $user->name;
        $ACTION_TYPE_BG = [
            'G' => 'bg-blue-50',
            'RL' => 'bg-yellow-50',
            'BR' => 'bg-green-50',
            'PR' => 'bg-pink-50',
            'INDEPENDENT' => 'bg-gray-100',
        ];
        $ACTION_TYPE_LABELS = \App\Models\Saas\Action::ACTION_TYPE_MAIN_SELECT;
        $ACTION_TYPE_LABELS['INDEPENDENT'] = 'Independent with no Type';
        $apps = \App\Models\Saas\App::with([
            'modules.components.actions.actioncluster' => function ($q) {
                $q->where('is_inactive', false);
            }
        ])->where('is_inactive', false)->orderBy('id', 'asc')->get();
        $assignedPanelIds = \App\Models\Saas\FirmPanel::where('firm_id', $firmId)->pluck('panel_id')->toArray();
        $assignedComponentPanels = \App\Models\Saas\ComponentPanel::where('firm_id', $firmId)
            ->select('component_id', 'panel_id')
            ->get()
            ->groupBy('component_id');
        $assignedComponentIds = $assignedComponentPanels->keys()->toArray();
        $grouped = [];
        $processedComponents = [];
        foreach ($apps as $app) {
            $appHasComponents = false;
            foreach ($app->modules as $module) {
                $moduleHasComponents = false;
                foreach ($module->components as $component) {
                    if (!in_array($component->id, $assignedComponentIds)) continue;
                    if (in_array($component->id, $processedComponents)) continue;
                    $processedComponents[] = $component->id;
                    $componentBelongsToAssignedPanel = false;
                    if (isset($assignedComponentPanels[$component->id])) {
                        $componentPanels = $assignedComponentPanels[$component->id]->pluck('panel_id')->toArray();
                        $componentBelongsToAssignedPanel = !empty(array_intersect($componentPanels, $assignedPanelIds));
                    }
                    if (!$componentBelongsToAssignedPanel) continue;
                    $grouped[$app->name][$module->name][$component->name] = [];
                    $moduleHasComponents = true;
                    $appHasComponents = true;
                    foreach ($ACTION_TYPE_LABELS as $typeKey => $typeLabel) {
                        if ($typeKey === 'INDEPENDENT') {
                            $actionsOfType = collect($component->actions)->filter(function($a){ return empty($a->action_type); });
                        } else {
                            $actionsOfType = collect($component->actions)->where('action_type', $typeKey);
                        }
                        if ($actionsOfType->isEmpty()) continue;
                        $actionsByCluster = $actionsOfType->groupBy(function($action) {
                            return $action->actioncluster ? $action->actioncluster->name : 'Independent';
                        });
                        $clusters = [];
                        foreach ($actionsByCluster as $clusterName => $actionsInCluster) {
                            $parentActions = $actionsInCluster->where('parent_action_id', null);
                            $childActions = $actionsInCluster->where('parent_action_id', '!=', null);
                            $clusterGroups = [];
                            foreach ($parentActions as $parent) {
                                $children = $childActions->where('parent_action_id', $parent->id)->values()->all();
                                $clusterGroups[] = [
                                    'parent' => [
                                        'id' => $parent->id,
                                        'name' => $parent->name,
                                        'code' => $parent->code,
                                        'type' => $typeLabel,
                                        'type_key' => $typeKey,
                                    ],
                                    'children' => collect($children)->map(function($child) use ($typeLabel, $typeKey) {
                                        return [
                                            'id' => $child->id,
                                            'name' => $child->name,
                                            'code' => $child->code,
                                            'type' => $typeLabel,
                                            'type_key' => $typeKey,
                                        ];
                                    })->values()->all(),
                                ];
                            }
                            $orphaned = $childActions->filter(function($child) use ($parentActions) {
                                return !$parentActions->contains('id', $child->parent_action_id);
                            });
                            foreach ($orphaned as $orphan) {
                                $clusterGroups[] = [
                                    'parent' => [
                                        'id' => $orphan->id,
                                        'name' => $orphan->name,
                                        'code' => $orphan->code,
                                        'type' => $typeLabel,
                                        'type_key' => $typeKey,
                                    ],
                                    'children' => [],
                                ];
                            }
                            $clusters[$clusterName] = $clusterGroups;
                        }
                        $grouped[$app->name][$module->name][$component->name][$typeKey] = [
                            'type_label' => $typeLabel,
                            'type_bg' => $ACTION_TYPE_BG[$typeKey] ?? 'bg-gray-50',
                            'clusters' => $clusters
                        ];
                    }
                }
                if (!$moduleHasComponents) unset($grouped[$app->name][$module->name]);
            }
            if (!$appHasComponents) unset($grouped[$app->name]);
        }
        $this->actionModalGroupedActions = $grouped;
        $this->actionModalAppList = array_keys($grouped);
        $this->actionModalSelectedApp = $this->actionModalAppList[0] ?? null;
        $existing = \App\Models\Saas\ActionUser::where('user_id', $userId)->where('firm_id', $firmId)->get();
        $this->actionModalSelectedActions = $existing->pluck('action_id')->map(fn($id) => (string)$id)->toArray();
        $this->previousActionModalSelectedActions = $this->actionModalSelectedActions;
        $this->actionModalActionScopes = $existing->pluck('records_scope', 'action_id')->toArray();
        $this->showActionModal = true;
        Flux::modal('user-action-modal')->show();
    }
    public function closeActionModal()
    {
        $this->showActionModal = false;
        $this->actionModalUserId = null;
        $this->actionModalUserName = '';
        $this->actionModalSelectedActions = [];
        $this->actionModalActionScopes = [];
        $this->actionModalGroupedActions = [];
        $this->actionModalAppList = [];
        $this->actionModalSelectedApp = null;
    }
    public function saveUserActions()
    {
        $firmId = $this->selectedFirmForUsers?->id;
        $userId = $this->actionModalUserId;
        $selectedActions = $this->actionModalSelectedActions;
        $scopes = $this->actionModalActionScopes;
        \App\Models\Saas\ActionUser::where('user_id', $userId)->where('firm_id', $firmId)
            ->whereNotIn('action_id', $selectedActions)->delete();
        foreach ($selectedActions as $actionId) {
            \App\Models\Saas\ActionUser::updateOrCreate(
                [
                    'user_id' => $userId,
                    'firm_id' => $firmId,
                    'action_id' => $actionId,
                ],
                [
                    'records_scope' => $scopes[$actionId] ?? 'all',
                ]
            );
        }
        $this->closeActionModal();
        Flux::toast(
            variant: 'success',
            heading: 'Actions updated.',
            text: 'Direct actions have been updated for the user.',
        );
        Flux::modal('user-action-modal')->close();
    }
    public function syncUser($userId)
    {
        $firmId = $this->selectedFirmForUsers?->id;
        $this->syncUserActions($userId, $firmId);
        Flux::toast(
            variant: 'success',
            heading: 'Permissions Synced.',
            text: 'User permissions have been synced with assigned roles.',
        );
    }
    public function syncUserActions($userId, $firmId = null)
    {
        $firmId = $firmId ?: $this->selectedFirmForUsers?->id;
        $roleIds = \App\Models\Saas\RoleUser::where('user_id', $userId)->where('firm_id', $firmId)->pluck('role_id')->toArray();
        $actionRoles = \App\Models\Saas\ActionRole::whereIn('role_id', $roleIds)->where('firm_id', $firmId)->get();
        $actionMap = [];
        foreach ($actionRoles as $ar) {
            $actionMap[$ar->action_id] = $ar->records_scope;
        }
        \App\Models\Saas\ActionUser::where('user_id', $userId)->where('firm_id', $firmId)
            ->whereNotIn('action_id', array_keys($actionMap))->delete();
        foreach ($actionMap as $actionId => $recordsScope) {
            \App\Models\Saas\ActionUser::updateOrCreate(
                [
                    'user_id' => $userId,
                    'firm_id' => $firmId,
                    'action_id' => $actionId,
                ],
                [
                    'records_scope' => $recordsScope,
                ]
            );
        }
    }

    // --- Additional methods for modals ---
    public function toggleRoleModalSelectedRole($roleId)
    {
        $roleId = (string) $roleId;
        if (in_array($roleId, $this->roleModalSelectedRoles)) {
            $this->roleModalSelectedRoles = array_values(array_diff($this->roleModalSelectedRoles, [$roleId]));
        } else {
            $this->roleModalSelectedRoles[] = $roleId;
        }
        $this->previousRoleModalSelectedRoles = $this->roleModalSelectedRoles;
    }

    public function toggleModule($appName, $moduleName)
    {
        $ids = collect($this->actionModalGroupedActions[$appName][$moduleName] ?? [])
            ->flatten(2)
            ->pluck('parent.id')
            ->merge(collect($this->actionModalGroupedActions[$appName][$moduleName] ?? [])->flatten(2)->pluck('children.*.id')->flatten())
            ->unique()
            ->values()
            ->all();
        $allSelected = !array_diff($ids, $this->actionModalSelectedActions);
        if ($allSelected) {
            $this->actionModalSelectedActions = array_values(array_diff($this->actionModalSelectedActions, $ids));
        } else {
            $this->actionModalSelectedActions = array_unique(array_merge($this->actionModalSelectedActions, $ids));
        }
    }

    public function toggleComponent($appName, $moduleName, $componentName)
    {
        $ids = collect($this->actionModalGroupedActions[$appName][$moduleName][$componentName] ?? [])
            ->flatten(2)
            ->pluck('parent.id')
            ->merge(collect($this->actionModalGroupedActions[$appName][$moduleName][$componentName] ?? [])->flatten(2)->pluck('children.*.id')->flatten())
            ->unique()
            ->values()
            ->all();
        $allSelected = !array_diff($ids, $this->actionModalSelectedActions);
        if ($allSelected) {
            $this->actionModalSelectedActions = array_values(array_diff($this->actionModalSelectedActions, $ids));
        } else {
            $this->actionModalSelectedActions = array_unique(array_merge($this->actionModalSelectedActions, $ids));
        }
    }

    public function toggleActionModalSelectedAction($actionId)
    {
        $actionId = (string) $actionId;
        if (in_array($actionId, $this->actionModalSelectedActions)) {
            $this->actionModalSelectedActions = array_values(array_diff($this->actionModalSelectedActions, [$actionId]));
        } else {
            $this->actionModalSelectedActions[] = $actionId;
        }
        $this->previousActionModalSelectedActions = $this->actionModalSelectedActions;
    }

    public function setActionScope($actionId, $scope)
    {
        $this->actionModalActionScopes[$actionId] = $scope;
    }

}
