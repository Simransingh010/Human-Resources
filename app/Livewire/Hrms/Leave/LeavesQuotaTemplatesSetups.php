<?php

namespace App\Livewire\Hrms\Leave;

use App\Models\Hrms\LeavesQuotaTemplateSetup;
use App\Models\Hrms\LeavesQuotaTemplate;
use App\Models\Hrms\LeaveType;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Session;
use Flux;

class LeavesQuotaTemplatesSetups extends Component
{
    use WithPagination;

    public $perPage = 10;
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $isEditing = false;
    public $statuses = [];
    public $templateId = null;
    public $availableLeaveTypes = [];
    public $usedLeaveTypes = [];

    // Field configuration for form and table
    public array $fieldConfig = [
        'leaves_quota_template_id' => ['label' => 'Template', 'type' => 'select', 'listKey' => 'templates_list'],
        'leave_type_id' => ['label' => 'Leave Type', 'type' => 'select', 'listKey' => 'leave_types_list'],
        'days_assigned' => ['label' => 'Days Assigned', 'type' => 'number'],
        'is_inactive' => ['label' => 'Disabled', 'type' => 'switch'],
    ];

    // Filter fields configuration
    public array $filterFields = [
        'leaves_quota_template_id' => ['label' => 'Template', 'type' => 'select', 'listKey' => 'templates_list'],
        'leave_type_id' => ['label' => 'Leave Type', 'type' => 'select', 'listKey' => 'leave_types_list'],
        'days_assigned' => ['label' => 'Days Assigned', 'type' => 'number'],
        'is_inactive' => ['label' => 'Status', 'type' => 'select', 'listKey' => 'disabled_list'],
    ];

    public array $listsForFields = [];
    public array $filters = [];
    public array $visibleFields = [];
    public array $visibleFilterFields = [];

    public $formData = [
        'id' => null,
        'firm_id' => null,
        'leaves_quota_template_id' => '',
        'leave_type_id' => '',
        'days_assigned' => 0,
        'is_inactive' => false,
    ];

    protected $rules = [
        'formData.leaves_quota_template_id' => 'required|exists:leaves_quota_templates,id',
        'formData.leave_type_id' => 'required|exists:leave_types,id',
        'formData.days_assigned' => 'required|integer|min:0',
        'formData.is_inactive' => 'boolean',
    ];

    public function mount($templateId = null)
    {
        $this->templateId = $templateId;

        $this->initListsForFields();

        // Set default visible fields
        $this->visibleFields = ['leaves_quota_template_id', 'leave_type_id', 'days_assigned', 'is_inactive'];
        $this->visibleFilterFields = ['leaves_quota_template_id', 'leave_type_id', 'days_assigned'];

        // Initialize filters
        $this->filters = array_fill_keys(array_keys($this->filterFields), '');

        // If templateId is provided, set it in filters and form data
        if ($this->templateId) {
            $this->filters['leaves_quota_template_id'] = $this->templateId;
            $this->formData['leaves_quota_template_id'] = $this->templateId;
            $this->updateAvailableLeaveTypes();
        }

        // Initialize statuses
        $this->refreshStatuses();
    }

    protected function initListsForFields(): void
    {
        $this->listsForFields['templates_list'] = LeavesQuotaTemplate::where('firm_id', session('firm_id'))
            ->pluck('name', 'id');

        $this->listsForFields['leave_types_list'] = LeaveType::where('firm_id', session('firm_id'))
            ->pluck('leave_title', 'id');

        $this->listsForFields['disabled_list'] = [
            '0' => 'Enabled',
            '1' => 'Disabled'
        ];

        if ($this->templateId) {
            $this->updateAvailableLeaveTypes();
        }
    }

    public function updateAvailableLeaveTypes()
    {
        // Get all leave types
        $allLeaveTypes = LeaveType::where('firm_id', session('firm_id'))
            ->pluck('leave_title', 'id')
            ->toArray();

        // Get used leave types for the current template
        $this->usedLeaveTypes = LeavesQuotaTemplateSetup::where('leaves_quota_template_id', $this->templateId)
            ->where('firm_id', session('firm_id'))
            ->when($this->isEditing && $this->formData['id'], function ($query) {
                // If editing, exclude the current setup's leave type from used types
                return $query->where('id', '!=', $this->formData['id']);
            })
            ->pluck('leave_type_id')
            ->toArray();

        // Filter out used leave types
        $this->availableLeaveTypes = collect($allLeaveTypes)
            ->reject(function ($title, $id) {
                return in_array($id, $this->usedLeaveTypes);
            })
            ->toArray();

        // Update the leave_types_list to show only available types
        $this->listsForFields['leave_types_list'] = $this->availableLeaveTypes;

        // If the currently selected leave type is not available, reset it
        if ($this->formData['leave_type_id'] && !isset($this->availableLeaveTypes[$this->formData['leave_type_id']])) {
            $this->formData['leave_type_id'] = '';
        }
    }

    public function updated($field)
    {
        // When template ID changes, update available leave types
        if ($field === 'formData.leaves_quota_template_id') {
            $this->updateAvailableLeaveTypes();
        }
    }

    public function applyFilters()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->filters = array_fill_keys(array_keys($this->filterFields), '');

        // If templateId is provided, make sure it stays in the filter
        if ($this->templateId) {
            $this->filters['leaves_quota_template_id'] = $this->templateId;
        }

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
        return LeavesQuotaTemplateSetup::query()
            ->with(['leaves_quota_template', 'leave_type'])
            ->where('firm_id', Session::get('firm_id'))
            ->when($this->templateId, fn($query) =>
                $query->where('leaves_quota_template_id', $this->templateId))
            ->when($this->filters['leaves_quota_template_id'] && !$this->templateId, fn($query, $value) =>
                $query->where('leaves_quota_template_id', $value))
            ->when($this->filters['leave_type_id'], fn($query, $value) =>
                $query->where('leave_type_id', $value))
            ->when($this->filters['days_assigned'], fn($query, $value) =>
                $query->where('days_assigned', $value))
            ->when($this->filters['is_inactive'] !== '', fn($query, $value) =>
                $query->where('is_inactive', $value))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);
    }

    public function store()
    {
        $validatedData = $this->validate();

        $validatedData['formData'] = collect($validatedData['formData'])
            ->map(fn($val) => $val === '' ? null : $val)
            ->toArray();

        $validatedData['formData']['firm_id'] = session('firm_id');
        $validatedData['formData']['is_inactive'] = false; // Always set as active when creating/updating

        if ($this->isEditing) {
            $setup = LeavesQuotaTemplateSetup::findOrFail($this->formData['id']);
            // Don't update is_inactive when editing
            unset($validatedData['formData']['is_inactive']);
            $setup->update($validatedData['formData']);
            $toastMsg = 'Setup updated successfully';
        } else {
            LeavesQuotaTemplateSetup::create($validatedData['formData']);
            $toastMsg = 'Setup added successfully';
        }

        $this->resetForm();
        $this->refreshStatuses();
        $this->modal('mdl-quota-setup')->close();
        $this->dispatch('quota-setup-changed');
        Flux::toast(
            variant: 'success',
            heading: 'Changes saved.',
            text: $toastMsg,
        );
    }

    public function resetForm()
    {
        $this->reset(['formData']);
        $this->formData['days_assigned'] = 0;
        $this->formData['is_inactive'] = false;

        // If templateId is provided, set it in the form data
        if ($this->templateId) {
            $this->formData['leaves_quota_template_id'] = $this->templateId;
        }

        $this->isEditing = false;

        // Update available leave types after resetting the form
        $this->updateAvailableLeaveTypes();
    }

    public function edit($id)
    {
        $this->isEditing = true;
        $setup = LeavesQuotaTemplateSetup::findOrFail($id);
        $this->formData = $setup->toArray();

        // Update available leave types before showing the modal
        $this->updateAvailableLeaveTypes();

        $this->modal('mdl-quota-setup')->show();
    }

    public function delete($id)
    {
        try {
            $setup = LeavesQuotaTemplateSetup::findOrFail($id);
            $setup->delete();

            $this->updateAvailableLeaveTypes();

            // Dispatch event to update parent component with browser event
            $this->dispatch('quota-setup-changed')->to('hrms.leave.leaves-quota-templates');

            Flux::toast(
                variant: 'success',
                heading: 'Record Deleted.',
                text: 'Setup has been deleted successfully',
            );
        } catch (\Exception $e) {
            Flux::toast(
                variant: 'error',
                heading: 'Error',
                text: 'Failed to delete setup: ' . $e->getMessage(),
            );
        }
    }

    public function refreshStatuses()
    {
        $query = LeavesQuotaTemplateSetup::where('firm_id', session('firm_id'));
        if ($this->templateId) {
            $query->where('leaves_quota_template_id', $this->templateId);
        }

        $this->statuses = $query->pluck('is_inactive', 'id')
            ->mapWithKeys(fn($val, $key) => [$key => (bool) $val])
            ->toArray();
    }

    public function toggleStatus($id)
    {
        $setup = LeavesQuotaTemplateSetup::find($id);
        $setup->is_inactive = !$setup->is_inactive;
        $setup->save();

        $this->statuses[$id] = $setup->is_inactive;
        $this->refreshStatuses();
        $this->dispatch('quota-setup-changed');
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Leave/blades/leaves-quota-templates-setups.blade.php'));
    }
}