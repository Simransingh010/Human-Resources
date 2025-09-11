<?php

namespace App\Livewire\Hrms\Offboard;

use App\Models\Hrms\FinalSettlementItem;
use App\Models\Hrms\EmployeeExit;
use App\Models\Hrms\FinalSettlement;
use App\Models\Hrms\Employee;
use App\Models\Hrms\SalaryComponent;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Cache;
use Flux;

class FinalSettlementItems extends Component
{
    use WithPagination;

    public $perPage = 10;
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $isEditing = false;

    // Field configuration for form and table
    public array $fieldConfig = [
        'exit_id' => ['label' => 'Exit', 'type' => 'select', 'listKey' => 'exits'],
        'final_settlement_id' => ['label' => 'Final Settlement', 'type' => 'select', 'listKey' => 'final_settlements'],
        'employee_id' => ['label' => 'Employee', 'type' => 'select', 'listKey' => 'employees'],
        'salary_component_id' => ['label' => 'Salary Component', 'type' => 'select', 'listKey' => 'salary_components'],
        'nature' => ['label' => 'Nature', 'type' => 'select', 'listKey' => 'nature_options'],
        'amount' => ['label' => 'Amount', 'type' => 'number'],
        'remarks' => ['label' => 'Remarks', 'type' => 'textarea'],
    ];

    // Filter fields configuration
    public array $filterFields = [
        'exit_id' => ['label' => 'Exit', 'type' => 'select', 'listKey' => 'exits'],
        'final_settlement_id' => ['label' => 'Final Settlement', 'type' => 'select', 'listKey' => 'final_settlements'],
        'employee_id' => ['label' => 'Employee', 'type' => 'select', 'listKey' => 'employees'],
        'salary_component_id' => ['label' => 'Salary Component', 'type' => 'select', 'listKey' => 'salary_components'],
        'nature' => ['label' => 'Nature', 'type' => 'select', 'listKey' => 'nature_options'],
    ];

    public array $listsForFields = [];
    public array $filters = [];
    public array $visibleFields = [];
    public array $visibleFilterFields = [];

    public $formData = [
        'id' => null,
        'firm_id' => null,
        'exit_id' => '',
        'final_settlement_id' => '',
        'employee_id' => '',
        'salary_component_id' => '',
        'nature' => '',
        'amount' => '',
        'remarks' => '',
    ];

    public function mount()
    {
        $this->initListsForFields();

        // Set default visible fields
        $this->visibleFields = ['employee_id', 'salary_component_id', 'nature', 'amount', 'remarks'];
        $this->visibleFilterFields = ['employee_id', 'salary_component_id', 'nature'];

        // Initialize filters
        $this->filters = array_fill_keys(array_keys($this->filterFields), '');
    }

    protected function initListsForFields(): void
    {
        $firmId = Session::get('firm_id');
        $cacheKey = "final_settlement_items_lists_{$firmId}";

        // Use caching for better performance
        $this->listsForFields = Cache::remember($cacheKey, 300, function () use ($firmId) {
            return [
                'exits' => EmployeeExit::where('firm_id', $firmId)
                    ->with('employee')
                    ->get()
                    ->mapWithKeys(function ($exit) {
                        $label = "Exit #{$exit->id}";
                        if ($exit->employee) {
                            $label .= " - {$exit->employee->fname} {$exit->employee->lname}";
                        }
                        return [$exit->id => $label];
                    })
                    ->toArray(),
                'final_settlements' => FinalSettlement::where('firm_id', $firmId)
                    ->with('employee')
                    ->get()
                    ->mapWithKeys(function ($settlement) {
                        $label = "Settlement #{$settlement->id}";
                        if ($settlement->employee) {
                            $label .= " - {$settlement->employee->fname} {$settlement->employee->lname}";
                        }
                        return [$settlement->id => $label];
                    })
                    ->toArray(),
                'employees' => Employee::where('firm_id', $firmId)
                    ->where('is_inactive', false)
                    ->orderBy('fname')
                    ->get()
                    ->mapWithKeys(function ($employee) {
                        return [$employee->id => "{$employee->fname} {$employee->lname} ({$employee->employee_code})"];
                    })
                    ->toArray(),
                'salary_components' => SalaryComponent::where('firm_id', $firmId)
//                    ->where('is_inactive', false)
                    ->orderBy('title')
                    ->pluck('title', 'id')
                    ->toArray(),
                'nature_options' => [
                    'earning' => 'Earning',
                    'deduction' => 'Deduction',
                    'no_impact' => 'No Impact'
                ]
            ];
        });
    }

    public function applyFilters()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->filters = array_fill_keys(array_keys($this->filterFields), '');
        $this->resetPage();
    }

    public function toggleColumn(string $field)
    {
        if (in_array($field, $this->visibleFields)) {
            $this->visibleFields = array_filter(
                $this->visibleFields,
                fn($f) => $f !== $field
            );
        } else {
            $this->visibleFields[] = $field;
        }
    }

    public function toggleFilterColumn(string $field)
    {
        if (in_array($field, $this->visibleFilterFields)) {
            $this->visibleFilterFields = array_filter(
                $this->visibleFilterFields,
                fn($f) => $f !== $field
            );
        } else {
            $this->visibleFilterFields[] = $field;
        }
    }

    #[Computed]
    public function list()
    {
        $firmId = Session::get('firm_id');
        
        return FinalSettlementItem::query()
            ->with(['exit.employee', 'finalSettlement.employee', 'employee', 'salaryComponent'])
            ->where('firm_id', $firmId)
            ->when($this->filters['exit_id'], fn($query, $value) =>
                $query->where('exit_id', $value))
            ->when($this->filters['final_settlement_id'], fn($query, $value) =>
                $query->where('final_settlement_id', $value))
            ->when($this->filters['employee_id'], fn($query, $value) =>
                $query->where('employee_id', $value))
            ->when($this->filters['salary_component_id'], fn($query, $value) =>
                $query->where('salary_component_id', $value))
            ->when($this->filters['nature'], fn($query, $value) =>
                $query->where('nature', $value))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);
    }

    protected function rules()
    {
        return [
            'formData.exit_id' => 'required|exists:employee_exits,id',
            'formData.final_settlement_id' => 'required|exists:final_settlements,id',
            'formData.employee_id' => 'required|exists:employees,id',
            'formData.salary_component_id' => 'required|exists:salary_components,id',
            'formData.nature' => 'required|in:earning,deduction,no_impact',
            'formData.amount' => 'required|numeric|min:0',
            'formData.remarks' => 'nullable|string|max:1000'
        ];
    }

    public function store()
    {
        $validatedData = $this->validate();

        $validatedData['formData'] = collect($validatedData['formData'])
            ->map(fn($val) => $val === '' ? null : $val)
            ->toArray();

        $validatedData['formData']['firm_id'] = session('firm_id');

        if ($this->isEditing) {
            $finalSettlementItem = FinalSettlementItem::findOrFail($this->formData['id']);
            $finalSettlementItem->update($validatedData['formData']);
            $toastMsg = 'Final settlement item updated successfully';
        } else {
            FinalSettlementItem::create($validatedData['formData']);
            $toastMsg = 'Final settlement item added successfully';
        }

        // Clear cache to refresh lists
        $this->clearCache();
        
        $this->resetForm();
        $this->modal('mdl-final-settlement-item')->close();
        Flux::toast(
            variant: 'success',
            heading: 'Changes saved.',
            text: $toastMsg,
        );
    }

    public function resetForm()
    {
        $this->reset(['formData']);
        $this->isEditing = false;
    }

    public function edit($id)
    {
        $this->isEditing = true;
        $finalSettlementItem = FinalSettlementItem::findOrFail($id);
        $this->formData = $finalSettlementItem->toArray();
        $this->modal('mdl-final-settlement-item')->show();
    }

    public function delete($id)
    {
        $finalSettlementItem = FinalSettlementItem::findOrFail($id);
        $finalSettlementItem->delete();
        
        // Clear cache to refresh lists
        $this->clearCache();
        
        Flux::toast(
            variant: 'success',
            heading: 'Record Deleted.',
            text: 'Final settlement item has been deleted successfully',
        );
    }

    protected function clearCache()
    {
        $firmId = Session::get('firm_id');
        Cache::forget("final_settlement_items_lists_{$firmId}");
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Offboard/blades/final-settlement-items.blade.php'));
    }
}
