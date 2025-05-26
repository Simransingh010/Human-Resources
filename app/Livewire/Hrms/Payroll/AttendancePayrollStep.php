<?php

namespace App\Livewire\Hrms\Payroll;

use App\Models\Hrms\Employee;
use App\Models\Hrms\EmpAttendance;
use App\Models\Settings\Department;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Session;
use Carbon\Carbon;
use Flux;

class AttendancePayrollStep extends Component
{
    use WithPagination;

    public $perPage = 10;
    public $sortBy = 'work_date';
    public $sortDirection = 'desc';
    public $isEditing = false;
    public $employeeSearch = '';
    public $selectedEmployees = [];
    public $payrollSlotId;
    public $fromDate;
    public $toDate;

    // Field configuration for form and table
    public array $fieldConfig = [
        'employee_id' => ['label' => 'Employee', 'type' => 'select', 'listKey' => 'employees'],
        'work_date' => ['label' => 'Date', 'type' => 'date'],
        'attendance_status_main' => ['label' => 'Status', 'type' => 'select', 'listKey' => 'attendance_status'],
        'ideal_working_hours' => ['label' => 'Ideal Hours', 'type' => 'text'],
        'actual_worked_hours' => ['label' => 'Actual Hours', 'type' => 'text'],
        'final_day_weightage' => ['label' => 'Weightage', 'type' => 'text'],
        'attend_remarks' => ['label' => 'Remarks', 'type' => 'text'],
    ];

    // Filter fields configuration
    public array $filterFields = [
        'employee_id' => ['label' => 'Employee', 'type' => 'select', 'listKey' => 'employees'],
        'attendance_status_main' => ['label' => 'Status', 'type' => 'select', 'listKey' => 'attendance_status'],
    ];

    public array $listsForFields = [];
    public array $filters = [];
    public array $visibleFields = [];
    public array $visibleFilterFields = [];

    public $formData = [
        'id' => null,
        'firm_id' => null,
        'employee_id' => null,
        'work_date' => null,
        'attendance_status_main' => null,
        'ideal_working_hours' => null,
        'actual_worked_hours' => null,
        'final_day_weightage' => null,
        'attend_remarks' => null,
    ];

    public function mount($payrollSlotId = null, $employeeIds = [], $fromDate = null, $toDate = null)
    {
        $this->payrollSlotId = $payrollSlotId;
        $this->selectedEmployees = $employeeIds;
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
        
        $this->initListsForFields();

        // Set default visible fields
        $this->visibleFields = ['employee_id', 'work_date', 'attendance_status_main',  'attend_remarks'];
        $this->visibleFilterFields = ['employee_id', 'work_date', 'attendance_status_main'];

        // Initialize filters
        $this->filters = array_fill_keys(array_keys($this->filterFields), '');
    }

    protected function initListsForFields(): void
    {
        // Get employees for dropdown
        $this->listsForFields['employees'] = Employee::where('firm_id', Session::get('firm_id'))
            ->get()
            ->mapWithKeys(function ($employee) {
                return [$employee->id => $employee->fname . ' ' . $employee->lname];
            })
            ->toArray();

        // Define attendance status options
        $this->listsForFields['attendance_status'] = EmpAttendance::ATTENDANCE_STATUS_MAIN_SELECT;
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
        return EmpAttendance::query()
            ->where('firm_id', Session::get('firm_id'))
            ->when(!empty($this->selectedEmployees), function ($query) {
                $query->whereIn('employee_id', $this->selectedEmployees);
            })
            ->when($this->fromDate, function ($query) {
                $query->whereDate('work_date', '>=', $this->fromDate);
            })
            ->when($this->toDate, function ($query) {
                $query->whereDate('work_date', '<=', $this->toDate);
            })
            ->when($this->filters['employee_id'], fn($query, $value) =>
                $query->where('employee_id', $value))
            ->when($this->filters['attendance_status_main'], fn($query, $value) =>
                $query->where('attendance_status_main', $value))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Payroll/blades/attendance_payroll_step.blade.php'));
    }
}
