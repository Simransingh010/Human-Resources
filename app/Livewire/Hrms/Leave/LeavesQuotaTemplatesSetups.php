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

    // Field configuration for form and table
    public array $fieldConfig = [
        'leaves_quota_template_id' => ['label' => 'Template', 'type' => 'select', 'listKey' => 'templates_list'],
        'leave_type_id' => ['label' => 'Leave Type', 'type' => 'select', 'listKey' => 'leave_types_list'],
        'days_assigned' => ['label' => 'Days Assigned', 'type' => 'number'],
        'alloc_period_unit' => ['label' => 'Allocation Period Unit', 'type' => 'select', 'listKey' => 'period_units_list'],
        'alloc_period_value' => ['label' => 'Allocation Period Value', 'type' => 'number'],
        'is_inactive' => ['label' => 'Status', 'type' => 'switch'],
    ];

    // Filter fields configuration
    public array $filterFields = [
        'leaves_quota_template_id' => ['label' => 'Template', 'type' => 'select', 'listKey' => 'templates_list'],
        'leave_type_id' => ['label' => 'Leave Type', 'type' => 'select', 'listKey' => 'leave_types_list'],
        'days_assigned' => ['label' => 'Days Assigned', 'type' => 'number'],
        'alloc_period_unit' => ['label' => 'Allocation Period Unit', 'type' => 'select', 'listKey' => 'period_units_list'],
        'is_inactive' => ['label' => 'Status', 'type' => 'select', 'listKey' => 'status_list'],
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
        'alloc_period_unit' => '',
        'alloc_period_value' => null,
        'is_inactive' => false,
    ];

    protected $rules = [
        'formData.leaves_quota_template_id' => 'required|exists:leaves_quota_templates,id',
        'formData.leave_type_id' => 'required|exists:leave_types,id',
        'formData.days_assigned' => 'required|integer|min:0',
        'formData.alloc_period_unit' => 'required|string',
        'formData.alloc_period_value' => 'required|integer|min:1',
        'formData.is_inactive' => 'boolean',
    ];

    public function mount()
    {
        $this->initListsForFields();
        
        // Set default visible fields
        $this->visibleFields = ['leaves_quota_template_id', 'leave_type_id', 'days_assigned', 'alloc_period_unit', 'alloc_period_value', 'is_inactive'];
        $this->visibleFilterFields = ['leaves_quota_template_id', 'leave_type_id', 'days_assigned', 'is_inactive'];
        
        // Initialize filters
        $this->filters = array_fill_keys(array_keys($this->filterFields), '');

        // Initialize statuses
        $this->refreshStatuses();
    }

    protected function initListsForFields(): void
    {
        $this->listsForFields['templates_list'] = LeavesQuotaTemplate::where('firm_id', session('firm_id'))
            ->pluck('name', 'id');

        $this->listsForFields['leave_types_list'] = LeaveType::where('firm_id', session('firm_id'))
            ->pluck('leave_title', 'id');

        $this->listsForFields['period_units_list'] = LeavesQuotaTemplateSetup::ALLOC_PERIOD_UNITS;

        $this->listsForFields['status_list'] = [
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
        return LeavesQuotaTemplateSetup::query()
            ->with(['leaves_quota_template', 'leave_type'])
            ->where('firm_id', Session::get('firm_id'))
            ->when($this->filters['leaves_quota_template_id'], fn($query, $value) => 
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

        if ($this->isEditing) {
            $setup = LeavesQuotaTemplateSetup::findOrFail($this->formData['id']);
            $setup->update($validatedData['formData']);
            $toastMsg = 'Setup updated successfully';
        } else {
            LeavesQuotaTemplateSetup::create($validatedData['formData']);
            $toastMsg = 'Setup added successfully';
        }

        $this->resetForm();
        $this->modal('mdl-quota-setup')->close();
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
        $this->formData['alloc_period_value'] = null;
        $this->formData['is_inactive'] = false;
        $this->isEditing = false;
    }

    public function edit($id)
    {
        $this->isEditing = true;
        $setup = LeavesQuotaTemplateSetup::findOrFail($id);
        $this->formData = $setup->toArray();
        $this->modal('mdl-quota-setup')->show();
    }

    public function delete($id)
    {
        try {
            $setup = LeavesQuotaTemplateSetup::findOrFail($id);
            $setup->delete();

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
        $this->statuses = LeavesQuotaTemplateSetup::where('firm_id', session('firm_id'))
            ->pluck('is_inactive', 'id')
            ->mapWithKeys(fn($val, $key) => [$key => !(bool)$val])
            ->toArray();
    }

    public function toggleStatus($id)
    {
        $setup = LeavesQuotaTemplateSetup::find($id);
        $setup->is_inactive = !$setup->is_inactive;
        $setup->save();

        $this->statuses[$id] = !$setup->is_inactive;
        $this->refreshStatuses();
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Leave/blades/leaves-quota-templates-setups.blade.php'));
    }
} 