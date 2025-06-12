<?php

namespace App\Livewire\Hrms\Payroll;

use App\Models\Hrms\TaxBracket;
use App\Models\Hrms\TaxRegime;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Session;
use Flux;

class TaxBrackets extends Component
{
    use WithPagination;

    public $perPage = 10;
    public $sortBy = 'income_from';
    public $sortDirection = 'asc';
    public $isEditing = false;

    // Field configuration for form and table
    public array $fieldConfig = [
        'regime_id' => ['label' => 'Tax Regime', 'type' => 'select', 'listKey' => 'tax_regimes'],
        'type' => ['label' => 'Type', 'type' => 'select', 'listKey' => 'bracket_types'],
        'income_from' => ['label' => 'Income From', 'type' => 'number'],
        'income_to' => ['label' => 'Income To', 'type' => 'number'],
        'rate' => ['label' => 'Rate (%)', 'type' => 'number'],
        'apply_breakdown_rate' => ['label' => 'Apply Breakdown Rate', 'type' => 'select', 'listKey' => 'apply_breakdown_rates'],
    ];

    // Filter fields configuration
    public array $filterFields = [
        'regime_id' => ['label' => 'Tax Regime', 'type' => 'select', 'listKey' => 'tax_regimes'],
        'type' => ['label' => 'Type', 'type' => 'select', 'listKey' => 'bracket_types'],
        'income_from' => ['label' => 'Income From', 'type' => 'number'],
    ];

    public array $listsForFields = [];
    public array $filters = [];
    public array $visibleFields = [];
    public array $visibleFilterFields = [];

    public $formData = [
        'id' => null,
        'firm_id' => null,
        'regime_id' => null,
        'type' => '',
        'income_from' => null,
        'income_to' => null,
        'rate' => null,
        'apply_breakdown_rate' => '',
    ];

    public function mount()
    {
        $this->initListsForFields();

        // Set default visible fields
        $this->visibleFields = ['regime_id', 'type', 'income_from', 'income_to', 'rate', 'apply_breakdown_rate'];
        $this->visibleFilterFields = ['regime_id', 'type', 'income_from'];

        // Initialize filters
        $this->filters = array_fill_keys(array_keys($this->filterFields), '');

        // Initialize form data with default values
        $this->formData = [
            'id' => null,
            'firm_id' => session('firm_id'),
            'regime_id' => '',
            'type' => '',
            'income_from' => null,
            'income_to' => null,
            'rate' => null,
            'apply_breakdown_rate' => '',
        ];
    }

    protected function initListsForFields(): void
    {
        // Get tax regimes for dropdown
        $this->listsForFields['tax_regimes'] = TaxRegime::where('firm_id', Session::get('firm_id'))
            ->where('is_active', true)
            ->pluck('name', 'id')
            ->toArray();

        // Tax bracket types
        $this->listsForFields['bracket_types'] = [
            'CESS' => 'CESS',
            'SURCHARGE' => 'SURCHARGE',
            'SLAB' => 'SLAB',
        ];

        // Apply breakdown rate options
        $this->listsForFields['apply_breakdown_rates'] = TaxBracket::APPLY_BREAKDOWN_RATE;
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
        return TaxBracket::query()
            ->where('firm_id', Session::get('firm_id'))
            ->when($this->filters['regime_id'], fn($query, $value) =>
                $query->where('regime_id', $value))
            ->when($this->filters['type'], fn($query, $value) =>
                $query->where('type', $value))
            ->when($this->filters['income_from'], fn($query, $value) =>
                $query->where('income_from', '>=', $value))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);
    }

    protected function rules()
    {
        return [
            'formData.regime_id' => 'required|integer|exists:tax_regimes,id',
            'formData.type' => 'required|string|in:CESS,SURCHARGE,SLAB',
            'formData.income_from' => 'required|numeric|min:0',
            'formData.income_to' => 'nullable|numeric|gt:formData.income_from',
            'formData.rate' => 'required|numeric|min:0|max:100',
            'formData.apply_breakdown_rate' => 'required|string|in:yes,no'
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
            $taxBracket = TaxBracket::findOrFail($this->formData['id']);
            $taxBracket->update($validatedData['formData']);
            $toastMsg = 'Tax bracket updated successfully';
        } else {
            TaxBracket::create($validatedData['formData']);
            $toastMsg = 'Tax bracket added successfully';
        }

        $this->resetForm();
        $this->modal('mdl-tax-bracket')->close();
        Flux::toast(
            variant: 'success',
            heading: 'Changes saved.',
            text: $toastMsg,
        );
    }

    public function resetForm()
    {
        $this->formData = [
            'id' => null,
            'firm_id' => session('firm_id'),
            'regime_id' => '',
            'type' => '',
            'income_from' => null,
            'income_to' => null,
            'rate' => null,
            'apply_breakdown_rate' => '',
        ];
        $this->isEditing = false;
    }

    public function edit($id)
    {
        $this->isEditing = true;
        $taxBracket = TaxBracket::findOrFail($id);
        $this->formData = $taxBracket->toArray();
        $this->modal('mdl-tax-bracket')->show();
    }

    public function delete($id)
    {
        $taxBracket = TaxBracket::findOrFail($id);
        $taxBracket->delete();

        Flux::toast(
            variant: 'success',
            heading: 'Record Deleted.',
            text: 'Tax bracket has been deleted successfully',
        );
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Payroll/blades/tax-brackets.blade.php'));
    }
}
