<?php

namespace App\Livewire\Saas;

use App\Models\Saas\College;
use App\Models\Saas\Firm;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Cache;

class Colleges extends Component
{
    use WithPagination;

    public $perPage = 20;
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $viewMode = 'card'; // default to card view

    public $listsForFields = [];

    public $formData = [
        'name' => '',
        'firm_id' => null,
        'code' => '',
        'established_year' => null,
        'address' => '',
        'city' => '',
        'state' => '',
        'country' => '',
        'phone' => '',
        'email' => '',
        'website' => '',
        'is_inactive' => false,
    ];

    // Field configuration for form and table
    public array $fieldConfig = [
        'name' => ['label' => 'College Name', 'type' => 'text'],
        'code' => ['label' => 'College Code', 'type' => 'text'],
        'firm_id' => ['label' => 'Firm', 'type' => 'select', 'listKey' => 'firms'],
        'established_year' => ['label' => 'Established Year', 'type' => 'number'],
        'city' => ['label' => 'City', 'type' => 'text'],
        'state' => ['label' => 'State', 'type' => 'text'],
        'phone' => ['label' => 'Phone', 'type' => 'text'],
        'email' => ['label' => 'Email', 'type' => 'email'],
        'is_inactive' => ['label' => 'Status', 'type' => 'select', 'listKey' => 'status']
    ];

    // Filter fields configuration
    public array $filterFields = [
        'name' => ['label' => 'College Name', 'type' => 'text'],
        'code' => ['label' => 'College Code', 'type' => 'text'],
        'city' => ['label' => 'City', 'type' => 'text'],
        'phone' => ['label' => 'Phone', 'type' => 'text'],
        'email' => ['label' => 'Email', 'type' => 'text'],
        'is_inactive' => ['label' => 'Status', 'type' => 'select', 'listKey' => 'status']
    ];

    public array $filters = [];
    public array $visibleFields = [];
    public array $visibleFilterFields = [];

    public function mount()
    {
        $this->initListsForFields();
        $this->resetPage();
        $this->visibleFields = ['name', 'code', 'city', 'phone', 'email'];
        $this->visibleFilterFields = ['name', 'code', 'city', 'phone', 'email'];
        $this->filters = array_fill_keys(array_merge(array_keys($this->filterFields), ['colleges']), '');
        // Load viewMode from session if set
        $this->viewMode = session('colleges_view_mode', $this->viewMode);
    }

    protected function initListsForFields(): void
    {
        $this->listsForFields = [
            'status' => [
                'active' => 'Active',
                'inactive' => 'Inactive',
            ],
            'firms' => Firm::query()
                ->orderBy('name')
                ->get()
                ->pluck('name', 'id')
                ->toArray(),
        ];
    }

    protected function rules(): array
    {
        return [
            'formData.name' => 'required|string|max:255',
            'formData.code' => 'required|string|max:50|unique:college,code',
            'formData.established_year' => 'required|integer|min:1800|max:' . date('Y'),
            'formData.address' => 'required|string|max:500',
            'formData.city' => 'required|string|max:100',
            'formData.state' => 'required|string|max:100',
            'formData.country' => 'required|string|max:100',
            'formData.phone' => 'required|string|max:20',
            'formData.email' => 'required|email|max:255',
            'formData.website' => 'nullable|url|max:255',
            'formData.is_inactive' => 'boolean',
        ];
    }

    public function sort($column)
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
    }

    public function store()
    {
        $validated = $this->validate();

        // Automatically set firm_id from session
        $validated['formData']['firm_id'] = Session::get('firm_id');

        DB::transaction(function () use ($validated) {
            College::create($validated['formData']);
        });

        $this->resetForm();
        if (method_exists($this, 'modal')) {
            $this->modal('mdl-college')->close();
        }
        \Flux\Flux::toast('College added successfully', 'success');
        Cache::flush();
    }

    public function edit($id)
    {
        $college = College::findOrFail($id);
        $this->formData = [
            'name' => $college->name,
            'firm_id' => null, // Don't store firm_id in form data
            'code' => $college->code,
            'established_year' => $college->established_year,
            'address' => $college->address,
            'city' => $college->city,
            'state' => $college->state,
            'country' => $college->country,
            'phone' => $college->phone,
            'email' => $college->email,
            'website' => $college->website,
            'is_inactive' => $college->is_inactive,
        ];
        $this->editingId = $id;
    }

    public function update()
    {
        $rules = $this->rules();
        $rules['formData.code'] = 'required|string|max:50|unique:college,code,' . $this->editingId;
        
        $validated = $this->validate($rules);
        
        // Automatically set firm_id from session (shouldn't change, but for safety)
        $validated['formData']['firm_id'] = Session::get('firm_id');

        DB::transaction(function () use ($validated) {
            College::findOrFail($this->editingId)->update($validated['formData']);
        });

        $this->resetForm();
        if (method_exists($this, 'modal')) {
            $this->modal('mdl-college')->close();
        }
        \Flux\Flux::toast('College updated successfully', 'success');
        Cache::flush();
    }

    public function delete($id)
    {
        $college = College::findOrFail($id);
        $college->delete();
        
        \Flux\Flux::toast('College deleted successfully', 'success');
        Cache::flush(); // Invalidate cache after delete
    }

    public function toggleStatus($collegeId)
    {
        $college = College::findOrFail($collegeId);
        $college->is_inactive = !$college->is_inactive;
        $college->save();

        \Flux\Flux::toast(
            heading: 'Status Updated',
            text: $college->is_inactive ? 'College has been deactivated.' : 'College has been activated.'
        );
    }

    public function setViewMode($mode)
    {
        $this->viewMode = $mode;
        session(['colleges_view_mode' => $mode]);
    }

    public function applyFilters()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->filters = array_fill_keys(array_merge(array_keys($this->filterFields), ['colleges']), '');
        $this->resetPage();
    }

    public function resetForm()
    {
        $this->formData = [
            'name' => '',
            'firm_id' => null,
            'code' => '',
            'established_year' => null,
            'address' => '',
            'city' => '',
            'state' => '',
            'country' => '',
            'phone' => '',
            'email' => '',
            'website' => '',
            'is_inactive' => false,
        ];
        $this->editingId = null;
    }

    #[Computed]
    public function collegeslist()
    {
        $firmId = Session::get('firm_id');
        $cacheKey = 'collegeslist_' . $firmId . '_' . md5(json_encode($this->filters) . $this->sortBy . $this->sortDirection . request('page', 1));
        return Cache::remember($cacheKey, 60, function () use ($firmId) {
            return College::query()
                ->where('firm_id', $firmId)
                ->with(['firm'])
                ->when($this->sortBy, fn($query) => $query->orderBy($this->sortBy, $this->sortDirection))
                ->when($this->filters['colleges'], function($query, $value) {
                    $query->where(function($q) use ($value) {
                        $q->where('name', 'like', "%{$value}%")
                          ->orWhere('code', 'like', "%{$value}%")
                          ->orWhere('city', 'like', "%{$value}%")
                          ->orWhere('state', 'like', "%{$value}%");
                    });
                })
                ->when($this->filters['name'], fn($query, $value) => $query->where('name', 'like', "%{$value}%"))
                ->when($this->filters['code'], fn($query, $value) => $query->where('code', 'like', "%{$value}%"))
                ->when($this->filters['city'], fn($query, $value) => $query->where('city', 'like', "%{$value}%"))
                ->when($this->filters['phone'], fn($query, $value) => $query->where('phone', 'like', "%{$value}%"))
                ->when($this->filters['email'], fn($query, $value) => $query->where('email', 'like', "%{$value}%"))
                ->when($this->filters['is_inactive'], fn($query, $value) => $query->where('is_inactive', $value === 'inactive'))
                ->paginate(12);
        });
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Saas/colleges.blade.php'));
    }
}
