<?php

namespace App\Livewire\Hrms\Taxation;

use App\Models\Hrms\DeclarationGroup;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class DeclarationGroups extends Component
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
        'regime_id' => '',
    ];

    private $searchCache = [];
    private $lastCacheKey = null;

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
            'formData.code' => 'required|string|max:255|unique:declaration_group,code',
            'formData.section_code' => 'nullable|integer',
            'formData.max_cap' => 'nullable|numeric|min:0',
            'formData.regime_id' => 'nullable|string|max:255',
        ];
    }

    public function store()
    {
        $validated = $this->validate();
        DB::transaction(function () use ($validated) {
            DeclarationGroup::create($validated['formData']);
        });
        $this->resetForm();
        if (method_exists($this, 'modal')) {
            $this->modal('mdl-declaration-group')->close();
        }
        \Flux\Flux::toast('Declaration Group added successfully', 'success');
        $this->invalidateCache();
    }

    public function resetForm()
    {
        $this->formData = [
            'name' => '',
            'code' => '',
            'section_code' => null,
            'max_cap' => null,
            'regime_id' => '',
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
            'declaration_groups',
            strtolower(trim($this->search)),
            $this->sortBy,
            $this->sortDirection,
            $this->perPage,
            $page,
        ]);
        if ($this->lastCacheKey === $cacheKey && isset($this->searchCache[$cacheKey])) {
            return $this->searchCache[$cacheKey];
        }
        $query = DeclarationGroup::query()
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
        return view()->file(app_path('Livewire/Hrms/Taxation/declaration-groups.php'), [
            'rows' => $this->tableRows,
            'sortBy' => $this->sortBy,
            'sortDirection' => $this->sortDirection,
        ]);
    }
}
