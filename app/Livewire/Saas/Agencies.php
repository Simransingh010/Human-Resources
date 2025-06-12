<?php

namespace App\Livewire\Saas;

use App\Models\Saas\Agency;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Session;
use Flux;

class Agencies extends Component
{
    use WithPagination;

    public $perPage = 10;
    public $sortBy = 'name';
    public $sortDirection = 'asc';
    public $isEditing = false;

    // Field configuration for form and table
    public array $fieldConfig = [
        'name' => ['label' => 'Agency Name', 'type' => 'text'],
        'email' => ['label' => 'Email', 'type' => 'email'],
        'phone' => ['label' => 'Phone', 'type' => 'text']
    ];

    // Filter fields configuration
    public array $filterFields = [
        'name' => ['label' => 'Agency Name', 'type' => 'text'],
        'email' => ['label' => 'Email', 'type' => 'text'],
        'phone' => ['label' => 'Phone', 'type' => 'text']
    ];

    public array $listsForFields = [];
    public array $filters = [];
    public array $visibleFields = [];
    public array $visibleFilterFields = [];

    public $formData = [
        'id' => null,
        'name' => '',
        'email' => '',
        'phone' => ''
    ];

    public function mount()
    {
        $this->initListsForFields();
        $this->visibleFields = ['name', 'email', 'phone'];
        $this->visibleFilterFields = ['name', 'email', 'phone'];
        $this->filters = array_fill_keys(array_keys($this->filterFields), '');
    }

    protected function initListsForFields(): void
    {
        // No dropdowns for now
    }

    public function applyFilters()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->filters = array_fill_keys(array_keys($this->filterFields), '');
        $this->resetPage();
    }

    public function toggleColumn(string $field)
    {
        if (in_array($field, $this->visibleFields)) {
            $this->visibleFields = array_filter(
                $this->visibleFields,
                fn($f) => $f !== $field
            );
        } else {
            $this->visibleFields[] = $field;
        }
    }

    public function toggleFilterColumn(string $field)
    {
        if (in_array($field, $this->visibleFilterFields)) {
            $this->visibleFilterFields = array_filter(
                $this->visibleFilterFields,
                fn($f) => $f !== $field
            );
        } else {
            $this->visibleFilterFields[] = $field;
        }
    }

    #[Computed]
    public function list()
    {
        return Agency::query()
            ->when($this->filters['name'], fn($query, $value) => $query->where('name', 'like', "%{$value}%"))
            ->when($this->filters['email'], fn($query, $value) => $query->where('email', 'like', "%{$value}%"))
            ->when($this->filters['phone'], fn($query, $value) => $query->where('phone', 'like', "%{$value}%"))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);
    }

    protected function rules()
    {
        return [
            'formData.name' => 'required|string|max:255',
            'formData.email' => 'nullable|email|max:255',
            'formData.phone' => 'nullable|string|max:255'
        ];
    }

    public function store()
    {
        $validatedData = $this->validate();
        $validatedData['formData'] = collect($validatedData['formData'])
            ->map(fn($val) => $val === '' ? null : $val)
            ->toArray();
        if ($this->isEditing) {
            $agency = Agency::findOrFail($this->formData['id']);
            $agency->update($validatedData['formData']);
            $toastMsg = 'Agency updated successfully';
        } else {
            Agency::create($validatedData['formData']);
            $toastMsg = 'Agency added successfully';
        }
        $this->resetForm();
        $this->modal('mdl-agency')->close();
        Flux::toast(
            variant: 'success',
            heading: 'Changes saved.',
            text: $toastMsg,
        );
    }

    public function resetForm()
    {
        $this->reset(['formData']);
        $this->isEditing = false;
    }

    public function edit($id)
    {
        $this->isEditing = true;
        $agency = Agency::findOrFail($id);
        $this->formData = $agency->toArray();
        $this->modal('mdl-agency')->show();
    }

    public function delete($id)
    {
        $agency = Agency::findOrFail($id);
        $agency->delete();
        Flux::toast(
            variant: 'success',
            heading: 'Record Deleted.',
            text: 'Agency has been deleted successfully',
        );
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Saas/blades/agencies.blade.php'));
    }
} 