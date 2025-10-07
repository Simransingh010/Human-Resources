<?php

namespace App\Livewire\Hrms\Taxation;

use App\Models\Hrms\EmpTaxDeclaration;
use App\Models\Hrms\DeclarationType;
use App\Models\Hrms\Employee;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;

class EmpTaxDeclarations extends Component
{
    use WithPagination;
    use WithFileUploads;

    public $perPage = 20;
    public $sortBy = 'employee_name';
    public $sortDirection = 'asc';

    public $search = '';
    public $filterStatus = '';
    public $filterSource = '';
    public $filterDeclarationTypeId = '';

    public $listsForFields = [];

    public $formData = [
        'emp_id' => null,
        'declaration_type_id' => null,
        'declared_amount' => null,
        'approved_amount' => null,
        'status' => 'pending',
        'source' => null,
        'remarks' => '',
    ];

    public $document = null;

    private $searchCache = [];
    private $lastCacheKey = null;

    public function mount()
    {
        $this->initListsForFields();
    }

    protected function initListsForFields(): void
    {
        $firmId = Session::get('firm_id');
        $this->listsForFields = [
            'status' => EmpTaxDeclaration::STATUS_SELECT,
            'source' => EmpTaxDeclaration::SOURCE_SELECT,
            'declaration_types' => DeclarationType::query()->orderBy('name')->pluck('name', 'id')->toArray(),
            'employees' => Employee::query()
                ->where('firm_id', $firmId)
                ->orderBy('fname')
                ->limit(200)
                ->get()
                ->mapWithKeys(function ($e) {
                    $name = trim(($e->fname ?? '') . ' ' . ($e->mname ?? '') . ' ' . ($e->lname ?? ''));
                    return [$e->id => $name ?: ('EMP #' . $e->id)];
                })->toArray(),
        ];
    }

    protected function rules(): array
    {
        return [
            'formData.emp_id' => 'required|integer',
            'formData.declaration_type_id' => 'required|integer',
            'formData.declared_amount' => 'required|numeric|min:0',
            'formData.approved_amount' => 'nullable|numeric|min:0',
            'formData.status' => 'required|string',
            'formData.source' => 'nullable|string',
            'formData.remarks' => 'nullable|string',
            'document' => 'nullable|file|mimes:pdf|max:10240',
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
        $firmId = Session::get('firm_id');
        $financialYearId = Session::get('financial_year_id');

        DB::transaction(function () use ($validated, $firmId, $financialYearId) {
            $data = $validated['formData'];

            $supportingPath = null;
            if ($this->document) {
                $supportingPath = $this->document->store('emp-tax-declarations', 'public');
            }

            EmpTaxDeclaration::create([
                'firm_id' => $firmId,
                'emp_id' => $data['emp_id'],
                'financial_year_id' => $financialYearId,
                'declaration_type_id' => $data['declaration_type_id'],
                'declared_amount' => $data['declared_amount'] ?? 0,
                'approved_amount' => 0,
                'status' => 'pending',
                'source' => $data['source'] ?? null,
                'remarks' => $data['remarks'] ?? null,
                'supporting_doc' => $supportingPath,
            ]);
        });

        $this->resetForm();
        if (method_exists($this, 'modal')) {
            $this->modal('mdl-emp-tax-declaration')->close();
        }
        \Flux\Flux::toast('Declaration added successfully', 'success');
        $this->invalidateCache();
    }

    public function resetForm()
    {
        $this->formData = [
            'emp_id' => null,
            'declaration_type_id' => null,
            'declared_amount' => null,
            'approved_amount' => null,
            'status' => 'pending',
            'source' => null,
            'remarks' => '',
        ];
        $this->document = null;
    }

    private function invalidateCache(): void
    {
        $this->searchCache = [];
        $this->lastCacheKey = null;
        $this->resetPage();
    }

    public function getTableRowsProperty()
    {
        $firmId = Session::get('firm_id');
        $fyId = Session::get('financial_year_id');
        $page = $this->getPage();
        $cacheKey = implode('|', [
            'emp_tax_declarations',
            $firmId,
            $fyId,
            strtolower(trim($this->search)),
            $this->filterStatus,
            $this->filterSource,
            $this->filterDeclarationTypeId,
            $this->sortBy,
            $this->sortDirection,
            $this->perPage,
            $page,
        ]);

        if ($this->lastCacheKey === $cacheKey && isset($this->searchCache[$cacheKey])) {
            return $this->searchCache[$cacheKey];
        }

        $query = EmpTaxDeclaration::query()
            ->from('emp_tax_declarations')
            ->where('emp_tax_declarations.firm_id', $firmId)
            ->when($fyId, fn($q) => $q->where('emp_tax_declarations.financial_year_id', $fyId))
            ->when($this->filterStatus !== '', fn($q) => $q->where('emp_tax_declarations.status', $this->filterStatus))
            ->when($this->filterSource !== '', fn($q) => $q->where('emp_tax_declarations.source', $this->filterSource))
            ->when($this->filterDeclarationTypeId !== '', fn($q) => $q->where('emp_tax_declarations.declaration_type_id', $this->filterDeclarationTypeId));

        $query->leftJoin('employees as e', function($join) use ($firmId) {
            $join->on('e.id', '=', 'emp_tax_declarations.emp_id')
                 ->whereNull('e.deleted_at')
                 ->where('e.firm_id', '=', $firmId);
        });

        $query->leftJoin('declaration_type as dt', function($join) {
            $join->on('dt.id', '=', 'emp_tax_declarations.declaration_type_id');
        });

        if (trim($this->search) !== '') {
            $term = '%' . strtolower(trim($this->search)) . '%';
            $query->where(function($q) use ($term) {
                $q->whereRaw('LOWER(COALESCE(e.fname, "")) like ?', [$term])
                  ->orWhereRaw('LOWER(COALESCE(e.mname, "")) like ?', [$term])
                  ->orWhereRaw('LOWER(COALESCE(e.lname, "")) like ?', [$term]);
            });
        }

        $query->select([
            'emp_tax_declarations.*',
            DB::raw("TRIM(CONCAT(COALESCE(e.fname,''),' ',COALESCE(e.mname,''),' ',COALESCE(e.lname,''))) as employee_name"),
            DB::raw("dt.name as declaration_type_name"),
        ]);

        $baseSortable = [
            'declared_amount' => 'emp_tax_declarations.declared_amount',
            'approved_amount' => 'emp_tax_declarations.approved_amount',
            'status' => 'emp_tax_declarations.status',
            'source' => 'emp_tax_declarations.source',
            'created_at' => 'emp_tax_declarations.created_at',
        ];
        if ($this->sortBy === 'employee_name') {
            $query->orderBy('employee_name', $this->sortDirection);
        } elseif (array_key_exists($this->sortBy, $baseSortable)) {
            $query->orderBy($baseSortable[$this->sortBy], $this->sortDirection);
        } elseif ($this->sortBy === 'declaration_type_name') {
            $query->orderBy('declaration_type_name', $this->sortDirection);
        } else {
            $query->orderBy('emp_tax_declarations.created_at', 'desc');
        }

        $rows = $query->paginate($this->perPage);

        $this->searchCache[$cacheKey] = $rows;
        $this->lastCacheKey = $cacheKey;
        return $rows;
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Taxation/emp-tax-declarations.blade.php'), [
            'rows' => $this->tableRows,
            'lists' => $this->listsForFields,
            'sortBy' => $this->sortBy,
            'sortDirection' => $this->sortDirection,
        ]);
    }
}
