<?php

namespace App\Livewire\Hrms\Payroll;

use App\Models\Hrms\TaxRegime;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Session;
use Flux;

class TaxRegimes extends Component
{
    use WithPagination;

    public $perPage = 10;
    public $sortBy = 'name';
    public $sortDirection = 'asc';
    public $isEditing = false;

    // Field configuration for form and table
    public array $fieldConfig = [
        
        'name' => ['label' => 'Name', 'type' => 'text'],
        'code' => ['label' => 'Code', 'type' => 'text'],
        'description' => ['label' => 'Description', 'type' => 'textarea'],
        'is_active' => ['label' => 'Active', 'type' => 'switch'],
    ];

    // Filter fields configuration
    public array $filterFields = [
        'code' => ['label' => 'Code', 'type' => 'text'],
        'name' => ['label' => 'Name', 'type' => 'text'],
        'is_active' => ['label' => 'Active', 'type' => 'select', 'listKey' => 'active_status'],
    ];

    public array $listsForFields = [];
    public array $filters = [];
    public array $visibleFields = [];
    public array $visibleFilterFields = [];

    public $formData = [
        'id' => null,
        'firm_id' => null,
        'code' => '',
        'name' => '',
        'description' => '',
        'is_active' => true,
    ];

    public function mount()
    {
        $this->initListsForFields();

        // Set default visible fields
        $this->visibleFields = ['code', 'name', 'description', 'is_active'];
        $this->visibleFilterFields = ['code', 'name', 'is_active'];

        // Initialize filters
        $this->filters = array_fill_keys(array_keys($this->filterFields), '');
    }

    protected function initListsForFields(): void
    {
        // Status options for dropdown
        $this->listsForFields['active_status'] = [
            '1' => 'Active',
            '0' => 'Inactive'
        ];
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
        return TaxRegime::query()
            ->where('firm_id', Session::get('firm_id'))
            ->when($this->filters['code'], fn($query, $value) =>
                $query->where('code', 'like', "%{$value}%"))
            ->when($this->filters['name'], fn($query, $value) =>
                $query->where('name', 'like', "%{$value}%"))
            ->when($this->filters['is_active'] !== '', fn($query, $value) =>
                $query->where('is_active', $this->filters['is_active']))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);
    }

    protected function rules()
    {
        return [
            'formData.code' => 'required|string|max:50',
            'formData.name' => 'required|string|max:255',
            'formData.description' => 'nullable|string',
            'formData.is_active' => 'boolean'
        ];
    }

    public function store()
    {
        $validatedData = $this->validate();

        $validatedData['formData'] = collect($validatedData['formData'])
            ->map(fn($val) => $val === '' ? null : $val)
            ->toArray();

        $validatedData['formData']['firm_id'] = session('firm_id');

        if ($this->isEditing) {
            $taxRegime = TaxRegime::findOrFail($this->formData['id']);
            $taxRegime->update($validatedData['formData']);
            $toastMsg = 'Tax regime updated successfully';
        } else {
            TaxRegime::create($validatedData['formData']);
            $toastMsg = 'Tax regime added successfully';
        }

        $this->resetForm();
        $this->modal('mdl-tax-regime')->close();
        Flux::toast(
            variant: 'success',
            heading: 'Changes saved.',
            text: $toastMsg,
        );
    }

    public function resetForm()
    {
        $this->reset(['formData']);
        $this->formData['is_active'] = true;
        $this->isEditing = false;
    }

    public function edit($id)
    {
        $this->isEditing = true;
        $taxRegime = TaxRegime::findOrFail($id);
        $this->formData = $taxRegime->toArray();
        $this->modal('mdl-tax-regime')->show();
    }

    public function delete($id)
    {
        // Check if tax regime has related records
        $taxRegime = TaxRegime::findOrFail($id);
        if (
            $taxRegime->employees()->count() > 0 ||
            $taxRegime->tax_brackets()->count() > 0
        ) {
            Flux::toast(
                variant: 'error',
                heading: 'Cannot Delete',
                text: 'This tax regime has related records and cannot be deleted.',
            );
            return;
        }

        $taxRegime->delete();
        Flux::toast(
            variant: 'success',
            heading: 'Record Deleted.',
            text: 'Tax regime has been deleted successfully',
        );
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Payroll/blades/tax-regimes.blade.php'));
    }
}
