<?php

namespace App\Livewire\Hrms\Onboard;

use Livewire\Component;
use App\Models\Hrms\EmployeeDoc;
use App\Models\Hrms\Employee;
use App\Models\Settings\DocumentType;
use Livewire\WithFileUploads;
use Flux;
use Illuminate\Support\Facades\Storage;

class EmployeeDocs extends Component
{
    use \Livewire\WithPagination;
    use WithFileUploads;
    
    public array $docStatuses = [];
    public $document; // For file upload
    
    public $docData = [
        'id' => null,
        'employee_id' => '',
        'document_type_id' => '',
        'document_number' => '',
        'issued_date' => '',
        'expiry_date' => '',
        'doc_url' => '',
        'is_inactive' => false,
    ];

    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $isEditing = false;

    public function mount()
    {
        session()->put('firm_id', 3);
        $this->loadDocStatuses();
    }

    private function loadDocStatuses()
    {
        $this->docStatuses = EmployeeDoc::pluck('is_inactive', 'id')
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
    public function docsList()
    {
        return EmployeeDoc::query()
            ->with(['employee', 'document_type'])
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

    #[\Livewire\Attributes\Computed]
    public function documentTypesList()
    {
        return DocumentType::where('firm_id', session('firm_id'))
            ->where('is_inactive', false)
            ->get();
    }

    public function fetchDoc($id)
    {
        $doc = EmployeeDoc::findOrFail($id);
        $this->docData = $doc->toArray();
        $this->isEditing = true;
        $this->modal('mdl-doc')->show();
    }

    public function saveDoc()
    {
        $validatedData = $this->validate([
            'docData.employee_id' => 'required|exists:employees,id',
            'docData.document_type_id' => 'required|exists:document_types,id',
            'docData.document_number' => 'required|string|max:255',
            'docData.issued_date' => 'nullable|date',
            'docData.expiry_date' => 'nullable|date|after:docData.issued_date',
            'document' => 'nullable|file|max:10240', // 10MB max
        ]);

        if ($this->document) {
            $path = $this->document->store('employee-docs', 'public');
            $this->docData['doc_url'] = $path;
        }

        if ($this->isEditing) {
            $doc = EmployeeDoc::findOrFail($this->docData['id']);
            $doc->update($this->docData);
            session()->flash('message', 'Document updated successfully.');
        } else {
            $this->docData['firm_id'] = session('firm_id');
            EmployeeDoc::create($this->docData);
            session()->flash('message', 'Document added successfully.');
        }

        $this->resetForm();
        $this->modal('mdl-doc')->close();
        Flux::toast(
            heading: 'Changes saved.',
            text: 'Document details have been updated successfully.',
        );
    }

    public function resetForm()
    {
        $this->docData = [
            'id' => null,
            'employee_id' => '',
            'document_type_id' => '',
            'document_number' => '',
            'issued_date' => '',
            'expiry_date' => '',
            'doc_url' => '',
            'is_inactive' => false,
        ];
        $this->document = null;
        $this->isEditing = false;
    }

    public function update_rec_status($docId)
    {
        $doc = EmployeeDoc::findOrFail($docId);
        $doc->is_inactive = !$doc->is_inactive;
        $doc->save();

        $this->docStatuses[$docId] = !$doc->is_inactive;

        Flux::toast(
            heading: 'Status Updated',
            text: $doc->is_inactive ? 'Document has been deactivated.' : 'Document has been activated.'
        );
    }

    public function deleteDoc($docId)
    {
        $doc = EmployeeDoc::findOrFail($docId);
        $docNumber = $doc->document_number;
        
        // Delete the document file if it exists
        if ($doc->doc_url) {
            Storage::disk('public')->delete($doc->doc_url);
        }
        
        // Delete the document record
        $doc->delete();
        
        // Show toast notification
        Flux::toast(
            heading: 'Document Deleted',
            text: "Document {$docNumber} has been deleted successfully."
        );
    }

    public function render()
    {
        return view('livewire.hrms.onboard.employee-docs', [
            'employees' => $this->employeesList,
            'document_types' => $this->documentTypesList
        ]);
    }
} 