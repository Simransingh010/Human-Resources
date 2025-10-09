<?php

namespace App\Livewire\Hrms\Taxation;

use App\Models\Hrms\TaxPayment;
use App\Models\Hrms\Employee;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;

class TaxPayments extends Component
{
    use WithPagination;
    use WithFileUploads;

    public $perPage = 20;
    public $sortBy = 'payment_date';
    public $sortDirection = 'desc';
    public $search = '';

    public $listsForFields = [];

    public $formData = [
        'emp_id' => null,
        'amount' => null,
        'payment_date' => null,
        'challan_no' => '',
        'from_date' => null,
        'to_date' => null,
        'payment_type' => null,
        'paid_by' => null,
    ];

    public $challan = null;
    public $challanReceipts = [];

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
            'payment_types' => [
                'tds' => 'TDS',
                'tcs' => 'TCS',
                'self_assessment' => 'Self Assessment',
                'advance_tax' => 'Advance Tax',
            ],
            'paid_by' => [
                'employee' => 'Employee',
                'employer' => 'Employer',
            ],
        ];
    }

    protected function rules(): array
    {
        return [
            'formData.emp_id' => 'required|integer',
            'formData.amount' => 'required|numeric|min:0',
            'formData.payment_date' => 'required|date',
            'formData.challan_no' => 'nullable|string|max:50',
            'formData.from_date' => 'nullable|date',
            'formData.to_date' => 'nullable|date|after_or_equal:formData.from_date',
            'formData.payment_type' => 'nullable|string',
            'formData.paid_by' => 'nullable|string',
            'challan' => 'nullable|file|mimes:pdf|max:10240',
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
            $record = TaxPayment::create([
                'firm_id' => $firmId,
                'emp_id' => $data['emp_id'],
                'financial_year_id' => $financialYearId,
                'amount' => $data['amount'] ?? 0,
                'payment_date' => $data['payment_date'] ?? null,
                'challan_no' => $data['challan_no'] ?? null,
                'from_date' => $data['from_date'] ?? null,
                'to_date' => $data['to_date'] ?? null,
                'payment_type' => $data['payment_type'] ?? null,
                'paid_by' => $data['paid_by'] ?? null,
            ]);

            if ($this->challan) {
                $dir = "tax-payments/{$record->id}";
                $name = $this->challan->getClientOriginalName() ?: ('challan-' . time() . '.pdf');
                $this->challan->storeAs($dir, $name, 'public');
            }
        });

        $this->resetForm();
        if (method_exists($this, 'modal')) {
            $this->modal('mdl-tax-payment')->close();
        }
        \Flux\Flux::toast('Tax payment added successfully', 'success');
        $this->invalidateCache();
    }

    public function resetForm()
    {
        $this->formData = [
            'emp_id' => null,
            'amount' => null,
            'payment_date' => null,
            'challan_no' => '',
            'from_date' => null,
            'to_date' => null,
            'payment_type' => null,
            'paid_by' => null,
        ];
        $this->challan = null;
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
            'tax_payments',
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

        $query = TaxPayment::query()
            ->from('tax_payments')
            ->where('tax_payments.firm_id', $firmId);

        if (trim($this->search) !== '') {
            $term = '%' . strtolower(trim($this->search)) . '%';
            $query->where(function($q) use ($term) {
                $q->whereRaw('LOWER(COALESCE(challan_no, "")) like ?', [$term]);
            });
        }

        $allowed = [
            'amount' => 'amount',
            'payment_date' => 'payment_date',
            'challan_no' => 'challan_no',
            'payment_type' => 'payment_type',
            'paid_by' => 'paid_by',
            'created_at' => 'tax_payments.created_at',
        ];
        $orderBy = $allowed[$this->sortBy] ?? 'payment_date';
        $dir = in_array($this->sortDirection, ['asc','desc']) ? $this->sortDirection : 'desc';

        $rows = $query->orderBy($orderBy, $dir)->paginate($this->perPage);

        // Build challan receipts collection per page
        $ids = collect($rows->items())->pluck('id');
        $this->challanReceipts = $ids->mapWithKeys(function ($id) {
            $files = collect(Storage::disk('public')->files("tax-payments/{$id}"));
            $first = $files->first();
            return [$id => $first ? Storage::disk('public')->url($first) : null];
        })->toArray();

        $this->searchCache[$cacheKey] = $rows;
        $this->lastCacheKey = $cacheKey;
        return $rows;
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Taxation/tax-payments.blade.php'), [
            'rows' => $this->tableRows,
            'sortBy' => $this->sortBy,
            'sortDirection' => $this->sortDirection,
            'lists' => $this->listsForFields,
            'challanReceipts' => $this->challanReceipts,
        ]);
    }
}

