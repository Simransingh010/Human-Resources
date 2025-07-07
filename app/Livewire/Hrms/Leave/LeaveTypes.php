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

    // Field configuration for form and table
    public array $fieldConfig = [
        'leave_title' => ['label' => 'Leave Title', 'type' => 'text'],
        'leave_code' => ['label' => 'Leave Code', 'type' => 'text'],
        'leave_nature' => ['label' => 'Leave Nature', 'type' => 'select', 'listKey' => 'leave_nature'],
        'leave_type_main' => ['label' => 'Leave Type Main', 'type' => 'select', 'listKey' => 'leave_type_main'],
        'max_days' => ['label' => 'Maximum Days', 'type' => 'number'],
        'carry_forward' => ['label' => 'Carry Forward', 'type' => 'switch'],
        'encashable' => ['label' => 'Encashable', 'type' => 'switch'],
        'leave_desc' => ['label' => 'Description', 'type' => 'textarea'],
    ];

    // Filter fields configuration
    public array $filterFields = [
        'leave_title' => ['label' => 'Leave Title', 'type' => 'text'],
        'leave_code' => ['label' => 'Leave Code', 'type' => 'text'],
        'leave_nature' => ['label' => 'Leave Nature', 'type' => 'select', 'listKey' => 'leave_nature'],
        'leave_type_main' => ['label' => 'Leave Type Main', 'type' => 'select', 'listKey' => 'leave_type_main'],
        'max_days' => ['label' => 'Maximum Days', 'type' => 'number'],
    ];

    public array $listsForFields = [];
    public array $filters = [];
    public array $visibleFields = [];
    public array $visibleFilterFields = [];

    public $formData = [
        'id' => null,
        'firm_id' => null,
        'leave_title' => '',
        'leave_desc' => '',
        'leave_code' => '',
        'leave_nature' => '',
        'leave_type_main' => '',
        'max_days' => null,
        'carry_forward' => false,
        'encashable' => false,
    ];

    public function mount()
    {
        $this->resetPage();
        $this->listsForFields['leave_nature'] = \App\Models\Hrms\LeaveType::LEAVE_NATURE_OPTIONS;
        $this->listsForFields['leave_type_main'] = \App\Models\Hrms\LeaveType::LEAVE_TYPE_MAIN_OPTIONS;
        
        // Set default visible fields
        $this->visibleFields = ['leave_title', 'leave_code', 'leave_nature', 'leave_type_main', 'max_days'];
        $this->visibleFilterFields = ['leave_title', 'leave_code', 'leave_nature', 'leave_type_main'];
        
        // Initialize filters
        $this->filters = array_fill_keys(array_keys($this->filterFields), '');
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
        return LeaveType::query()
            ->where('firm_id', Session::get('firm_id'))


            ->when($this->filters['leave_title'], fn($query, $value) => 
                $query->where('leave_title', 'like', "%{$value}%"))
            ->when($this->filters['leave_code'], fn($query, $value) => 
                $query->where('leave_code', 'like', "%{$value}%"))
            ->when($this->filters['leave_nature'], fn($query, $value) => 
                $query->where('leave_nature', $value))
            ->when($this->filters['leave_type_main'], fn($query, $value) => 
                $query->where('leave_type_main', $value))
            ->when($this->filters['max_days'], fn($query, $value) => 
                $query->where('max_days', $value))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);


    }

    protected function rules()
    {
        return [
            'formData.leave_title' => 'required|string|max:255',
            'formData.leave_desc' => 'nullable|string',
            'formData.leave_code' => 'nullable|string',
            'formData.leave_nature' => 'nullable|string',
            'formData.leave_type_main' => 'nullable|string',
            'formData.max_days' => 'nullable|integer',
            'formData.carry_forward' => 'boolean',
            'formData.encashable' => 'boolean',
            'formData.is_inactive' => 'boolean'
        ];
    }

    public function store()
    {
        $validatedData = $this->validate();


        $validatedData['formData'] = collect($validatedData['formData'])
            ->map(fn($val) => $val === '' ? null : $val)
            ->toArray();

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

    public function resetForm()
    {
        $this->reset(['formData']);
        $this->formData['carry_forward'] = false;
        $this->formData['encashable'] = false;
        $this->isEditing = false;
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

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Leave/blades/leave-types.blade.php'));
    }
} 