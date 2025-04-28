<?php

namespace App\Livewire\Hrms\Leave;

use App\Models\Hrms\LeaveType;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Session;
use Flux;

class LeaveTypes extends Component
{
    use WithPagination;

    public $perPage = 10;
    public $sortBy = 'leave_title';
    public $sortDirection = 'asc';
    public $isEditing = false;
    public array $listsForFields = [];

    public $formData = [
        'id' => null,
        'firm_id' => null,
        'leave_title' => '',
        'leave_desc' => '',
        'leave_code' => '',
        'leave_nature' => '',
        'max_days' => null,
        'carry_forward' => false,
        'encashable' => false,
    ];

    public $filters = [
        'search_title' => '',
        'search_code' => '',
        'search_nature' => '',
    ];

    protected $rules = [
        'formData.leave_title' => 'required|string|max:255',
        'formData.leave_desc' => 'nullable|string',
        'formData.leave_code' => 'nullable|string|max:50',
        'formData.leave_nature' => 'required|in:paid,unpaid',
        'formData.max_days' => 'required|integer|min:0',
        'formData.carry_forward' => 'boolean',
        'formData.encashable' => 'boolean',
    ];

    public function mount()
    {
        $this->resetPage();
        $this->initListsForFields();
    }

    protected function initListsForFields(): void
    {
        $this->listsForFields['leave_nature'] = LeaveType::LEAVE_NATURE_SELECT;
    }

    public function resetForm()
    {
        $this->reset(['formData']);
        $this->formData['carry_forward'] = false;
        $this->formData['encashable'] = false;
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
            $leaveType = LeaveType::findOrFail($this->formData['id']);
            $leaveType->update($validatedData['formData']);
            $toastMsg = 'Leave type updated successfully';
        } else {
            LeaveType::create($validatedData['formData']);
            $toastMsg = 'Leave type added successfully';
        }

        $this->resetForm();
        $this->modal('mdl-leave-type')->close();
        Flux::toast(
            variant: 'success',
            heading: 'Changes saved.',
            text: $toastMsg,
        );
    }

    public function edit($id)
    {
        $this->isEditing = true;
        $leaveType = LeaveType::findOrFail($id);
        $this->formData = $leaveType->toArray();
        $this->modal('mdl-leave-type')->show();
    }

    public function delete($id)
    {
        // Check if leave type has related records
        $leaveType = LeaveType::findOrFail($id);
        if ($leaveType->leave_allocations()->count() > 0) {
            Flux::toast(
                variant: 'error',
                heading: 'Cannot Delete',
                text: 'This leave type has related records and cannot be deleted.',
            );
            return;
        }

        $leaveType->delete();
        Flux::toast(
            variant: 'success',
            heading: 'Record Deleted.',
            text: 'Leave type has been deleted successfully',
        );
    }

    public function clearFilters()
    {
        $this->reset('filters');
        $this->resetPage();
    }

    #[Computed]
    public function list()
    {
        return LeaveType::query()
            ->where('firm_id', Session::get('firm_id'))
            ->when($this->filters['search_title'], function($query) {
                $query->where('leave_title', 'like', '%' . $this->filters['search_title'] . '%');
            })
            ->when($this->filters['search_code'], function($query) {
                $query->where('leave_code', 'like', '%' . $this->filters['search_code'] . '%');
            })
            ->when($this->filters['search_nature'], function($query) {
                $query->where('leave_nature', $this->filters['search_nature']);
            })
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Leave/blades/leave-types.blade.php'));
    }
} 