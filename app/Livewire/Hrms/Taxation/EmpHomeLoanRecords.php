<?php

namespace App\Livewire\Hrms\Taxation;

use App\Models\Hrms\EmpHomeLoanRecord;
use App\Models\Hrms\Employee;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Session;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;

class EmpHomeLoanRecords extends Component
{
    use WithPagination;
    use WithFileUploads;

    public $perPage = 20;
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $search = '';

    public $listsForFields = [];

    public $formData = [
        'emp_id' => null,
        'lender_name' => '',
        'outstanding_principle' => null,
        'interest_paid' => null,
        'property_status' => null,
        'from_date' => null,
        'to_date' => null,
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
            'property_status' => EmpHomeLoanRecord::PROPERTY_STATUS_SELECT,
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
            'formData.lender_name' => 'required|string|max:255',
            'formData.outstanding_principle' => 'required|numeric|min:0',
            'formData.interest_paid' => 'required|numeric|min:0',
            'formData.property_status' => 'nullable|string',
            'formData.from_date' => 'nullable|date',
            'formData.to_date' => 'nullable|date|after_or_equal:formData.from_date',
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
            $record = EmpHomeLoanRecord::create([
                'firm_id' => $firmId,
                'emp_id' => $data['emp_id'],
                'financial_year_id' => $financialYearId,
                'lender_name' => $data['lender_name'] ?? null,
                'outstanding_principle' => $data['outstanding_principle'] ?? 0,
                'interest_paid' => $data['interest_paid'] ?? 0,
                'property_status' => $data['property_status'] ?? null,
                'from_date' => $data['from_date'] ?? null,
                'to_date' => $data['to_date'] ?? null,
            ]);

            if ($this->document) {
                $dir = "emp-home-loan-records/{$record->id}";
                $name = $this->document->getClientOriginalName() ?: ('supporting-' . time() . '.pdf');
                $this->document->storeAs($dir, $name, 'public');
            }
        });

        $this->resetForm();
        if (method_exists($this, 'modal')) {
            $this->modal('mdl-emp-home-loan-record')->close();
        }
        \Flux\Flux::toast('Home loan record added successfully', 'success');
        $this->invalidateCache();
    }

    public function resetForm()
    {
        $this->formData = [
            'emp_id' => null,
            'lender_name' => '',
            'outstanding_principle' => null,
            'interest_paid' => null,
            'property_status' => null,
            'from_date' => null,
            'to_date' => null,
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
            'emp_home_loan_records',
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

        $query = EmpHomeLoanRecord::query()
            ->from('emp_home_loan_records')
            ->where('emp_home_loan_records.firm_id', $firmId)
            ->when($fyId, fn($q) => $q->where('emp_home_loan_records.financial_year_id', $fyId));

        $query->leftJoin('employees as e', function($join) use ($firmId) {
            $join->on('e.id', '=', 'emp_home_loan_records.emp_id')
                 ->whereNull('e.deleted_at')
                 ->where('e.firm_id', '=', $firmId);
        });

        if (trim($this->search) !== '') {
            $term = '%' . strtolower(trim($this->search)) . '%';
            $query->where(function($q) use ($term) {
                $q->whereRaw('LOWER(COALESCE(e.fname, "")) like ?', [$term])
                  ->orWhereRaw('LOWER(COALESCE(e.mname, "")) like ?', [$term])
                  ->orWhereRaw('LOWER(COALESCE(e.lname, "")) like ?', [$term])
                  ->orWhereRaw('LOWER(COALESCE(emp_home_loan_records.lender_name, "")) like ?', [$term]);
            });
        }

        $query->select([
            'emp_home_loan_records.*',
            DB::raw("TRIM(CONCAT(COALESCE(e.fname,''),' ',COALESCE(e.mname,''),' ',COALESCE(e.lname,''))) as employee_name"),
        ]);

        $allowed = [
            'employee_name' => 'employee_name',
            'lender_name' => 'lender_name',
            'outstanding_principle' => 'outstanding_principle',
            'interest_paid' => 'interest_paid',
            'from_date' => 'from_date',
            'to_date' => 'to_date',
            'created_at' => 'emp_home_loan_records.created_at',
        ];
        $orderBy = $allowed[$this->sortBy] ?? 'emp_home_loan_records.created_at';
        $dir = in_array($this->sortDirection, ['asc','desc']) ? $this->sortDirection : 'desc';

        $rows = $query->orderBy($orderBy, $dir)->paginate($this->perPage);
        // Build collection of document URLs without DB field
        $ids = collect($rows->items())->pluck('id');
        $this->docUrls = $ids->mapWithKeys(function ($id) {
            $files = collect(Storage::disk('public')->files("emp-home-loan-records/{$id}"));
            $first = $files->first();
            return [$id => $first ? Storage::disk('public')->url($first) : null];
        })->toArray();
        $this->searchCache[$cacheKey] = $rows;
        $this->lastCacheKey = $cacheKey;
        return $rows;
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Taxation/emp-home-loan-records.blade.php'), [
            'rows' => $this->tableRows,
            'sortBy' => $this->sortBy,
            'sortDirection' => $this->sortDirection,
            'lists' => $this->listsForFields,
            'docUrls' => $this->docUrls,
        ]);
    }
}

