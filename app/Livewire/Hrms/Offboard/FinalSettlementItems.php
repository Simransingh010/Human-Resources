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
        // Below fields are used in repeater rows instead of single inputs
        'salary_component_id' => '',
        'nature' => '',
        'amount' => '',
        'remarks' => '',
    ];

    // Repeater rows: multiple final settlement items in one submit
    public array $fsItems = [];

    public function mount()
    {
        $this->initListsForFields();

        // Set default visible fields
        $this->visibleFields = ['employee_id', 'salary_component_id', 'nature', 'amount', 'remarks'];
        $this->visibleFilterFields = ['employee_id', 'salary_component_id', 'nature'];

        // Initialize filters
        $this->filters = array_fill_keys(array_keys($this->filterFields), '');

        // Initialize one empty row for multi-entry
        if (empty($this->fsItems)) {
            $this->fsItems = [
                ['salary_component_id' => '', 'nature' => '', 'amount' => '', 'remarks' => '']
            ];
        }
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
                // Start with empty; populate on employee selection
                'salary_components' => [],
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

    // When Exit is selected, auto-fill employee and related final settlement, and load components
    public function updatedFormDataExitId($exitId): void
    {
        if (!$exitId) {
            return;
        }

        $firmId = Session::get('firm_id');
        $exit = EmployeeExit::where('firm_id', $firmId)->with('employee')->find($exitId);
        if ($exit) {
            $this->formData['employee_id'] = $exit->employee_id;

            // Pick the latest settlement for this exit if available
            $settlement = FinalSettlement::where('firm_id', $firmId)
                ->where('exit_id', $exit->id)
                ->orderByDesc('id')
                ->first();
            if ($settlement) {
                $this->formData['final_settlement_id'] = $settlement->id;
            }

            $this->loadEmployeeComponents($exit->employee_id);
        }
    }

    public function updatedFormDataEmployeeId($employeeId): void
    {
        if ($employeeId) {
            $this->loadEmployeeComponents($employeeId);
        } else {
            $this->listsForFields['salary_components'] = [];
        }
    }

    protected function loadEmployeeComponents($employeeId): void
    {
        // Filter salary components assigned to this employee
        $assigned = \App\Models\Hrms\SalaryComponentsEmployee::where('employee_id', $employeeId)
            ->with('salary_component')
            ->get();

        // Components for select [id => title]
        $this->listsForFields['salary_components'] = $assigned
            ->mapWithKeys(function ($item) {
                return [$item->salary_component->id => $item->salary_component->title];
            })
            ->toArray();

        // Component natures map [id => nature]
        $this->listsForFields['component_natures'] = $assigned
            ->mapWithKeys(function ($item) {
                return [$item->salary_component->id => $item->nature];
            })
            ->toArray();
    }

    public function availableComponents(int $currentIndex): array
    {
        $all = $this->listsForFields['salary_components'] ?? [];
        $selectedIds = collect($this->fsItems)
            ->pluck('salary_component_id')
            ->filter()
            ->values()
            ->toArray();
        $current = $this->fsItems[$currentIndex]['salary_component_id'] ?? null;
        $selectedOther = array_values(array_filter($selectedIds, fn($id) => (string)$id !== (string)$current));
        return array_filter($all, function ($title, $id) use ($selectedOther) {
            return !in_array((string)$id, array_map('strval', $selectedOther), true);
        }, ARRAY_FILTER_USE_BOTH);
    }

    public function addItem(): void
    {
        $this->fsItems[] = ['salary_component_id' => '', 'nature' => '', 'amount' => '', 'remarks' => ''];
    }

    public function removeItem($index): void
    {
        if (isset($this->fsItems[$index])) {
            unset($this->fsItems[$index]);
            $this->fsItems = array_values($this->fsItems);
        }
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
            // Repeater validation
            'fsItems' => 'required|array|min:1',
            'fsItems.*.salary_component_id' => 'required|exists:salary_components,id',
            'fsItems.*.amount' => 'required|numeric|min:0',
            'fsItems.*.remarks' => 'nullable|string|max:1000',
        ];
    }

    public function store()
    {
        $validatedData = $this->validate();

        $firmId = session('firm_id');

        if ($this->isEditing) {
            // Update a single item using the first repeater row and derive nature
            $finalSettlementItem = FinalSettlementItem::findOrFail($this->formData['id']);
            $row = $validatedData['fsItems'][0] ?? null;
            if ($row) {
                $nature = $this->listsForFields['component_natures'][$row['salary_component_id']] ??
                    (string) \App\Models\Hrms\SalaryComponentsEmployee::where('employee_id', $this->formData['employee_id'])
                        ->where('salary_component_id', $row['salary_component_id'])
                        ->value('nature');
                $finalSettlementItem->update([
                    'exit_id' => $this->formData['exit_id'],
                    'final_settlement_id' => $this->formData['final_settlement_id'],
                    'employee_id' => $this->formData['employee_id'],
                    'salary_component_id' => $row['salary_component_id'],
                    'nature' => $nature,
                    'amount' => $row['amount'],
                    'remarks' => $this->formData['remarks'] ?? null,
                ]);
            }
            // After any change, recompute the parent settlement totals
            if (!empty($this->formData['final_settlement_id'])) {
                $settlement = FinalSettlement::find($this->formData['final_settlement_id']);
                if ($settlement) {
                    $settlement->recomputeTotals();
                }
            }
            $toastMsg = 'Final settlement item updated successfully';
        } else {
            foreach ($validatedData['fsItems'] as $row) {
                $nature = $this->listsForFields['component_natures'][$row['salary_component_id']] ??
                    (string) \App\Models\Hrms\SalaryComponentsEmployee::where('employee_id', $this->formData['employee_id'])
                        ->where('salary_component_id', $row['salary_component_id'])
                        ->value('nature');

                FinalSettlementItem::create([
                    'firm_id' => $firmId,
                    'exit_id' => $this->formData['exit_id'],
                    'final_settlement_id' => $this->formData['final_settlement_id'],
                    'employee_id' => $this->formData['employee_id'],
                    'salary_component_id' => $row['salary_component_id'],
                    'nature' => $nature,
                    'amount' => $row['amount'],
                    'remarks' => $row['remarks'] ?? null,
                ]);
            }
            // Recompute totals for parent settlement after bulk insert
            if (!empty($this->formData['final_settlement_id'])) {
                $settlement = FinalSettlement::find($this->formData['final_settlement_id']);
                if ($settlement) {
                    $settlement->recomputeTotals();
                }
            }
            $toastMsg = 'Final settlement items added successfully';
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
        $this->fsItems = [
            ['salary_component_id' => '', 'nature' => '', 'amount' => '', 'remarks' => '']
        ];
    }

    public function edit($id)
    {
        $this->isEditing = true;
        $finalSettlementItem = FinalSettlementItem::findOrFail($id);
        $this->formData['id'] = $finalSettlementItem->id;
        $this->formData['exit_id'] = $finalSettlementItem->exit_id;
        $this->formData['final_settlement_id'] = $finalSettlementItem->final_settlement_id;
        $this->formData['employee_id'] = $finalSettlementItem->employee_id;
        $this->formData['remarks'] = $finalSettlementItem->remarks;

        // Load components for the employee so repeater has options and natures
        if ($finalSettlementItem->employee_id) {
            $this->loadEmployeeComponents($finalSettlementItem->employee_id);
        }

        // Prefill repeater with the existing item (component + amount)
        $this->fsItems = [
            [
                'salary_component_id' => $finalSettlementItem->salary_component_id,
                'amount' => $finalSettlementItem->amount,
                'remarks' => $finalSettlementItem->remarks,
            ]
        ];
        $this->modal('mdl-final-settlement-item')->show();
    }

    public function delete($id)
    {
        $finalSettlementItem = FinalSettlementItem::findOrFail($id);
        $settlementId = $finalSettlementItem->final_settlement_id;
        $finalSettlementItem->delete();
        
        // Clear cache to refresh lists
        $this->clearCache();
        
        // Recompute totals for parent after deletion
        if ($settlementId) {
            $settlement = FinalSettlement::find($settlementId);
            if ($settlement) {
                $settlement->recomputeTotals();
            }
        }
        
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
