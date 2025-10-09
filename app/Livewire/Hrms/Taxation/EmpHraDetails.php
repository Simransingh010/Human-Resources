<?php

namespace App\Livewire\Hrms\Taxation;

use App\Models\Hrms\EmpHraDetail;
use App\Models\Hrms\Employee;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;

class EmpHraDetails extends Component
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
        'land_lord_name' => '',
        'landlord_pan' => '',
        'monthly_rent' => null,
        'from_date' => null,
        'to_date' => null,
        'status' => 'active',
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
            'employees' => Employee::query()
                ->where('firm_id', $firmId)
                ->orderBy('fname')
                ->limit(200)
                ->get()
                ->mapWithKeys(function ($e) {
                    $name = trim(($e->fname ?? '') . ' ' . ($e->mname ?? '') . ' ' . ($e->lname ?? ''));
                    return [$e->id => $name ?: ('EMP #' . $e->id)];
                })->toArray(),
            'status' => [
                'active' => 'Active',
                'inactive' => 'Inactive',
            ],
        ];
    }

    protected function rules(): array
    {
        return [
            'formData.emp_id' => 'required|integer',
            'formData.land_lord_name' => 'required|string|max:255',
            'formData.landlord_pan' => 'nullable|string|max:10',
            'formData.monthly_rent' => 'required|numeric|min:0',
            'formData.from_date' => 'required|date',
            'formData.to_date' => 'required|date|after_or_equal:formData.from_date',
            'formData.status' => 'required|string',
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

        DB::transaction(function () use ($validated, $firmId) {
            $data = $validated['formData'];
            $record = EmpHraDetail::create([
                'firm_id' => $firmId,
                'emp_id' => $data['emp_id'],
                'land_lord_name' => $data['land_lord_name'] ?? null,
                'landlord_pan' => $data['landlord_pan'] ?? null,
                'monthly_rent' => $data['monthly_rent'] ?? 0,
                'from_date' => $data['from_date'] ?? null,
                'to_date' => $data['to_date'] ?? null,
                'status' => $data['status'] ?? 'active',
            ]);

            if ($this->document) {
                $dir = "emp-hra-details/{$record->id}";
                $name = $this->document->getClientOriginalName() ?: ('supporting-' . time() . '.pdf');
                $this->document->storeAs($dir, $name, 'public');
            }
        });

        $this->resetForm();
        if (method_exists($this, 'modal')) {
            $this->modal('mdl-emp-hra-detail')->close();
        }
        \Flux\Flux::toast('HRA detail added successfully', 'success');
        $this->invalidateCache();
    }

    public function resetForm()
    {
        $this->formData = [
            'emp_id' => null,
            'land_lord_name' => '',
            'landlord_pan' => '',
            'monthly_rent' => null,
            'from_date' => null,
            'to_date' => null,
            'status' => 'active',
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
            'emp_hra_details',
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

        $query = EmpHraDetail::query()
            ->from('emp_hra_details')
            ->where('emp_hra_details.firm_id', $firmId);

        if (trim($this->search) !== '') {
            $term = '%' . strtolower(trim($this->search)) . '%';
            $query->where(function($q) use ($term) {
                $q->whereRaw('LOWER(COALESCE(land_lord_name, "")) like ?', [$term])
                  ->orWhereRaw('LOWER(COALESCE(landlord_pan, "")) like ?', [$term]);
            });
        }

        $allowed = [
            'land_lord_name' => 'land_lord_name',
            'landlord_pan' => 'landlord_pan',
            'monthly_rent' => 'monthly_rent',
            'from_date' => 'from_date',
            'to_date' => 'to_date',
            'status' => 'status',
            'created_at' => 'emp_hra_details.created_at',
        ];
        $orderBy = $allowed[$this->sortBy] ?? 'emp_hra_details.created_at';
        $dir = in_array($this->sortDirection, ['asc','desc']) ? $this->sortDirection : 'desc';

        $rows = $query->orderBy($orderBy, $dir)->paginate($this->perPage);

        // Build collection of document URLs without DB field
        $ids = collect($rows->items())->pluck('id');
        $this->docUrls = $ids->mapWithKeys(function ($id) {
            $files = collect(Storage::disk('public')->files("emp-hra-details/{$id}"));
            $first = $files->first();
            return [$id => $first ? Storage::disk('public')->url($first) : null];
        })->toArray();

        $this->searchCache[$cacheKey] = $rows;
        $this->lastCacheKey = $cacheKey;
        return $rows;
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Taxation/emp-hra-details.blade.php'), [
            'rows' => $this->tableRows,
            'sortBy' => $this->sortBy,
            'sortDirection' => $this->sortDirection,
            'lists' => $this->listsForFields,
            'docUrls' => $this->docUrls,
        ]);
    }
}

