<?php

namespace App\Livewire\Hrms\EmployeesMeta;

use Livewire\Component;
use App\Models\Hrms\EmpWorkShift;
use App\Models\Hrms\WorkShift;
use App\Models\Hrms\Employee;
use Flux;

class EmployeeWorkShift extends Component
{
    use \Livewire\WithPagination;

    public array $listsForFields = [];
    public Employee $employee;

    public $shiftData = [
        'id' => null,
        'employee_id' => '',
        'work_shift_id' => '',
        'start_date' => '',
        'end_date' => '',
    ];

    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $isEditing = false;

    public function mount($employeeId)
    {
        session()->put('firm_id', session('firm_id'));
        $this->employee = Employee::findOrFail($employeeId);
        $this->shiftsList();
        $this->initListsForFields();
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
            ->with('work_shift')
            ->where('employee_id', $this->employee->id)
            ->where('firm_id', session('firm_id'))
            ->when($this->sortBy, fn($query) => $query->orderBy($this->sortBy, $this->sortDirection))
            ->get();
    }

    public function fetchShift($id)
    {
        $shift = EmpWorkShift::findOrFail($id);
        $this->shiftData = $shift->toArray();
        $this->isEditing = true;
        $this->modal('mdl-shift')->show();
    }

    public function saveShift()
    {
        $validatedData = $this->validate([
            'shiftData.work_shift_id' => 'required|exists:work_shifts,id',
            'shiftData.start_date' => 'required|date',
            'shiftData.end_date' => 'nullable|date|after:shiftData.start_date',
        ]);

        $validatedData['shiftData']['employee_id'] = $this->employee->id;

        if ($this->isEditing) {
            $shift = EmpWorkShift::findOrFail($this->shiftData['id']);
            $shift->update($validatedData['shiftData']);
            $toast = 'Work shift updated successfully.';
        } else {
            $validatedData['shiftData']['firm_id'] = session('firm_id');
            EmpWorkShift::create($validatedData['shiftData']);
            $toast = 'Work shift added successfully.';
        }

        $this->resetForm();
        $this->modal('mdl-shift')->close();
        Flux::toast(
            heading: 'Changes saved.',
            text: $toast,
        );
    }

    protected function initListsForFields(): void
    {
        $this->listsForFields['work_shifts'] = WorkShift::where('firm_id', session('firm_id'))
            ->pluck('shift_title', 'id')
            ->toArray();
    }

    public function resetForm()
    {
        $this->shiftData = [
            'id' => null,
            'employee_id' => '',
            'work_shift_id' => '',
            'start_date' => '',
            'end_date' => '',
        ];
        $this->isEditing = false;
    }

    public function deleteShift($shiftId)
    {
        $shift = EmpWorkShift::findOrFail($shiftId);
        $shift->delete();

        Flux::toast(
            heading: 'Work Shift Deleted',
            text: "Work shift has been deleted successfully."
        );
    }

    public function render()
    {
        return view('livewire.hrms.employees-meta.employee-work-shift');
    }
} 