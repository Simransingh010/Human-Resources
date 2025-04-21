<?php

namespace App\Livewire\Hrms\EmployeesMeta;

use Livewire\Component;
use App\Models\Hrms\EmployeePersonalDetail;
use App\Models\Hrms\Employee;
use Flux;

class EmployeePersonalDetails extends Component
{
    use \Livewire\WithPagination;
    public Employee $employee;

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

    public function mount($employeeId)
    {
        session()->put('firm_id', session('firm_id'));
        $this->employee = Employee::findOrFail($employeeId);
        $this->personalDetailsList();
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
            ->where('employee_id', $this->employee->id)
            ->where('firm_id', session('firm_id'))
            ->when($this->sortBy, fn($query) => $query->orderBy($this->sortBy, $this->sortDirection))
            ->get();
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
            'personalData.dob' => 'required|date|before:today',
            'personalData.marital_status' => 'required|in:single,married,divorced,widowed',
            'personalData.doa' => 'nullable|date|before:today',
            'personalData.nationality' => 'required|string|max:100',
            'personalData.fathername' => 'required|string|max:255',
            'personalData.mothername' => 'required|string|max:255',
            'personalData.adharno' => 'required|string|size:12|regex:/^[0-9]+$/',
            'personalData.panno' => 'required|string|size:10|regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/',
        ]);

        $validatedData['personalData']['employee_id'] = $this->employee->id;

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

}