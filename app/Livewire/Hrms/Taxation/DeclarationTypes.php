<?php

namespace App\Livewire\Hrms\Taxation;

use App\Models\Hrms\DeclarationType;
use App\Models\Hrms\DeclarationGroup;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class DeclarationTypes extends Component
{
    use WithPagination;

    public $perPage = 20;
    public $sortBy = 'name';
    public $sortDirection = 'asc';
    public $search = '';

    public $formData = [
        'name' => '',
        'code' => '',
        'section_code' => null,
        'max_cap' => null,
        'declaration_group_id' => null,
        'proof_required' => false,
        'validation_rules' => '',
    ];

    public $listsForFields = [];

    private $searchCache = [];
    private $lastCacheKey = null;

    public function mount()
    {
        $this->listsForFields['declaration_groups'] = DeclarationGroup::query()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
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

    protected function rules(): array
    {
        return [
            'formData.name' => 'required|string|max:255',
            'formData.code' => 'required|string|max:255|unique:declaration_type,code',
            'formData.section_code' => 'nullable|integer',
            'formData.max_cap' => 'nullable|numeric|min:0',
            'formData.declaration_group_id' => 'nullable|integer|exists:declaration_group,id',
            'formData.proof_required' => 'boolean',
            'formData.validation_rules' => 'nullable|string',
        ];
    }

    public function store()
    {
        $validated = $this->validate();
        DB::transaction(function () use ($validated) {
            DeclarationType::create($validated['formData']);
        });
        $this->resetForm();
        if (method_exists($this, 'modal')) {
            $this->modal('mdl-declaration-type')->close();
        }
        \Flux\Flux::toast('Declaration Type added successfully', 'success');
        $this->invalidateCache();
    }

    public function resetForm()
    {
        $this->formData = [
            'name' => '',
            'code' => '',
            'section_code' => null,
            'max_cap' => null,
            'declaration_group_id' => null,
            'proof_required' => false,
            'validation_rules' => '',
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
            'declaration_types',
            strtolower(trim($this->search)),
            $this->sortBy,
            $this->sortDirection,
            $this->perPage,
            $page,
        ]);
        if ($this->lastCacheKey === $cacheKey && isset($this->searchCache[$cacheKey])) {
            return $this->searchCache[$cacheKey];
        }
        $query = DeclarationType::query()
            ->when(trim($this->search) !== '', function ($q) {
                $term = '%' . strtolower(trim($this->search)) . '%';
                $q->whereRaw('LOWER(name) like ?', [$term])
                  ->orWhereRaw('LOWER(code) like ?', [$term]);
            });
        $allowed = [
            'name' => 'name',
            'code' => 'code',
            'section_code' => 'section_code',
            'max_cap' => 'max_cap',
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
        return view()->file(app_path('Livewire/Hrms/Taxation/declaration-types.blade.php'), [
            'rows' => $this->tableRows,
            'sortBy' => $this->sortBy,
            'sortDirection' => $this->sortDirection,
            'lists' => $this->listsForFields,
        ]);
    }
}
