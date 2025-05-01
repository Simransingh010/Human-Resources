<?php

namespace App\Livewire\Hrms\Workshifts\WorkShiftMeta;

use App\Models\Hrms\EmpWorkShift;
use App\Models\Hrms\WorkShift;
use App\Models\Hrms\Employee;
use Livewire\Component;
use Livewire\WithPagination;
use Carbon\Carbon;
use Flux;
use Illuminate\Support\Str;

class EmpWorkShifts extends Component
{
    use WithPagination;
    
    public $selectedId = null;
    public $sortBy = 'start_date';
    public $sortDirection = 'desc';
    public $formData = [
        'id' => null,
        'work_shift_id' => '',
        'employee_id' => '',
        'start_date' => '',
        'end_date' => '',
    ];

    public $isEditing = false;
    public $modal = false;

    // Add filter properties
    public $filters = [
        'search_shift' => '',
        'search_employee' => '',
        'search_date' => '',
    ];

    public array $listsForFields = [];

    public function mount()
    {
        $this->resetPage();
        $this->getWorkShiftsForSelect();
        $this->getEmployeesForSelect();
    }

    private function getWorkShiftsForSelect()
    {
        $this->listsForFields['work_shifts'] = WorkShift::where('firm_id', session('firm_id'))
            ->where('is_inactive', 0)
            ->pluck('shift_title', 'id')
            ->toArray();
    }

    private function getEmployeesForSelect()
    {
        $this->listsForFields['employees'] = Employee::where('firm_id', session('firm_id'))
            ->where('is_inactive', 0)
            ->get()
            ->mapWithKeys(function ($employee) {
                $name = trim(implode(' ', array_filter([$employee->fname, $employee->mname, $employee->lname])));
                return [$employee->id => $name];
            })
            ->toArray();
    }

    #[\Livewire\Attributes\Computed]
    public function list()
    {
        return EmpWorkShift::query()
            ->where('firm_id', session('firm_id'))
            ->when($this->filters['search_shift'], function($query) {
                $query->whereHas('work_shift', function($q) {
                    $q->where('shift_title', 'like', '%' . $this->filters['search_shift'] . '%');
                });
            })
            ->when($this->filters['search_employee'], function($query) {
                $query->whereHas('employee', function($q) {
                    $search = $this->filters['search_employee'];
                    $q->where(function($q) use ($search) {
                        $q->where('fname', 'like', '%' . $search . '%')
                          ->orWhere('mname', 'like', '%' . $search . '%')
                          ->orWhere('lname', 'like', '%' . $search . '%');
                    });
                });
            })
            ->when($this->filters['search_date'], function($query) {
                $query->where(function($q) {
                    $q->whereDate('start_date', $this->filters['search_date'])
                      ->orWhereDate('end_date', $this->filters['search_date']);
                });
            })
            ->with(['work_shift', 'employee'])
            ->when($this->sortBy, fn($query) => $query->orderBy($this->sortBy, $this->sortDirection))
            ->paginate(5);
    }

    public function store()
    {
        $validatedData = $this->validate([
            'formData.work_shift_id' => 'required|exists:work_shifts,id',
            'formData.employee_id' => 'required|exists:employees,id',
            'formData.start_date' => 'required|date',
            'formData.end_date' => 'nullable|date|after_or_equal:formData.start_date',
        ]);

        // Convert empty strings to null
        $validatedData['formData'] = collect($validatedData['formData'])
            ->map(function($val) {
                return $val === '' ? null : $val;
            })
            ->toArray();

        // Add firm_id from session
        $validatedData['formData']['firm_id'] = session('firm_id');

        if ($this->isEditing) {
            $empWorkShift = EmpWorkShift::findOrFail($this->formData['id']);
            $empWorkShift->update($validatedData['formData']);
            $toastMsg = 'Employee Work Shift updated successfully';
        } else {
            EmpWorkShift::create($validatedData['formData']);
            $toastMsg = 'Employee Work Shift added successfully';
        }

        $this->resetForm();
        $this->modal('mdl-emp-shift')->close();
        Flux::toast(
            variant: 'success',
            heading: 'Changes saved.',
            text: $toastMsg,
        );
    }

    public function clearFilters()
    {
        $this->reset('filters');
        $this->resetPage();
    }

    public function edit($id)
    {
        $empWorkShift = EmpWorkShift::findOrFail($id);
        $this->formData = array_merge($empWorkShift->toArray(), [
            'start_date' => $empWorkShift->start_date ? $empWorkShift->start_date->format('Y-m-d') : null,
            'end_date' => $empWorkShift->end_date ? $empWorkShift->end_date->format('Y-m-d') : null,
        ]);
        $this->isEditing = true;
        $this->modal('mdl-emp-shift')->show();
    }

    public function delete($id)
    {
        $empWorkShift = EmpWorkShift::findOrFail($id);
        $empWorkShift->delete();
        
        Flux::toast(
            variant: 'success',
            heading: 'Record Deleted.',
            text: 'Employee Work Shift has been deleted successfully',
        );
    }

    public function resetForm()
    {
        $this->reset(['formData']);
        $this->isEditing = false;
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/WorkShifts/WorkShiftMeta/blades/emp-work-shifts.blade.php'));
    }
} 