<?php

namespace App\Livewire\Hrms\Payroll;

use App\Models\Hrms\TaxBracketBreakdown;
use App\Models\Hrms\TaxBracket;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Session;
use Flux;

class TaxBracketBreakdowns extends Component
{
    use WithPagination;

    public $perPage = 10;
    public $sortBy = 'id';
    public $sortDirection = 'desc';
    public $isEditing = false;

    // Field configuration for form and table
    public array $fieldConfig = [
        'tax_bracket_id' => ['label' => 'Tax Bracket', 'type' => 'select', 'listKey' => 'taxBrackets'],
        'breakdown_amount_from' => ['label' => 'Amount From', 'type' => 'number'],
        'breakdown_amount_to' => ['label' => 'Amount To', 'type' => 'number'],
        'rate' => ['label' => 'Rate (%)', 'type' => 'number'],
        'is_inactive' => ['label' => 'Status', 'type' => 'switch'],
    ];

    // Filter fields configuration
    public array $filterFields = [
        'tax_bracket_id' => ['label' => 'Tax Bracket', 'type' => 'select', 'listKey' => 'taxBrackets'],
        'is_inactive' => ['label' => 'Status', 'type' => 'select', 'listKey' => 'statuses'],
    ];

    public array $listsForFields = [];
    public array $filters = [];
    public array $visibleFields = [];
    public array $visibleFilterFields = [];

    public $formData = [
        'id' => null,
        'firm_id' => null,
        'tax_bracket_id' => '',
        'breakdown_amount_from' => null,
        'breakdown_amount_to' => null,
        'rate' => null,
        'is_inactive' => false,
    ];

    public function mount()
    {
        $this->resetPage();
        $this->initListsForFields();
        
        // Set default visible fields
        $this->visibleFields = ['tax_bracket_id', 'breakdown_amount_from', 'breakdown_amount_to', 'rate', 'is_inactive'];
        $this->visibleFilterFields = ['tax_bracket_id', 'is_inactive'];
        
        // Initialize filters
        $this->filters = array_fill_keys(array_keys($this->filterFields), '');
    }

    protected function initListsForFields(): void
    {
        // Get tax brackets for dropdown
        $this->listsForFields['taxBrackets'] = TaxBracket::where('firm_id', Session::get('firm_id'))
            ->with('tax_regime')
            ->get()
            ->mapWithKeys(function ($bracket) {
                $regimeName = $bracket->tax_regime ? $bracket->tax_regime->name : 'N/A';
                return [
                    $bracket->id => $regimeName . ' - ' . $bracket->type . ' (' . $bracket->income_from . ' to ' . ($bracket->income_to ?? '∞') . ')'
                ];
            })
            ->toArray();


        // Status options
        $this->listsForFields['statuses'] = [
            '0' => 'Active',
            '1' => 'Inactive'
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

    public function store()
    {
        $validatedData = $this->validate([
            'formData.tax_bracket_id' => 'required|exists:tax_brackets,id',
            'formData.breakdown_amount_from' => 'required|numeric|min:0',
            'formData.breakdown_amount_to' => 'required|numeric|min:0|gt:formData.breakdown_amount_from',
            'formData.rate' => 'required|numeric|min:0|max:100',
            'formData.is_inactive' => 'boolean'
        ]);

        $validatedData['formData']['firm_id'] = Session::get('firm_id');

        if ($this->isEditing) {
            $breakdown = TaxBracketBreakdown::findOrFail($this->formData['id']);
            $breakdown->update($validatedData['formData']);
            $toastMsg = 'Tax bracket breakdown updated successfully';
        } else {
            TaxBracketBreakdown::create($validatedData['formData']);
            $toastMsg = 'Tax bracket breakdown added successfully';
        }

        $this->resetForm();
        $this->modal('mdl-tax-bracket-breakdown')->close();
        Flux::toast(
            variant: 'success',
            heading: 'Changes saved.',
            text: $toastMsg,
        );
    }

    public function resetForm()
    {
        $this->reset(['formData']);
        $this->formData['is_inactive'] = false;
        $this->isEditing = false;
    }

    public function edit($id)
    {
        $this->isEditing = true;
        $breakdown = TaxBracketBreakdown::findOrFail($id);
        $this->formData = $breakdown->toArray();
        $this->modal('mdl-tax-bracket-breakdown')->show();
    }

    public function delete($id)
    {
        $breakdown = TaxBracketBreakdown::findOrFail($id);
        $breakdown->delete();
        Flux::toast(
            variant: 'success',
            heading: 'Record Deleted.',
            text: 'Tax bracket breakdown has been deleted successfully',
        );
    }

    #[Computed]
    public function list()
    {
        return TaxBracketBreakdown::query()
            ->where('firm_id', Session::get('firm_id'))
            ->when($this->filters['tax_bracket_id'], fn($query, $value) => 
                $query->where('tax_bracket_id', $value))
            ->when($this->filters['is_inactive'] !== '', fn($query) => 
                $query->where('is_inactive', $this->filters['is_inactive']))
            ->with(['tax_bracket'])
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Payroll/blades/tax-bracket-breakdowns.blade.php'));
    }
} 