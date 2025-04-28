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
    public $templateId;

    public $formData = [
        'id' => null,
        'firm_id' => null,
        'leaves_quota_template_id' => '',
        'leave_type_id' => '',
        'days_assigned' => 0,
        'alloc_period_unit' => '',
        'alloc_period_value' => null,
    ];

    public $filters = [
        'search_template' => '',
        'search_leave_type' => '',
    ];

    protected $rules = [
        'formData.leaves_quota_template_id' => 'required|exists:leaves_quota_templates,id',
        'formData.leave_type_id' => 'required|exists:leave_types,id',
        'formData.days_assigned' => 'required|integer|min:0',
        'formData.alloc_period_unit' => 'required|integer|min:1',
        'formData.alloc_period_value' => 'required|integer|min:1',
    ];

    public function mount($templateId = null)
    {
        $this->resetPage();
        $this->templateId = $templateId;
        if ($templateId) {
            $this->formData['leaves_quota_template_id'] = $templateId;
        }
    }

    public function resetForm()
    {
        $this->reset(['formData']);
        $this->formData['days_assigned'] = 0;
        $this->formData['alloc_period_value'] = null;
        if ($this->templateId) {
            $this->formData['leaves_quota_template_id'] = $this->templateId;
        }
        $this->isEditing = false;
    }

    public function store()
    {
        $validatedData = $this->validate();

        // Convert empty strings to null
        $validatedData['formData'] = collect($validatedData['formData'])
            ->map(fn($val) => $val === '' ? null : $val)
            ->toArray();

        // Add firm_id from session
        $validatedData['formData']['firm_id'] = session('firm_id');

        try {
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
        } catch (\Exception $e) {
            Flux::toast(
                variant: 'error',
                heading: 'Error',
                text: 'Failed to save setup: ' . $e->getMessage(),
            );
        }
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

    public function clearFilters()
    {
        $this->reset('filters');
        $this->resetPage();
    }

    #[Computed]
    public function list()
    {
        return LeavesQuotaTemplateSetup::query()
            ->with(['leaves_quota_template', 'leave_type'])
            ->when($this->filters['search_template'], function($query) {
                $query->where('leaves_quota_template_id', $this->filters['search_template']);
            })
            ->when($this->filters['search_leave_type'], function($query) {
                $query->where('leave_type_id', $this->filters['search_leave_type']);
            })
            ->when($this->templateId, function($query) {
                $query->where('leaves_quota_template_id', $this->templateId);
            })
            ->where('firm_id', Session::get('firm_id'))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);
    }

    #[Computed]
    public function templatesList()
    {
        return LeavesQuotaTemplate::where('firm_id', session('firm_id'))
            ->pluck('name', 'id');
    }

    #[Computed]
    public function leaveTypesList()
    {
        return LeaveType::where('firm_id', session('firm_id'))
            ->pluck('leave_title', 'id');
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Leave/blades/leaves-quota-templates-setups.blade.php'));
    }
} 