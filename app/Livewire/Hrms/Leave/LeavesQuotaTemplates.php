<?php

namespace App\Livewire\Hrms\Leave;

use App\Models\Hrms\LeavesQuotaTemplate;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Session;
use Flux;
use Livewire\Attributes\On;

class LeavesQuotaTemplates extends Component
{
    use WithPagination;

    public $perPage = 10;
    public $sortBy = 'name';
    public $sortDirection = 'asc';
    public $isEditing = false;
    public $statuses = [];
    public $showSetupsModal = false;
    public $selectedTemplateId = null;

    // Field configuration for form and table
    public array $fieldConfig = [
        'name' => ['label' => 'Template Name', 'type' => 'text'],
        'alloc_period_value' => ['label' => 'Allocation Value', 'type' => 'number'],
        'alloc_period_unit' => ['label' => 'Allocation Period', 'type' => 'select', 'listKey' => 'period_units'],
        'desc' => ['label' => 'Description', 'type' => 'textarea'],
        'quota_setups_count' => ['label' => 'Quota Setups', 'type' => 'badge'],
    ];

    // Filter fields configuration
    public array $filterFields = [
        'name' => ['label' => 'Template Name', 'type' => 'text'],
        'desc' => ['label' => 'Description', 'type' => 'text'],
        'alloc_period_unit' => ['label' => 'Allocation Period', 'type' => 'select', 'listKey' => 'period_units'],
        'is_inactive' => ['label' => 'Status', 'type' => 'select', 'listKey' => 'disabled_list'],
    ];

    public array $listsForFields = [];
    public array $filters = [];
    public array $visibleFields = [];
    public array $visibleFilterFields = [];

    public $formData = [
        'id' => null,
        'firm_id' => null,
        'name' => '',
        'desc' => '',
        'alloc_period_unit' => '',
        'alloc_period_value' => null,
        'is_inactive' => false,
    ];

    protected $rules = [
        'formData.name' => 'required|string|max:255',
        'formData.desc' => 'nullable|string',
        'formData.alloc_period_unit' => 'required|string',
        'formData.alloc_period_value' => 'required|numeric|min:1',
    ];

    public function mount()
    {
        $this->initListsForFields();

        // Set default visible fields
        $this->visibleFields = ['name', 'desc', 'alloc_period_unit', 'is_inactive', 'quota_setups_count'];
        $this->visibleFilterFields = ['name', 'desc', 'alloc_period_unit', 'is_inactive'];

        // Initialize filters
        $this->filters = array_fill_keys(array_keys($this->filterFields), '');

        $this->refreshStatuses();
    }

    protected function initListsForFields(): void
    {
        $this->listsForFields['disabled_list'] = [
            '0' => 'Enabled',
            '1' => 'Disabled'
        ];

        $this->listsForFields['period_units'] = LeavesQuotaTemplate::PERIOD_UNITS;
    }

    public function refreshStatuses()
    {
        $this->statuses = LeavesQuotaTemplate::where('firm_id', session('firm_id'))
            ->pluck('is_inactive', 'id')
            ->mapWithKeys(fn($val, $key) => [$key => (bool) $val])
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
        return LeavesQuotaTemplate::query()
            ->where('firm_id', Session::get('firm_id'))
            ->when($this->filters['name'], fn($query, $value) =>
                $query->where('name', 'like', "%{$value}%"))
            ->when($this->filters['desc'], fn($query, $value) =>
                $query->where('desc', 'like', "%{$value}%"))
            ->when($this->filters['alloc_period_unit'], fn($query, $value) =>
                $query->where('alloc_period_unit', $value))
            ->when($this->filters['is_inactive'] !== '', fn($query, $value) =>
                $query->where('is_inactive', $value))
            ->withCount('leaves_quota_template_setups as quota_setups_count')
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
            $template = LeavesQuotaTemplate::findOrFail($this->formData['id']);
            // Don't update is_inactive when editing
            unset($validatedData['formData']['is_inactive']);
            $template->update($validatedData['formData']);
            $toastMsg = 'Template updated successfully';
        } else {
            LeavesQuotaTemplate::create($validatedData['formData']);
            $toastMsg = 'Template added successfully';
        }

        $this->resetForm();
        $this->refreshStatuses();
        $this->modal('mdl-quota-template')->close();
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
        $template = LeavesQuotaTemplate::findOrFail($id);
        $this->formData = $template->toArray();
        $this->modal('mdl-quota-template')->show();
    }

    #[On('quota-setup-changed')]
    public function handleQuotaSetupChanged()
    {
        $this->dispatch('$refresh');
    }

    public function showTemplateSetups($id)
    {
        try {
            $template = LeavesQuotaTemplate::findOrFail($id);
            $this->selectedTemplateId = $template->id;
            $this->showSetupsModal = true;
        } catch (\Exception $e) {
            Flux::toast(
                variant: 'error',
                heading: 'Error',
                text: 'Failed to load template setups: ' . $e->getMessage(),
            );
        }
    }

    public function closeSetupsModal()
    {
        $this->showSetupsModal = false;
        $this->selectedTemplateId = null;
    }

    public function delete($id)
    {
        $template = LeavesQuotaTemplate::findOrFail($id);
        if ($template->leaves_quota_template_setups()->count() > 0) {
            Flux::toast(
                variant: 'error',
                heading: 'Cannot Delete',
                text: 'This template has related quota setups and cannot be deleted.',
            );
            return;
        }

        $template->delete();
        Flux::toast(
            variant: 'success',
            heading: 'Record Deleted.',
            text: 'Template has been deleted successfully',
        );
    }

    public function toggleStatus($id)
    {
        $template = LeavesQuotaTemplate::find($id);
        $template->is_inactive = !$template->is_inactive;
        $template->save();

        $this->statuses[$id] = $template->is_inactive;
        $this->refreshStatuses();
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Leave/blades/leaves-quota-templates.blade.php'));
    }
}