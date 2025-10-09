<?php

namespace App\Livewire\Hrms\Payroll;

use App\Models\Hrms\TaxRebate;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class TaxRebates extends Component
{
    use WithPagination;

    public $perPage = 20;
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $search = '';

    public $formData = [
        'financial_year_id' => null,
        'tax_regime_id' => null,
        'taxable_income_lim' => null,
        'max_rebate_amount' => null,
        'section_code' => null,
    ];

    private $searchCache = [];
    private $lastCacheKey = null;

    protected function rules(): array
    {
        return [
            'formData.financial_year_id' => 'required|integer',
            'formData.tax_regime_id' => 'required|integer',
            'formData.taxable_income_lim' => 'nullable|numeric|min:0',
            'formData.max_rebate_amount' => 'nullable|numeric|min:0',
            'formData.section_code' => 'nullable|integer',
        ];
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function sort($column)
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
    }

    public function store()
    {
        $validated = $this->validate();
        DB::transaction(function () use ($validated) {
            TaxRebate::create($validated['formData']);
        });
        $this->resetForm();
        if (method_exists($this, 'modal')) {
            $this->modal('mdl-tax-rebate')->close();
        }
        \Flux\Flux::toast('Tax rebate rule added successfully', 'success');
        $this->invalidateCache();
    }

    public function resetForm()
    {
        $this->formData = [
            'financial_year_id' => null,
            'tax_regime_id' => null,
            'taxable_income_lim' => null,
            'max_rebate_amount' => null,
            'section_code' => null,
        ];
    }

    private function invalidateCache(): void
    {
        $this->searchCache = [];
        $this->lastCacheKey = null;
        $this->resetPage();
    }

    public function getTableRowsProperty()
    {
        $page = $this->getPage();
        $cacheKey = implode('|', [
            'tax_rebates',
            strtolower(trim($this->search)),
            $this->sortBy,
            $this->sortDirection,
            $this->perPage,
            $page,
        ]);

        if ($this->lastCacheKey === $cacheKey && isset($this->searchCache[$cacheKey])) {
            return $this->searchCache[$cacheKey];
        }

        $query = TaxRebate::query()
            ->when(trim($this->search) !== '', function ($q) {
                $term = '%' . strtolower(trim($this->search)) . '%';
                $q->whereRaw('CAST(financial_year_id as CHAR) like ?', [$term])
                  ->orWhereRaw('CAST(tax_regime_id as CHAR) like ?', [$term])
                  ->orWhereRaw('CAST(section_code as CHAR) like ?', [$term]);
            });

        $allowed = [
            'financial_year_id' => 'financial_year_id',
            'tax_regime_id' => 'tax_regime_id',
            'taxable_income_lim' => 'taxable_income_lim',
            'max_rebate_amount' => 'max_rebate_amount',
            'section_code' => 'section_code',
            'created_at' => 'created_at',
        ];
        $orderBy = $allowed[$this->sortBy] ?? 'created_at';
        $dir = in_array($this->sortDirection, ['asc','desc']) ? $this->sortDirection : 'desc';
        $rows = $query->orderBy($orderBy, $dir)->paginate($this->perPage);
        $this->searchCache[$cacheKey] = $rows;
        $this->lastCacheKey = $cacheKey;
        return $rows;
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Payroll/blades/tax-rebates.blade.php'), [
            'rows' => $this->tableRows,
            'sortBy' => $this->sortBy,
            'sortDirection' => $this->sortDirection,
        ]);
    }
}

