<?php

namespace App\Livewire\Hrms\Taxation;

use App\Models\Hrms\EmpItrReturn;
use App\Models\Hrms\Employee;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;

class EmpItrReturns extends Component
{
    use WithPagination;
    use WithFileUploads;

    public $perPage = 20;
    public $sortBy = 'date_filed';
    public $sortDirection = 'desc';
    public $search = '';

    public $listsForFields = [];

    public $formData = [
        'emp_id' => null,
        'itr_type' => null,
        'date_filed' => null,
        'acknowledgement_no' => '',
        'status' => 'filled',
        'filling_json' => null,
    ];

    public $document = null;
    public $docUrls = [];

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
            'itr_types' => EmpItrReturn::ITR_TYPE_SELECT,
            'status' => EmpItrReturn::STATUS_SELECT,
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
            'formData.itr_type' => 'required|string',
            'formData.date_filed' => 'required|date',
            'formData.acknowledgement_no' => 'nullable|string|max:50',
            'formData.status' => 'required|string',
            'formData.filling_json' => 'nullable',
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
            $record = EmpItrReturn::create([
                'firm_id' => $firmId,
                'emp_id' => $data['emp_id'],
                'financial_year_id' => $financialYearId,
                'itr_type' => $data['itr_type'],
                'date_filed' => $data['date_filed'] ?? null,
                'acknowledgement_no' => $data['acknowledgement_no'] ?? null,
                'filling_json' => $data['filling_json'] ?? null,
                'status' => $data['status'] ?? 'filled',
            ]);

            if ($this->document) {
                $dir = "emp-itr-returns/{$record->id}";
                $name = $this->document->getClientOriginalName() ?: ('itr-' . time() . '.pdf');
                $this->document->storeAs($dir, $name, 'public');
            }
        });

        $this->resetForm();
        if (method_exists($this, 'modal')) {
            $this->modal('mdl-emp-itr-return')->close();
        }
        \Flux\Flux::toast('ITR return added successfully', 'success');
        $this->invalidateCache();
    }

    public function resetForm()
    {
        $this->formData = [
            'emp_id' => null,
            'itr_type' => null,
            'date_filed' => null,
            'acknowledgement_no' => '',
            'status' => 'filled',
            'filling_json' => null,
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
        $page = $this->getPage();
        $cacheKey = implode('|', [
            'emp_itr_returns',
            $firmId,
            strtolower(trim($this->search)),
            $this->sortBy,
            $this->sortDirection,
            $this->perPage,
            $page,
        ]);

        if ($this->lastCacheKey === $cacheKey && isset($this->searchCache[$cacheKey])) {
            return $this->searchCache[$cacheKey];
        }

        $query = EmpItrReturn::query()
            ->from('emp_itr_returns')
            ->where('emp_itr_returns.firm_id', $firmId);

        if (trim($this->search) !== '') {
            $term = '%' . strtolower(trim($this->search)) . '%';
            $query->where(function($q) use ($term) {
                $q->whereRaw('LOWER(COALESCE(acknowledgement_no, "")) like ?', [$term])
                  ->orWhereRaw('LOWER(COALESCE(itr_type, "")) like ?', [$term])
                  ->orWhereRaw('LOWER(COALESCE(status, "")) like ?', [$term]);
            });
        }

        $allowed = [
            'itr_type' => 'itr_type',
            'date_filed' => 'date_filed',
            'acknowledgement_no' => 'acknowledgement_no',
            'status' => 'status',
            'created_at' => 'emp_itr_returns.created_at',
        ];
        $orderBy = $allowed[$this->sortBy] ?? 'date_filed';
        $dir = in_array($this->sortDirection, ['asc','desc']) ? $this->sortDirection : 'desc';

        $rows = $query->orderBy($orderBy, $dir)->paginate($this->perPage);

        // Build document URLs collection (first PDF per record)
        $ids = collect($rows->items())->pluck('id');
        $this->docUrls = $ids->mapWithKeys(function ($id) {
            $files = collect(Storage::disk('public')->files("emp-itr-returns/{$id}"));
            $first = $files->first();
            return [$id => $first ? Storage::disk('public')->url($first) : null];
        })->toArray();

        $this->searchCache[$cacheKey] = $rows;
        $this->lastCacheKey = $cacheKey;
        return $rows;
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Taxation/emp-itr-returns.blade.php'), [
            'rows' => $this->tableRows,
            'sortBy' => $this->sortBy,
            'sortDirection' => $this->sortDirection,
            'lists' => $this->listsForFields,
            'docUrls' => $this->docUrls,
        ]);
    }
}

