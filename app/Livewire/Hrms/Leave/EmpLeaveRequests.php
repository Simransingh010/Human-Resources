<?php

namespace App\Livewire\Hrms\Leave;

use App\Models\Hrms\EmpLeaveRequest;
use App\Models\Hrms\Employee;
use App\Models\Hrms\LeaveType;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Session;
use Flux;

class EmpLeaveRequests extends Component
{
    use WithPagination;

    public $perPage = 10;
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $isEditing = false;

    // Field configuration for form and table
    public array $fieldConfig = [
        'employee_id' => ['label' => 'Employee', 'type' => 'select', 'listKey' => 'employees'],
        'leave_type_id' => ['label' => 'Leave Type', 'type' => 'select', 'listKey' => 'leave_types'],
        'apply_from' => ['label' => 'Apply From', 'type' => 'date'],
        'apply_to' => ['label' => 'Apply To', 'type' => 'date'],
        'apply_days' => ['label' => 'Apply Days', 'type' => 'number'],
        'reason' => ['label' => 'Reason', 'type' => 'text'],
        'status' => ['label' => 'Status', 'type' => 'select', 'listKey' => 'statuses'],

    ];

    // Filter fields configuration
    public array $filterFields = [
        'employee_id' => ['label' => 'Employee', 'type' => 'select', 'listKey' => 'employees'],
        'leave_type_id' => ['label' => 'Leave Type', 'type' => 'select', 'listKey' => 'leave_types'],
        'apply_from' => ['label' => 'Apply From', 'type' => 'date'],
        'apply_to' => ['label' => 'Apply To', 'type' => 'date'],
        'status' => ['label' => 'Status', 'type' => 'select', 'listKey' => 'statuses'],
        'created_at' => ['label' => 'Created At', 'type' => 'date'],
        'updated_at' => ['label' => 'Updated At', 'type' => 'date'],
    ];

    public array $listsForFields = [];
    public array $filters = [];
    public array $visibleFields = [];
    public array $visibleFilterFields = [];

    public $formData = [
        'id' => null,
        'firm_id' => null,
        'employee_id' => null,
        'leave_type_id' => null,
        'apply_from' => '',
        'apply_to' => '',
        'apply_days' => 0,
        'reason' => '',
        'status' => '',
    ];

    protected function rules()
    {
        return [
            'formData.employee_id' => 'required|integer|exists:employees,id',
            'formData.leave_type_id' => 'required|integer|exists:leave_types,id',
            'formData.apply_from' => 'required|date',
            'formData.apply_to' => 'required|date|after:formData.apply_from',
            'formData.apply_days' => 'required|numeric|min:1',
            'formData.reason' => 'nullable|string',
            'formData.status' => 'required|string',
        ];
    }

    public function mount()
    {
        $this->initListsForFields();
        
        // Set default visible fields - excluding created_at and updated_at
        $this->visibleFields = ['employee_id', 'leave_type_id', 'apply_from', 'apply_to', 'apply_days', 'status', 'reason'];
        $this->visibleFilterFields = ['employee_id', 'leave_type_id', 'apply_from', 'apply_to', 'status'];
        
        // Initialize filters
        $this->filters = array_fill_keys(array_keys($this->filterFields), '');
    }

    protected function initListsForFields(): void
    {
        $this->listsForFields['employees'] = Employee::where('firm_id', session('firm_id'))
            ->pluck('fname', 'id');
        $this->listsForFields['leave_types'] = LeaveType::where('firm_id', session('firm_id'))
            ->pluck('leave_title', 'id');
        $this->listsForFields['statuses'] = EmpLeaveRequest::STATUS_SELECT;
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
        return EmpLeaveRequest::query()
            ->with(['employee', 'leave_type'])
            ->where('firm_id', Session::get('firm_id'))
            ->when($this->filters['employee_id'], fn($query, $value) => 
                $query->where('employee_id', $value))
            ->when($this->filters['leave_type_id'], fn($query, $value) => 
                $query->where('leave_type_id', $value))
            ->when($this->filters['apply_from'], fn($query, $value) => 
                $query->where('apply_from', '>=', $value))
            ->when($this->filters['apply_to'], fn($query, $value) => 
                $query->where('apply_to', '<=', $value))
            ->when($this->filters['status'], fn($query, $value) => 
                $query->where('status', $value))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);
    }

    public function store()
    {
        $validatedData = $this->validate($this->rules());

        $validatedData['formData'] = collect($validatedData['formData'])
            ->map(fn($val) => $val === '' ? null : $val)
            ->toArray();

        $validatedData['formData']['firm_id'] = session('firm_id');

        if ($this->isEditing) {
            $leaveRequest = EmpLeaveRequest::findOrFail($this->formData['id']);
            $leaveRequest->update($validatedData['formData']);
            $toastMsg = 'Leave request updated successfully';
        } else {
            EmpLeaveRequest::create($validatedData['formData']);
            $toastMsg = 'Leave request added successfully';
        }

        $this->resetForm();
        $this->modal('mdl-leave-request')->close();
        Flux::toast(
            variant: 'success',
            heading: 'Changes saved.',
            text: $toastMsg,
        );
    }

    public function resetForm()
    {
        $this->reset(['formData']);
        $this->formData['apply_days'] = 0;
        $this->isEditing = false;
    }

    public function edit($id)
    {
        $this->isEditing = true;
        $leaveRequest = EmpLeaveRequest::findOrFail($id);
        $this->formData = $leaveRequest->toArray();
        $this->formData['apply_from'] = $leaveRequest->apply_from ? $leaveRequest->apply_from->format('Y-m-d') : '';
        $this->formData['apply_to'] = $leaveRequest->apply_to ? $leaveRequest->apply_to->format('Y-m-d') : '';
        $this->modal('mdl-leave-request')->show();
    }

    public function delete($id)
    {
        // Check if leave request has related records
        $leaveRequest = EmpLeaveRequest::findOrFail($id);
        if ($leaveRequest->emp_leave_request_approvals()->count() > 0 || 
            $leaveRequest->leave_request_events()->count() > 0) {
            Flux::toast(
                variant: 'error',
                heading: 'Cannot Delete',
                text: 'This leave request has related records and cannot be deleted.',
            );
            return;
        }

        $leaveRequest->delete();
        Flux::toast(
            variant: 'success',
            heading: 'Record Deleted.',
            text: 'Leave request has been deleted successfully',
        );
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Leave/blades/emp-leave-requests.blade.php'));
    }
} 