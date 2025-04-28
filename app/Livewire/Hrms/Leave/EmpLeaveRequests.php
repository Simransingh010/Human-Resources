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
    public $selectedId = null;
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

    public $filters = [
        'search' => '',
        'employees' => [],
        'leave_types' => [],
        'status' => ''
    ];

    public $listsForFields = [];

    protected function rules()
    {
        return [
            'formData.employee_id' => 'required|integer',
            'formData.leave_type_id' => 'required|integer',
            'formData.apply_from' => 'required|date',
            'formData.apply_to' => 'required|date|after:formData.apply_from',
            'formData.apply_days' => 'required|integer|min:1',
            'formData.reason' => 'nullable|string',
            'formData.status' => 'required|string',
        ];
    }

    public function mount()
    {
        $this->initListsForFields();
        $this->resetPage();
    }

    protected function initListsForFields(): void
    {
        $this->listsForFields['employees'] = Employee::where('firm_id', session('firm_id'))
            ->pluck('fname', 'id');
        $this->listsForFields['leave_types'] = LeaveType::where('firm_id', session('firm_id'))
            ->pluck('leave_title', 'id');
        $this->listsForFields['statuses'] = EmpLeaveRequest::STATUS_SELECT;
    }

    public function resetForm()
    {
        $this->reset(['formData']);
        $this->formData['apply_days'] = 0;
        $this->formData['employee_id'] = null;
        $this->formData['leave_type_id'] = null;
        $this->formData['apply_from'] = '';
        $this->formData['apply_to'] = '';
        $this->formData['reason'] = '';
        $this->formData['status'] = '';
        $this->isEditing = false;
    }

    public function store()
    {
        $validatedData = $this->validate($this->rules());

        if ($this->isEditing) {
            $request = EmpLeaveRequest::findOrFail($this->formData['id']);
            $request->update($validatedData['formData']);
            session()->flash('message', 'Leave request updated successfully.');
        } else {
            $validatedData['formData']['firm_id'] = session('firm_id');
            EmpLeaveRequest::create($validatedData['formData']);
            session()->flash('message', 'Leave request added successfully.');
        }

        $this->resetForm();
        $this->modal('mdl-leave-request')->close();
        Flux::toast(
            heading: 'Changes saved.',
            text: 'Leave requests have been updated.',
        );
    }

    public function edit($id)
    {
        $this->isEditing = true;
        $request = EmpLeaveRequest::findOrFail($id);
        $this->formData = $request->toArray();
        $this->formData['employee_id'] = $request->employee_id;
        $this->formData['leave_type_id'] = $request->leave_type_id;
        $this->formData['apply_from'] = $request->apply_from ? $request->apply_from->format('Y-m-d') : '';
        $this->formData['apply_to'] = $request->apply_to ? $request->apply_to->format('Y-m-d') : '';
        $this->formData['status'] = $request->status;
        $this->modal('mdl-leave-request')->show();
    }

    public function delete($id)
    {
        try {
            $request = EmpLeaveRequest::findOrFail($id);
            $request->delete();

            Flux::toast(
                variant: 'success',
                heading: 'Record Deleted.',
                text: 'Leave request has been deleted successfully',
            );
        } catch (\Exception $e) {
            Flux::toast(
                variant: 'error',
                heading: 'Error',
                text: 'Failed to delete leave request: ' . $e->getMessage(),
            );
        }
    }

    public function clearFilters()
    {
        $this->reset('filters');
        $this->resetPage();
    }

    public function applyFilters()
    {
        $this->resetPage();
    }

    #[Computed]
    public function list()
    {
        return EmpLeaveRequest::query()
            ->with(['employee', 'leave_type'])
            ->when($this->filters['search'], function($query) {
                $query->where(function($q) {
                    $search = '%' . $this->filters['search'] . '%';
                    $q->whereHas('employee', function($q) use ($search) {
                        $q->where('fname', 'like', $search)
                            ->orWhere('lname', 'like', $search);
                    })
                    ->orWhereHas('leave_type', function($q) use ($search) {
                        $q->where('leave_title', 'like', $search);
                    });
                });
            })
            ->when(!empty($this->filters['employees']), function($query) {
                $query->whereIn('employee_id', $this->filters['employees']);
            })
            ->when(!empty($this->filters['leave_types']), function($query) {
                $query->whereIn('leave_type_id', $this->filters['leave_types']);
            })
            ->when($this->filters['status'], function($query) {
                $query->where('status', $this->filters['status']);
            })
            ->where('firm_id', Session::get('firm_id'))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);
    }
    public function showRequestLogs($id)
    {
        $this->selectedId = $id;
        $this->modal('add-emp-leave-request-logs')->show();
    }
    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Leave/blades/emp-leave-requests.blade.php'));
    }
} 