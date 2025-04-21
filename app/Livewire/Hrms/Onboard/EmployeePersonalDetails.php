<?php

namespace App\Livewire\Hrms\Onboard;

use Livewire\Component;
use App\Models\Hrms\EmployeePersonalDetail;
use App\Models\Hrms\Employee;
use Flux;

class EmployeePersonalDetails extends Component
{
    use \Livewire\WithPagination;
    
    public $personalData = [
        'id' => null,
        'employee_id' => '',
        'dob' => '',
        'marital_status' => '',
        'doa' => '',
        'nationality' => '',
        'fathername' => '',
        'mothername' => '',
        'adharno' => '',
        'panno' => '',
    ];

    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $isEditing = false;

    public function mount()
    {
        session()->put('firm_id', 3);
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
    public function personalDetailsList()
    {
        return EmployeePersonalDetail::query()
            ->with('employee')
            ->when($this->sortBy, fn($query) => $query->orderBy($this->sortBy, $this->sortDirection))
            ->paginate(5);
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
                    'name' => $employee->fname . ' ' . $employee->lname . ' (' . $employee->email . ')'
                ];
            });
    }

    public function fetchPersonalDetail($id)
    {
        $personalDetail = EmployeePersonalDetail::findOrFail($id);
        $this->personalData = $personalDetail->toArray();
        $this->isEditing = true;
        $this->modal('mdl-personal')->show();
    }

    public function savePersonalDetail()
    {
        $validatedData = $this->validate([
            'personalData.employee_id' => 'required|exists:employees,id',
            'personalData.dob' => 'required|date|before:today',
            'personalData.marital_status' => 'required|in:single,married,divorced,widowed',
            'personalData.doa' => 'nullable|date|before:today',
            'personalData.nationality' => 'required|string|max:100',
            'personalData.fathername' => 'required|string|max:255',
            'personalData.mothername' => 'required|string|max:255',
            'personalData.adharno' => 'required|string|size:12|regex:/^[0-9]+$/',
            'personalData.panno' => 'required|string|size:10|regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/',
        ]);

        if ($this->isEditing) {
            $personalDetail = EmployeePersonalDetail::findOrFail($this->personalData['id']);
            $personalDetail->update($validatedData['personalData']);
            session()->flash('message', 'Personal details updated successfully.');
        } else {
            $validatedData['personalData']['firm_id'] = session('firm_id');
            EmployeePersonalDetail::create($validatedData['personalData']);
            session()->flash('message', 'Personal details added successfully.');
        }

        $this->resetForm();
        $this->modal('mdl-personal')->close();
        Flux::toast(
            heading: 'Changes saved.',
            text: 'Personal details have been updated successfully.',
        );
    }

    public function deletePersonalDetail($detailId)
    {
        $personalDetail = EmployeePersonalDetail::findOrFail($detailId);
        $employeeName = $personalDetail->employee->fname . ' ' . $personalDetail->employee->lname;
        
        // Delete the personal detail
        $personalDetail->delete();
        
        // Show toast notification
        Flux::toast(
            heading: 'Personal Details Deleted',
            text: "Personal details for {$employeeName} have been deleted successfully."
        );
    }

    public function resetForm()
    {
        $this->personalData = [
            'id' => null,
            'employee_id' => '',
            'dob' => '',
            'marital_status' => '',
            'doa' => '',
            'nationality' => '',
            'fathername' => '',
            'mothername' => '',
            'adharno' => '',
            'panno' => '',
        ];
        $this->isEditing = false;
    }

    public function render()
    {
        return view('livewire.hrms.onboard.employee-personal-details', [
            'employees' => $this->employeesList
        ]);
    }
} 