<?php

namespace App\Livewire\Hrms\Payroll;

use App\Models\Hrms\Employee;
use App\Models\Hrms\EmployeeTaxRegime;
use App\Models\Hrms\TaxRegime;
use App\Models\Settings\Department;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Session;
use Carbon\Carbon;
use Flux;

class EmployeesTaxRegime extends Component
{
    use WithPagination;

    public $perPage = 10;
    public $sortBy = 'effective_from';
    public $sortDirection = 'desc';
    public $isEditing = false;
    public $selectedRegime = null;
    public $employeeSearch = '';
    public $selectedEmployees = [];
    public $regimeId = null;

    // Field configuration for form and table
    public array $fieldConfig = [
        'employee_id' => ['label' => 'Employee', 'type' => 'select', 'listKey' => 'employees'],
        'regime_id' => ['label' => 'Tax Regime', 'type' => 'select', 'listKey' => 'tax_regimes'],
        'effective_from' => ['label' => 'Effective From', 'type' => 'date'],
        'effective_to' => ['label' => 'Effective To', 'type' => 'date'],
    ];

    // Filter fields configuration
    public array $filterFields = [
        'employee_id' => ['label' => 'Employee', 'type' => 'select', 'listKey' => 'employees'],
        'regime_id' => ['label' => 'Tax Regime', 'type' => 'select', 'listKey' => 'tax_regimes'],
        'effective_from' => ['label' => 'Effective From', 'type' => 'date'],
    ];

    public array $listsForFields = [];
    public array $filters = [];
    public array $visibleFields = [];
    public array $visibleFilterFields = [];

    public $formData = [
        'id' => null,
        'firm_id' => null,
        'employee_id' => null,
        'regime_id' => null,
        'effective_from' => null,
        'effective_to' => null,
    ];

    public function mount($regimeId = null)
    {
        $this->regimeId = $regimeId;
        $this->initListsForFields();

        // Set default visible fields
        $this->visibleFields = ['employee_id', 'regime_id', 'effective_from', 'effective_to'];
        $this->visibleFilterFields = ['employee_id', 'regime_id', 'effective_from'];

        // Initialize filters
        $this->filters = array_fill_keys(array_keys($this->filterFields), '');

        if ($regimeId) {
            $this->selectedRegime = $regimeId;
            $this->formData['regime_id'] = $regimeId;
        }
    }

    protected function initListsForFields(): void
    {
        // Get tax regimes for dropdown
        $this->listsForFields['tax_regimes'] = TaxRegime::where('firm_id', Session::get('firm_id'))
            ->pluck('name', 'id')
            ->toArray();

        // Get employees for dropdown
        $this->listsForFields['employees'] = Employee::where('firm_id', Session::get('firm_id'))

            ->get()
            ->mapWithKeys(function ($employee) {
                return [$employee->id => $employee->fname . ' ' . $employee->lname];
            })
            ->toArray();
    }

    #[Computed]
    public function filteredDepartmentsWithEmployees()
    {
        return Department::where('firm_id', Session::get('firm_id'))
            ->with([
                'employees' => function ($query) {
                    $query->when($this->employeeSearch, function ($query) {
                        $search = '%' . $this->employeeSearch . '%';
                        $query->where(function ($q) use ($search) {
                            $q->where('fname', 'like', $search)
                                ->orWhere('lname', 'like', $search)
                                ->orWhere('email', 'like', $search)
                                ->orWhere('phone', 'like', $search);
                        });
                    });
                }
            ])
            ->get()
            ->map(function ($department) {
                return [
                    'id' => $department->id,
                    'title' => $department->title,
                    'employees' => $department->employees->map(function ($employee) {
                        return [
                            'id' => $employee->id,
                            'fname' => $employee->fname,
                            'lname' => $employee->lname,
                            'email' => $employee->email,
                            'phone' => $employee->phone,
                        ];
                    })->toArray(),
                ];
            })
            ->filter(function ($department) {
                return count($department['employees']) > 0;
            })
            ->values()
            ->toArray();
    }

    public function selectAllEmployees($departmentId)
    {
        $department = collect($this->filteredDepartmentsWithEmployees)
            ->firstWhere('id', $departmentId);

        if ($department) {
            $employeeIds = collect($department['employees'])->pluck('id')->toArray();
            $this->selectedEmployees = array_unique(array_merge($this->selectedEmployees, $employeeIds));
        }
    }

    public function deselectAllEmployees($departmentId)
    {
        $department = collect($this->filteredDepartmentsWithEmployees)
            ->firstWhere('id', $departmentId);

        if ($department) {
            $employeeIds = collect($department['employees'])->pluck('id')->toArray();
            $this->selectedEmployees = array_diff($this->selectedEmployees, $employeeIds);
        }
    }

    public function selectAllEmployeesGlobal()
    {
        $allEmployeeIds = collect($this->filteredDepartmentsWithEmployees)
            ->pluck('employees.*.id')
            ->flatten()
            ->toArray();
        $this->selectedEmployees = array_unique($allEmployeeIds);
    }

    public function deselectAllEmployeesGlobal()
    {
        $this->selectedEmployees = [];
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
        return EmployeeTaxRegime::query()
            ->where('firm_id', Session::get('firm_id'))
            ->when($this->filters['employee_id'], fn($query, $value) =>
                $query->where('employee_id', $value))
            ->when($this->filters['regime_id'], fn($query, $value) =>
                $query->where('regime_id', $value))
            ->when($this->filters['effective_from'], fn($query, $value) =>
                $query->whereDate('effective_from', '>=', $value))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);
    }

    protected function rules()
    {
        return [
            'selectedRegime' => 'required|exists:tax_regimes,id',
            'selectedEmployees' => 'required|array|min:1',
            'selectedEmployees.*' => 'exists:employees,id',
            'formData.effective_from' => 'required|date',
            'formData.effective_to' => 'nullable|date|after:formData.effective_from',
        ];
    }

    public function store()
    {
        $validatedData = $this->validate();

        $firmId = session('firm_id');
        $effectiveFrom = Carbon::parse($validatedData['formData']['effective_from']);
        $effectiveTo = $validatedData['formData']['effective_to']
            ? Carbon::parse($validatedData['formData']['effective_to'])
            : null;

        // Loop through selected employees and create/update assignments
        foreach ($validatedData['selectedEmployees'] as $employeeId) {
            EmployeeTaxRegime::create([
                'firm_id' => $firmId,
                'employee_id' => $employeeId,
                'regime_id' => $validatedData['selectedRegime'],
                'effective_from' => $effectiveFrom,
                'effective_to' => $effectiveTo,
            ]);
        }

        $this->resetForm();
        $this->modal('mdl-employee-assignment')->close();
        Flux::toast(
            variant: 'success',
            heading: 'Changes saved.',
            text: count($validatedData['selectedEmployees']) . ' employees assigned successfully.',
        );
    }

    public function resetForm()
    {
        $this->reset(['formData', 'selectedEmployees']);
        if ($this->regimeId) {
            $this->formData['regime_id'] = $this->regimeId;
        }
        $this->isEditing = false;
    }

    public function edit($id)
    {
        $this->isEditing = true;
        $employeeTaxRegime = EmployeeTaxRegime::findOrFail($id);
        $this->formData = $employeeTaxRegime->toArray();
        $this->selectedRegime = $employeeTaxRegime->regime_id;
        $this->selectedEmployees = [$employeeTaxRegime->employee_id];
        $this->modal('mdl-employee-assignment')->show();
    }

    public function delete($id)
    {
        $employeeTaxRegime = EmployeeTaxRegime::findOrFail($id);
        $employeeTaxRegime->delete();

        Flux::toast(
            variant: 'success',
            heading: 'Record Deleted.',
            text: 'Employee tax regime assignment has been removed successfully',
        );
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Payroll/blades/employees-tax-regime.blade.php'));
    }
}
