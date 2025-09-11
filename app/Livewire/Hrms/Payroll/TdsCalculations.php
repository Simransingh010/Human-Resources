<?php

namespace App\Livewire\Hrms\Payroll;

use App\Models\Hrms\EmployeeTaxRegime;
use App\Models\Hrms\Employee;
use App\Models\Hrms\TaxRegime;
use App\Models\Hrms\EmployeesSalaryExecutionGroup;
use App\Models\Hrms\PayrollSlot;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Session;
use Flux;

class TdsCalculations extends Component
{
    use WithPagination;
    protected $paginationTheme = 'tailwind';
    protected $updatesQueryString = [];
    public $perPage = 10;
    public $sortBy = 'id';
    public $sortDirection = 'desc';
    public $slotId = null;
    public $employeeIds = [];

    // Edit Modal Properties
    public $showEditModal = false;
    public $selectedRecord = null;
    public $editForm = [
        'regime_id' => '',
        'effective_from' => '',
        'effective_to' => ''
    ];

    // Field configuration for form and table
    public array $fieldConfig = [
        'employee_name' => ['label' => 'Employee', 'type' => 'text'],
        'regime_name' => ['label' => 'Tax Regime', 'type' => 'text'],
        'effective_from' => ['label' => 'Effective From', 'type' => 'date'],
        'effective_to' => ['label' => 'Effective To', 'type' => 'date']
    ];

    // Filter fields configuration
    public array $filterFields = [
        'employee_id' => ['label' => 'Employee', 'type' => 'select', 'listKey' => 'employees']
    ];

    public array $listsForFields = [];
    public array $filters = [];
    public array $visibleFields = [];
    public array $visibleFilterFields = [];

    public function mount($slotId = null)
    {
        $this->slotId = $slotId;
        
        // Get employees from the execution group if slot is provided
        if ($slotId) {
            $slot = PayrollSlot::find($slotId);
            if ($slot) {
                $this->employeeIds = EmployeesSalaryExecutionGroup::where('firm_id', Session::get('firm_id'))
                    ->where('salary_execution_group_id', $slot->salary_execution_group_id)
                    ->pluck('employee_id')
                    ->toArray();
            }
        }

        $this->initListsForFields();

        // Set default visible fields
        $this->visibleFields = [
            'employee_name',
            'regime_name',
            'effective_from',
            'effective_to'
        ];

        $this->visibleFilterFields = [
            'employee_id'
        ];

        // Initialize filters
        $this->filters = array_fill_keys(array_keys($this->filterFields), '');
    }

    protected function initListsForFields(): void
    {
        if (!empty($this->employeeIds)) {
            // Show only employees from the execution group
            $this->listsForFields['employees'] = Employee::where('firm_id', Session::get('firm_id'))
                ->whereIn('id', $this->employeeIds)
                ->where('is_inactive', false)
                ->get()
                ->mapWithKeys(function ($employee) {
                    return [$employee->id => $employee->fname . ' ' . $employee->lname];
                })
                ->toArray();
        } else {
            // Fallback to all employees with tax regimes
            $employeeIds = EmployeeTaxRegime::where('firm_id', Session::get('firm_id'))
                ->pluck('employee_id')
                ->unique();

            $this->listsForFields['employees'] = Employee::where('firm_id', Session::get('firm_id'))
                ->whereIn('id', $employeeIds)
                ->where('is_inactive', false)
                ->get()
                ->mapWithKeys(function ($employee) {
                    return [$employee->id => $employee->fname . ' ' . $employee->lname];
                })
                ->toArray();
        }

        // Get tax regimes
        $this->listsForFields['tax_regimes'] = TaxRegime::where('firm_id', Session::get('firm_id'))
            ->where('is_active', true)
            ->pluck('name', 'id')
            ->toArray();
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
        $query = EmployeeTaxRegime::query()
            ->where('firm_id', Session::get('firm_id'))
            ->when($this->filters['employee_id'], fn($query, $value) =>
            $query->where('employee_id', $value))
            ->when(!empty($this->employeeIds), fn($query) =>
            $query->whereIn('employee_id', $this->employeeIds))
            ->with(['employee', 'tax_regime']);

        // Apply pagination BEFORE get
        $paginated = $query->paginate(10);

        // Map results AFTER pagination
        $paginated->getCollection()->transform(function ($record) {
            return [
                'id' => $record->id,
                'employee_id' => $record->employee_id,
                'employee_name' => $record->employee->fname . ' ' . $record->employee->lname,
                'regime_id' => $record->regime_id,
                'regime_name' => $record->tax_regime->name,
                'effective_from' => $record->effective_from ? $record->effective_from->format('Y-m-d') : null,
                'effective_to' => $record->effective_to ? $record->effective_to->format('Y-m-d') : null
            ];
        });

        return $paginated;
    }

    public function editTaxRegime($id)
    {
        $record = EmployeeTaxRegime::with(['employee', 'tax_regime'])->find($id);
        if ($record) {
            $this->selectedRecord = [
                'id' => $record->id,
                'employee_name' => $record->employee->fname . ' ' . $record->employee->lname,
                'regime_name' => $record->tax_regime->name
            ];
            $this->editForm = [
                'regime_id' => $record->regime_id,
                'effective_from' => $record->effective_from ? $record->effective_from->format('Y-m-d') : '',
                'effective_to' => $record->effective_to ? $record->effective_to->format('Y-m-d') : ''
            ];
            $this->showEditModal = true;
        }
    }

    public function updateTaxRegime()
    {
        $this->validate([
            'editForm.regime_id' => 'required|exists:tax_regimes,id',
            'editForm.effective_from' => 'required|date',
            'editForm.effective_to' => 'nullable|date|after:editForm.effective_from',
        ]);

        try {
            $record = EmployeeTaxRegime::find($this->selectedRecord['id']);
            if ($record) {
                $record->update([
                    'regime_id' => $this->editForm['regime_id'],
                    'effective_from' => $this->editForm['effective_from'],
                    'effective_to' => $this->editForm['effective_to']
                ]);

                Flux::toast(
                    variant: 'success',
                    heading: 'Success',
                    text: 'Tax regime updated successfully.',
                );
            }
        } catch (\Exception $e) {
            Flux::toast(
                variant: 'error',
                heading: 'Error',
                text: 'Failed to update tax regime: ' . $e->getMessage(),
            );
        }

        $this->closeEditModal();
    }

    public function closeEditModal()
    {
        $this->showEditModal = false;
        $this->selectedRecord = null;
        $this->editForm = [
            'regime_id' => '',
            'effective_from' => '',
            'effective_to' => ''
        ];
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Payroll/blades/tds-calculations.blade.php'));
    }
}
