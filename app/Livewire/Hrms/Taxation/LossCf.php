<?php

namespace App\Livewire\Hrms\Taxation;

use App\Models\Hrms\LossCf as LossCfModel;
use App\Models\Hrms\Employee;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Livewire\Component;
use Livewire\WithPagination;

class LossCf extends Component3
{
    use WithPagination;

    public $perPage = 20;
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $search = '';

    public $listsForFields = [];

    public $formData = [
        'emp_id' => null,
        'original_loss_amount' => null,
        'setoff_in_current_year' => null,
        'carry_forward_amount' => null,
        'forward_upto_year' => null,
        'declaration_id' => null,
        'itr_id' => null,
        'remarks' => '',
    ];

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
            'formData.original_loss_amount' => 'required|numeric|min:0',
            'formData.setoff_in_current_year' => 'required|numeric|min:0',
            'formData.carry_forward_amount' => 'required|numeric|min:0',
            'formData.forward_upto_year' => 'required|integer',
            'formData.declaration_id' => 'nullable|integer',
            'formData.itr_id' => 'nullable|integer',
            'formData.remarks' => 'nullable|string',
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
            LossCfModel::create([
                'firm_id' => $firmId,
                'emp_id' => $data['emp_id'],
                'financial_year_id' => $financialYearId,
                'original_loss_amount' => $data['original_loss_amount'] ?? 0,
                'setoff_in_current_year' => $data['setoff_in_current_year'] ?? 0,
                'carry_forward_amount' => $data['carry_forward_amount'] ?? 0,
                'forward_upto_year' => $data['forward_upto_year'] ?? date('Y'),
                'declaration_id' => $data['declaration_id'] ?? null,
                'itr_id' => $data['itr_id'] ?? null,
                'remarks' => $data['remarks'] ?? null,
            ]);
        });

        $this->resetForm();
        if (method_exists($this, 'modal')) {
            $this->modal('mdl-loss-cf')->close();
        }
        \Flux\Flux::toast('Loss carry-forward record added successfully', 'success');
        $this->invalidateCache();
    }

    public function resetForm()
    {
        $this->formData = [
            'emp_id' => null,
            'original_loss_amount' => null,
            'setoff_in_current_year' => null,
            'carry_forward_amount' => null,
            'forward_upto_year' => null,
            'declaration_id' => null,
            'itr_id' => null,
            'remarks' => '',
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
        $firmId = Session::get('firm_id');
        $fyId = Session::get('financial_year_id');
        $page = $this->getPage();
        $cacheKey = implode('|', [
            'loss_cf',
            $firmId,
            $fyId,
            strtolower(trim($this->search)),
            $this->sortBy,
            $this->sortDirection,
            $this->perPage,
            $page,
        ]);

        if ($this->lastCacheKey === $cacheKey && isset($this->searchCache[$cacheKey])) {
            return $this->searchCache[$cacheKey];
        }

        $query = LossCfModel::query()
            ->from('loss_cf')
            ->where('loss_cf.firm_id', $firmId)
            ->when($fyId, fn($q) => $q->where('loss_cf.financial_year_id', $fyId));

        $query->leftJoin('employees as e', function($join) use ($firmId) {
            $join->on('e.id', '=', 'loss_cf.emp_id')
                 ->whereNull('e.deleted_at')
                 ->where('e.firm_id', '=', $firmId);
        });

        if (trim($this->search) !== '') {
            $term = '%' . strtolower(trim($this->search)) . '%';
            $query->where(function($q) use ($term) {
                $q->whereRaw('LOWER(COALESCE(e.fname, "")) like ?', [$term])
                  ->orWhereRaw('LOWER(COALESCE(e.mname, "")) like ?', [$term])
                  ->orWhereRaw('LOWER(COALESCE(e.lname, "")) like ?', [$term])
                  ->orWhereRaw('LOWER(COALESCE(loss_cf.remarks, "")) like ?', [$term]);
            });
        }

        $query->select([
            'loss_cf.*',
            DB::raw("TRIM(CONCAT(COALESCE(e.fname,''),' ',COALESCE(e.mname,''),' ',COALESCE(e.lname,''))) as employee_name"),
        ]);

        $allowed = [
            'employee_name' => 'employee_name',
            'original_loss_amount' => 'original_loss_amount',
            'setoff_in_current_year' => 'setoff_in_current_year',
            'carry_forward_amount' => 'carry_forward_amount',
            'forward_upto_year' => 'forward_upto_year',
            'created_at' => 'loss_cf.created_at',
        ];
        $orderBy = $allowed[$this->sortBy] ?? 'loss_cf.created_at';
        $dir = in_array($this->sortDirection, ['asc','desc']) ? $this->sortDirection : 'desc';

        $rows = $query->orderBy($orderBy, $dir)->paginate($this->perPage);
        $this->searchCache[$cacheKey] = $rows;
        $this->lastCacheKey = $cacheKey;
        return $rows;
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Taxation/loss-cf.blade.php'), [
            'rows' => $this->tableRows,
            'lists' => $this->listsForFields,
            'sortBy' => $this->sortBy,
            'sortDirection' => $this->sortDirection,
        ]);
    }
}

