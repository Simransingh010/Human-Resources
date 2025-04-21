<?php

namespace App\Livewire\Hrms\Attendance;

use Livewire\Component;
use App\Models\Hrms\EmpPunch;
use App\Models\Hrms\Employee;
use Flux;
use Livewire\WithFileUploads;

class EmpPunches extends Component
{
    use \Livewire\WithPagination;
    use WithFileUploads;

    public $punchData = [
        'id' => null,
        'firm_id' => null,
        'employee_id' => null,
        'work_date' => null,
        'punch_datetime' => null,
        'in_out' => null,
        'attend_location_id' => null,
        'punch_geo_location' => null,
        'punch_type' => null,
        'device_id' => null,
        'is_final' => false,
    ];

    public $photo;
    public $sortBy = 'punch_datetime';
    public $sortDirection = 'desc';
    public $isEditing = false;

    public function mount()
    {
        session()->put('firm_id', 1); // Example firm_id
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
    public function punchesList()
    {
        return EmpPunch::query()
            ->with('employee')
            ->when($this->sortBy, fn($query) => $query->orderBy($this->sortBy, $this->sortDirection))
            ->where('firm_id', session('firm_id'))
            ->paginate(10);
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

    public function fetchPunch($id)
    {
        $empPunch = EmpPunch::findOrFail($id);
        $this->punchData = $empPunch->toArray();
        $this->isEditing = true;
        $this->modal('mdl-emp-punch')->show();
    }

    public function savePunch()
    {
        $validatedData = $this->validate([
            'punchData.employee_id' => 'required|exists:employees,id',
            'punchData.work_date' => 'required|date',
            'punchData.punch_datetime' => 'required|date',
            'punchData.in_out' => 'required|in:IN,OUT',
            'punchData.attend_location_id' => 'nullable|string',
            'punchData.punch_type' => 'nullable|string',
            'punchData.device_id' => 'nullable|string',
            'punchData.is_final' => 'boolean',
            'photo' => 'nullable|image|max:1024',
        ]);
        dd($validatedData);
        try {
            if ($this->isEditing) {
                $empPunch = EmpPunch::findOrFail($this->punchData['id']);
                $empPunch->update($validatedData['punchData']);
            } else {
                $validatedData['punchData']['firm_id'] = session('firm_id');
                $empPunch = EmpPunch::create($validatedData['punchData']);
            }

            if ($this->photo) {
                $empPunch->addMedia($this->photo->getRealPath())
                    ->toMediaCollection('punch_photos');
            }

            $this->resetForm();
            $this->modal('mdl-emp-punch')->close();
            
            Flux::toast(
                variant: 'success',
                position: 'top-right',
                heading: 'Changes saved.',
                text: 'Employee punch record has been ' . ($this->isEditing ? 'updated' : 'added') . '.',
            );
        } catch (\Exception $e) {
            Flux::toast(
                variant: 'error',
                position: 'top-right',
                heading: 'Error',
                text: 'Failed to save punch record.',
            );
        }
    }

    public function deletePunch($id)
    {
        try {
            $empPunch = EmpPunch::findOrFail($id);
            $empPunch->delete();

            Flux::toast(
                position: 'top-right',
                variant: 'danger',
                heading: 'Success',
                text: 'Punch record deleted successfully.',
            );
        } catch (\Exception $e) {
            Flux::toast(
                position: 'top-right',
                heading: 'Error',
                text: 'Failed to delete punch record.',
                variant: 'error'
            );
        }
    }

    public function resetForm()
    {
        $this->punchData = [
            'id' => null,
            'firm_id' => null,
            'employee_id' => null,
            'work_date' => null,
            'punch_datetime' => null,
            'in_out' => null,
            'attend_location_id' => null,
            'punch_geo_location' => null,
            'punch_type' => null,
            'device_id' => null,
            'is_final' => false,
        ];
        $this->photo = null;
        $this->isEditing = false;
    }

    public function render()
    {
        return view('livewire.hrms.attendance.emp-punches');
    }
} 