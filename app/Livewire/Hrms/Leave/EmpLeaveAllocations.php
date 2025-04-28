<?php

namespace App\Livewire\Hrms\Leave;

use App\Models\Hrms\EmpLeaveAllocation;
use App\Models\Hrms\Employee;
use App\Models\Hrms\LeaveType;
use App\Models\Hrms\LeavesQuotaTemplate;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Session;
use Flux;

class EmpLeaveAllocations extends Component
{
    use WithPagination;

    public $perPage = 10;
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $isEditing = false;

    public $formData = [
        'id' => null,
        'firm_id' => null,
        'employee_id' => null,
        'leave_type_id' => null,
        'leaves_quota_template_id' => null,
        'days_assigned' => 0,
        'start_date' => '',
        'end_date' => '',
        'days_balance' => 0,
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
            'formData.leaves_quota_template_id' => 'nullable|integer',
            'formData.days_assigned' => 'required|integer|min:0',
            'formData.start_date' => 'required|date',
            'formData.end_date' => 'required|date|after:formData.start_date',
            'formData.days_balance' => 'required|integer|min:0',
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
        $this->listsForFields['templates'] = LeavesQuotaTemplate::where('firm_id', session('firm_id'))
            ->pluck('name', 'id');
    }

    public function resetForm()
    {
        $this->reset(['formData']);
        $this->formData['days_assigned'] = 0;
        $this->formData['days_balance'] = 0;
        $this->formData['employee_id'] = null;
        $this->formData['leave_type_id'] = null;
        $this->formData['leaves_quota_template_id'] = null;
        $this->isEditing = false;
    }

    public function store()
    {
        $validatedData = $this->validate($this->rules());

        if ($this->isEditing) {
            $allocation = EmpLeaveAllocation::findOrFail($this->formData['id']);
            $allocation->update($validatedData['formData']);
            session()->flash('message', 'Allocation updated successfully.');
        } else {
            $validatedData['formData']['firm_id'] = session('firm_id');
            EmpLeaveAllocation::create($validatedData['formData']);
            session()->flash('message', 'Allocation added successfully.');
        }

        $this->resetForm();
        $this->modal('mdl-leave-allocation')->close();
        Flux::toast(
            heading: 'Changes saved.',
            text: 'Leave allocations have been updated.',
        );
    }

    public function edit($id)
    {
        $this->isEditing = true;
        $allocation = EmpLeaveAllocation::findOrFail($id);
        $this->formData = $allocation->toArray();
        $this->formData['employee_id'] = $allocation->employee_id;
        $this->formData['leave_type_id'] = $allocation->leave_type_id;
        $this->formData['leaves_quota_template_id'] = $allocation->leaves_quota_template_id;
        $this->modal('mdl-leave-allocation')->show();
    }

    public function delete($id)
    {
        try {
            $allocation = EmpLeaveAllocation::findOrFail($id);
            $allocation->delete();

            Flux::toast(
                variant: 'success',
                heading: 'Record Deleted.',
                text: 'Allocation has been deleted successfully',
            );
        } catch (\Exception $e) {
            Flux::toast(
                variant: 'error',
                heading: 'Error',
                text: 'Failed to delete allocation: ' . $e->getMessage(),
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
        return EmpLeaveAllocation::query()
            ->with(['employee', 'leave_type', 'leaves_quota_template'])
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
            ->where('firm_id', Session::get('firm_id'))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Leave/blades/emp-leave-allocations.blade.php'));
    }
} 