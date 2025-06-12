<?php

namespace App\Livewire\Hrms\Attendance;

use Livewire\Component;
use App\Models\Hrms\EmpWorkShift;
use App\Models\Hrms\WorkShift;
use App\Models\Hrms\Employee;
use Flux;

class EmpWorkShifts extends Component
{
    use \Livewire\WithPagination;

    public $shiftData = [
        'id' => null,
        'firm_id' => null,
        'work_shift_id' => null,
        'employee_id' => null,
        'start_date' => null,
        'end_date' => null,
    ];

    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $isEditing = false;

    public function mount()
    {
        session()->put('firm_id', 3); // Example firm_id
    }

    public function sort($column)
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
    }

    #[\Livewire\Attributes\Computed]
    public function shiftsList()
    {
        return EmpWorkShift::query()
            ->with(['work_shift', 'employee'])
            ->when($this->sortBy, fn($query) => $query->orderBy($this->sortBy, $this->sortDirection))
            ->where('firm_id', session('firm_id'))
            ->paginate(5);
    }

    #[\Livewire\Attributes\Computed]
    public function workShiftsList()
    {
        return WorkShift::where('firm_id', session('firm_id'))
            ->where('is_inactive', false)
            ->pluck('shift_title', 'id');
    }

    #[\Livewire\Attributes\Computed]
    public function employeesList()
    {
        return Employee::where('firm_id', session('firm_id'))
            ->where('is_inactive', false)
            ->get()
            ->map(function($employee) {
                return [
                    'id' => $employee->id,
                    'name' => $employee->fname . ' ' . $employee->lname
                ];
            })
            ->pluck('name', 'id');
    }

    public function fetchShift($id)
    {
        $empWorkShift = EmpWorkShift::findOrFail($id);
        $this->shiftData = $empWorkShift->toArray();
        $this->isEditing = true;
        $this->modal('mdl-emp-shift')->show();
    }

    public function saveShift()
    {
        $validatedData = $this->validate([
            'shiftData.work_shift_id' => 'required|exists:work_shifts,id',
            'shiftData.employee_id' => 'required|exists:employees,id',
            'shiftData.start_date' => 'required|date',
            'shiftData.end_date' => 'required|date|after_or_equal:shiftData.start_date',
        ]);

        if ($this->isEditing) {
            $empWorkShift = EmpWorkShift::findOrFail($this->shiftData['id']);
            $empWorkShift->update($validatedData['shiftData']);
            session()->flash('message', 'Employee work shift updated successfully.');
        } else {
            $validatedData['shiftData']['firm_id'] = session('firm_id');
            EmpWorkShift::create($validatedData['shiftData']);
            session()->flash('message', 'Employee work shift added successfully.');
        }

        $this->resetForm();
        $this->modal('mdl-emp-shift')->close();
        Flux::toast(
            variant: 'success',
            position: 'top-right',
            heading: 'Changes saved.',
            text: 'Employee work shift has been updated.',
        );
    }

    public function deleteShift($id)
    {
        try {
            $empWorkShift = EmpWorkShift::findOrFail($id);
            $empWorkShift->delete();

            Flux::toast(
                position: 'top-right',
                variant: 'danger',
                heading: 'Success',
                text: 'Employee work shift deleted successfully.',
            );
        } catch (\Exception $e) {
            Flux::toast(
                position: 'top-right',
                heading: 'Error',
                text: 'Failed to delete employee work shift.',
                variant: 'error'
            );
        }
    }

    public function resetForm()
    {
        $this->shiftData = [
            'id' => null,
            'firm_id' => null,
            'work_shift_id' => null,
            'employee_id' => null,
            'start_date' => null,
            'end_date' => null,
        ];
        $this->isEditing = false;
    }

    public function render()
    {
        return view('livewire.hrms.attendance.emp-work-shifts');
    }
} 