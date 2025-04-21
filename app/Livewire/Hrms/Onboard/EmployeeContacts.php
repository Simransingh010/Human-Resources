<?php

namespace App\Livewire\Hrms\Onboard;

use Livewire\Component;
use App\Models\Hrms\EmployeeContact;
use App\Models\Hrms\Employee;
use Flux;

class EmployeeContacts extends Component
{
    use \Livewire\WithPagination;
    
    public array $contactStatuses = [];
    
    public $contactData = [
        'id' => null,
        'employee_id' => '',
        'contact_type' => '',
        'contact_value' => '',
        'contact_person' => '',
        'relation' => '',
        'is_primary' => false,
        'is_for_emergency' => false,
        'is_inactive' => false,
    ];

    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $isEditing = false;

    public function mount()
    {
        session()->put('firm_id', 3);
        $this->loadContactStatuses();
    }

    private function loadContactStatuses()
    {
        $this->contactStatuses = EmployeeContact::pluck('is_inactive', 'id')
            ->map(function ($status) {
                return !$status; // Invert the is_inactive value for the switch
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
    public function contactsList()
    {
        return EmployeeContact::query()
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

    public function fetchContact($id)
    {
        $contact = EmployeeContact::findOrFail($id);
        $this->contactData = $contact->toArray();
        $this->isEditing = true;
        $this->modal('mdl-contact')->show();
    }

    public function saveContact()
    {
        $validatedData = $this->validate([
            'contactData.employee_id' => 'required|exists:employees,id',
            'contactData.contact_type' => 'required|string|max:255',
            'contactData.contact_value' => 'required|string|max:255',
            'contactData.contact_person' => 'nullable|string|max:255',
            'contactData.relation' => 'nullable|string|max:255',
            'contactData.is_primary' => 'boolean',
            'contactData.is_for_emergency' => 'boolean',
            'contactData.is_inactive' => 'boolean',
        ]);

        if ($this->isEditing) {
            $contact = EmployeeContact::findOrFail($this->contactData['id']);
            $contact->update($validatedData['contactData']);
            session()->flash('message', 'Contact updated successfully.');
        } else {
            $validatedData['contactData']['firm_id'] = session('firm_id');
            EmployeeContact::create($validatedData['contactData']);
            session()->flash('message', 'Contact added successfully.');
        }

        $this->resetForm();
        $this->modal('mdl-contact')->close();
        Flux::toast(
            heading: 'Changes saved.',
            text: 'Contact details have been updated successfully.',
        );
    }

    public function resetForm()
    {
        $this->contactData = [
            'id' => null,
            'employee_id' => '',
            'contact_type' => '',
            'contact_value' => '',
            'contact_person' => '',
            'relation' => '',
            'is_primary' => false,
            'is_for_emergency' => false,
            'is_inactive' => false,
        ];
        $this->isEditing = false;
    }

    public function update_rec_status($contactId)
    {
        $contact = EmployeeContact::findOrFail($contactId);
        $contact->is_inactive = !$contact->is_inactive;
        $contact->save();

        // Update the status in the local array
        $this->contactStatuses[$contactId] = !$contact->is_inactive;

        // Show toast notification
        Flux::toast(
            heading: 'Status Updated',
            text: $contact->is_inactive ? 'Contact has been deactivated.' : 'Contact has been activated.'
        );
    }

    public function render()
    {
        $this->loadContactStatuses();
        return view('livewire.hrms.onboard.employee-contacts', [
            'employees' => $this->employeesList
        ]);
    }
} 