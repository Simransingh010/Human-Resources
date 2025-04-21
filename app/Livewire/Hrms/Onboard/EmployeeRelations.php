<?php

namespace App\Livewire\Hrms\Onboard;

use Livewire\Component;
use App\Models\Hrms\EmployeeRelation;
use App\Models\Hrms\Employee;
use Flux;

class EmployeeRelations extends Component
{
    use \Livewire\WithPagination;
    
    public array $relationStatuses = [];
    
    public $relationData = [
        'id' => null,
        'employee_id' => '',
        'relation' => '',
        'person_name' => '',
        'occupation' => '',
        'dob' => '',
        'qualification' => '',
        'is_inactive' => false,
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
    public function relationsList()
    {
        return EmployeeRelation::query()
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

    public function fetchRelation($id)
    {
        $relation = EmployeeRelation::findOrFail($id);
        $this->relationData = $relation->toArray();
        $this->isEditing = true;
        $this->modal('mdl-relation')->show();
    }

    public function saveRelation()
    {
        $validatedData = $this->validate([
            'relationData.employee_id' => 'required|exists:employees,id',
            'relationData.relation' => 'required|string|max:50',
            'relationData.person_name' => 'required|string|max:255',
            'relationData.occupation' => 'nullable|string|max:255',
            'relationData.dob' => 'nullable|date|before:today',
            'relationData.qualification' => 'nullable|string|max:255',
            'relationData.is_inactive' => 'boolean',
        ]);

        if ($this->isEditing) {
            $relation = EmployeeRelation::findOrFail($this->relationData['id']);
            $relation->update($validatedData['relationData']);
            session()->flash('message', 'Relation updated successfully.');
        } else {
            $validatedData['relationData']['firm_id'] = session('firm_id');
            EmployeeRelation::create($validatedData['relationData']);
            session()->flash('message', 'Relation added successfully.');
        }

        $this->resetForm();
        $this->modal('mdl-relation')->close();
        Flux::toast(
            heading: 'Changes saved.',
            text: 'Relation details have been updated successfully.',
            position: 'top-right',
        );
    }

    public function resetForm()
    {
        $this->relationData = [
            'id' => null,
            'employee_id' => '',
            'relation' => '',
            'person_name' => '',
            'occupation' => '',
            'dob' => '',
            'qualification' => '',
            'is_inactive' => false,
        ];
        $this->isEditing = false;
    }

    public function update_rec_status($relationId)
    {
        $relation = EmployeeRelation::find($relationId);
        if ($relation) {
            $relation->is_inactive = !$this->relationStatuses[$relationId];
            $relation->save();
            Flux::toast(
                heading: 'Changes saved.',
                text: 'Relation Status Changed',
                position: 'top-right',
            );
        }
    }

    public function deleteRelation($relationId)
    {
        $relation = EmployeeRelation::findOrFail($relationId);
        $employeeName = $relation->employee->fname . ' ' . $relation->employee->lname;
        $relationName = $relation->person_name;
        
        // Delete the relation
        $relation->delete();
        
        // Show toast notification
        Flux::toast(
            heading: 'Relation Deleted',
            text: "Relation {$relationName} for employee {$employeeName} has been deleted successfully.",
            position: 'top-right',
        );
    }

    public function render()
    {
        $this->relationStatuses = EmployeeRelation::pluck('is_inactive', 'id')->toArray();
        return view('livewire.hrms.onboard.employee-relations', [
            'employees' => $this->employeesList
        ]);
    }
} 