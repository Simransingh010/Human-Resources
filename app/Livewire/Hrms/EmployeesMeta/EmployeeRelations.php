<?php

namespace App\Livewire\Hrms\EmployeesMeta;

use Livewire\Component;
use App\Models\Hrms\EmployeeRelation;
use App\Models\Hrms\Employee;
use Flux;

class EmployeeRelations extends Component
{
    use \Livewire\WithPagination;

    Public array $listsForFields = [];
    public Employee $employee;
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

    public function mount($employeeId)
    {
        session()->put('firm_id', session('firm_id'));
        $this->employee = Employee::findOrFail($employeeId);
        $this->relationsList();
        $this->initlistsForFields();
        $this->loadRelationStatuses();
    }

    private function loadRelationStatuses()
    {
        $this->relationStatuses = EmployeeRelation::where('employee_id', $this->employee->id)
            ->pluck('is_inactive', 'id')
            ->map(function ($status) {
                return !$status;
            })
            ->toArray();
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
            ->where('employee_id', $this->employee->id)
            ->where('firm_id', session('firm_id'))
            ->when($this->sortBy, fn($query) => $query->orderBy($this->sortBy, $this->sortDirection))
            ->get();
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
            'relationData.relation' => 'required|string|max:50',
            'relationData.person_name' => 'required|string|max:255',
            'relationData.occupation' => 'nullable|string|max:255',
            'relationData.dob' => 'nullable|date|before:today',
            'relationData.qualification' => 'nullable|string|max:255',
            'relationData.is_inactive' => 'boolean',
        ]);

        $validatedData['relationData']['employee_id'] = $this->employee->id;

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
        );
    }

    protected function initlistsForFields() : void
    {
        $this->listsForFields['relation'] = EmployeeRelation::RELATION_SELECT;
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
        $relation = EmployeeRelation::findOrFail($relationId);
        $relation->is_inactive = !$relation->is_inactive;
        $relation->save();

        $this->relationStatuses[$relationId] = !$relation->is_inactive;

        Flux::toast(
            heading: 'Status Updated',
            text: $relation->is_inactive ? 'Relation has been deactivated.' : 'Relation has been activated.'
        );
    }

    public function deleteRelation($relationId)
    {
        $relation = EmployeeRelation::findOrFail($relationId);
        $relationName = $relation->person_name;

        // Delete the relation
        $relation->delete();

        // Show toast notification
        Flux::toast(
            heading: 'Relation Deleted',
            text: "Relation {$relationName} has been deleted successfully."
        );
    }
}