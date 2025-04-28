<?php

namespace App\Livewire\Hrms\Leave;

use App\Models\Hrms\LeavesQuotaTemplate;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Session;
use Flux;

class LeavesQuotaTemplates extends Component
{
    use WithPagination;

    public $perPage = 10;
    public $sortBy = 'name';
    public $sortDirection = 'asc';
    public $isEditing = false;
    public $statuses;
    public $formData = [
        'id' => null,
        'firm_id' => null,
        'name' => '',
        'desc' => '',
        'is_inactive' => false,
    ];

    public $filters = [
        'search_name' => '',
        'search_desc' => '',
        'search_status' => '',
    ];

    protected $rules = [
        'formData.name' => 'required|string|max:255',
        'formData.desc' => 'nullable|string',
        'formData.is_inactive' => 'boolean',
    ];

    public function mount()
    {
        $this->resetPage();
        $this->refreshStatuses();
    }

    public function refreshStatuses()
    {
        $this->statuses = LeavesQuotaTemplate::where('firm_id', session('firm_id'))
            ->pluck('is_inactive', 'id')
            ->mapWithKeys(fn($val, $key) => [$key => (bool)$val])
            ->toArray();
    }

    public function resetForm()
    {
        $this->reset(['formData']);
        $this->formData['is_inactive'] = false;
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

        if ($this->isEditing) {
            $template = LeavesQuotaTemplate::findOrFail($this->formData['id']);
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

    public function edit($id)
    {
        $this->isEditing = true;
        $template = LeavesQuotaTemplate::findOrFail($id);
        $this->formData = $template->toArray();
        $this->modal('mdl-quota-template')->show();
    }

    public function delete($id)
    {
        // Check if template has related allocations
        $template = LeavesQuotaTemplate::findOrFail($id);
        if ($template->emp_leave_allocations()->count() > 0) {
            Flux::toast(
                variant: 'error',
                heading: 'Cannot Delete',
                text: 'This template has related allocations and cannot be deleted.',
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

    public function clearFilters()
    {
        $this->reset('filters');
        $this->resetPage();
    }

    #[Computed]
    public function list()
    {
        return LeavesQuotaTemplate::query()
            ->where('firm_id', Session::get('firm_id'))
            ->when($this->filters['search_name'], function($query) {
                $query->where('name', 'like', '%' . $this->filters['search_name'] . '%');
            })
            ->when($this->filters['search_desc'], function($query) {
                $query->where('desc', 'like', '%' . $this->filters['search_desc'] . '%');
            })
            ->when($this->filters['search_status'] !== '', function($query) {
                $query->where('is_inactive', $this->filters['search_status']);
            })
            ->withCount('emp_leave_allocations')
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Leave/blades/leaves-quota-templates.blade.php'));
    }
} 