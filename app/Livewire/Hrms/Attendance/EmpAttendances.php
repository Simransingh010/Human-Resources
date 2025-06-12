<?php

namespace App\Livewire\Hrms\Attendance;

use Livewire\Component;
use App\Models\Hrms\EmpAttendance;
use App\Models\Hrms\Employee;
use App\Models\Hrms\WorkShiftDay;
use App\Models\Saas\Firm;
use Carbon\Carbon;
use Flux;

class EmpAttendances extends Component
{
    use \Livewire\WithPagination;
    public $selectedId = null;
    public array $listsForFields = [];
    public $attendanceData = [
        'id' => null,
        'firm_id' => null,
        'employee_id' => null,
        'employee_name' => '',
        'work_date' => null,
        'work_shift_day_id' => null,
        'attendance_status_main' => null,
        'attend_location_id' => null,
        'ideal_working_hours' => 0,
        'actual_worked_hours' => 0,
        'final_day_weightage' => 0,
        'attend_remarks' => null,
    ];

    public $sortBy = 'work_date';
    public $sortDirection = 'desc';
    public $isEditing = false;

    // Field configuration for form and table
    public array $fieldConfig = [
        'work_date' => ['label' => 'Work Date', 'type' => 'date'],
        'employee_id' => ['label' => 'Employee', 'type' => 'select', 'listKey' => 'employeelist'],
        'attendance_status_main' => ['label' => 'Status', 'type' => 'select', 'listKey' => 'attendance_status_main'],
        'ideal_working_hours' => ['label' => 'Ideal Hours', 'type' => 'number'],
        'actual_worked_hours' => ['label' => 'Actual Hours', 'type' => 'number'],
        'final_day_weightage' => ['label' => 'Day Weightage', 'type' => 'number'],
    ];

    // Filter fields configuration
    public array $filterFields = [
        'date_range' => ['label' => 'Date Range', 'type' => 'daterange'],
        'employees' => ['label' => 'Employees', 'type' => 'select', 'listKey' => 'employeelist'],
        'status' => ['label' => 'Status', 'type' => 'select', 'listKey' => 'attendance_status_main'],
    ];

    public array $visibleFields = [];
    public array $visibleFilterFields = [];

    public $filters = [
        'date_range' => null,
        'employees' => [],
        'status' => [],
    ];

    protected $rules = [
        'attendanceData.employee_id' => 'required|exists:employees,id',
        'attendanceData.work_date' => 'required|date',
        'attendanceData.work_shift_day_id' => 'nullable|exists:work_shift_days,id',
        'attendanceData.attendance_status_main' => 'required|integer|in:0,1,2,3,4,5,6,7,8',
        'attendanceData.attend_location_id' => 'nullable|string',
        'attendanceData.ideal_working_hours' => 'required|numeric|min:0|max:24',
        'attendanceData.actual_worked_hours' => 'required|numeric|min:0|max:24',
        'attendanceData.final_day_weightage' => 'required|numeric|min:0|max:1',
        'attendanceData.attend_remarks' => 'nullable|string|max:255',
    ];

    public function mount()
    {
        $this->resetPage();
        $this->attendanceData['firm_id'] = session('firm_id', 1);
        $this->initListsForFields();

        
        // Set default visible fields
        $this->visibleFields = ['work_date', 'employee_id', 'attendance_status_main'];
        $this->visibleFilterFields = ['date_range', 'employees', 'status'];
    }

    #[\Livewire\Attributes\Computed]
    public function attendancesList()
    {
        $query = EmpAttendance::with(['employee', 'work_shift_day'])
            ->where('firm_id', session('firm_id'))
            ->where('work_date', '<=', Carbon::today()->endOfDay()); // Show all records up to end of today

        // Date range filter
        if ($this->filters['date_range']) {
            try {
                $start = Carbon::parse($this->filters['date_range']['start'])->startOfDay();
                $end = Carbon::parse($this->filters['date_range']['end'])->endOfDay();
                
                // Ensure end date doesn't exceed today
                $end = min($end, Carbon::today()->endOfDay());
                
                $query->whereBetween('work_date', [$start, $end]);
            } catch (\Exception $e) {
                \Log::error("Invalid date range: {$this->filters['date_range']}");
            }
        }

        // Employees filter
        if (!empty($this->filters['employees'])) {
            $query->whereIn('employee_id', $this->filters['employees']);
        }

        // Status filter
        if (!empty($this->filters['status'])) {
            $query->whereIn('attendance_status_main', $this->filters['status']);
        }

        return $query
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(10);
    }

    #[\Livewire\Attributes\Computed]
    public function employeesList()
    {
        return Employee::where('firm_id', session('firm_id'))
            ->where('is_inactive', false)
            ->get()
            ->pluck('fname', 'id');
    }

    #[\Livewire\Attributes\Computed]
    public function workShiftDaysList()
    {
        return WorkShiftDay::whereHas('work_shift', function($query) {
            $query->where('firm_id', session('firm_id'));
        })->pluck('work_shift_id', 'id');
    }

    public function updatedAttendanceDataEmployeeName($value)
    {
        if (empty($value)) {
            $this->attendanceData['employee_id'] = null;
            return;
        }

        // Find employee by name
        $employee = Employee::where('firm_id', session('firm_id'))
            ->where(function($query) use ($value) {
                $query->where('fname', 'like', "%{$value}%")
                    ->orWhere('lname', 'like', "%{$value}%")
                    ->orWhereRaw("CONCAT(fname, ' ', lname) LIKE ?", ["%{$value}%"]);
            })
            ->first();

        if ($employee) {
            $this->attendanceData['employee_id'] = $employee->id;
            $this->attendanceData['employee_name'] = $employee->fname . ' ' . $employee->lname;
        } else {
            $this->attendanceData['employee_id'] = null;
        }
    }

    public function fetchAttendance($id)
    {
        $attendance = EmpAttendance::with('employee')->findOrFail($id);
        $this->attendanceData = $attendance->toArray();
        $this->attendanceData['employee_name'] = $attendance->employee->fname . ' ' . $attendance->employee->lname;
        $this->isEditing = true;
        $this->modal('mdl-emp-attendance')->show();
    }

    public function saveAttendance()
    {
        try {
            $this->validate();

            // Format work_date
            $this->attendanceData['work_date'] = Carbon::parse($this->attendanceData['work_date'])->format('Y-m-d');

            // Set firm_id
            $this->attendanceData['firm_id'] = session('firm_id');

            if ($this->isEditing) {
                EmpAttendance::findOrFail($this->attendanceData['id'])
                    ->update($this->attendanceData);
            } else {
                EmpAttendance::create($this->attendanceData);
            }

            $this->resetForm();
            $this->modal('mdl-emp-attendance')->close();

            Flux::toast(
                variant: 'success',
                position: 'top-right',
                heading: 'Success',
                text: 'Attendance record ' . ($this->isEditing ? 'updated' : 'added') . ' successfully.'
            );
        } catch (\Exception $e) {
            \Log::error('Attendance save error: ' . $e->getMessage());

            Flux::toast(
                variant: 'error',
                position: 'top-right',
                heading: 'Error',
                text: 'Failed to save: ' . $e->getMessage()
            );
        }
    }

    public function deleteAttendance($id)
    {
        try {
            EmpAttendance::findOrFail($id)->delete();

            Flux::toast(
                variant: 'success',
                position: 'top-right',
                heading: 'Success',
                text: 'Record deleted successfully.'
            );
        } catch (\Exception $e) {
            Flux::toast(
                variant: 'error',
                position: 'top-right',
                heading: 'Error',
                text: 'Failed to delete record.'
            );
        }
    }

    public function sort($column)
    {
        $this->sortDirection = $this->sortBy === $column
            ? ($this->sortDirection === 'asc' ? 'desc' : 'asc')
            : 'asc';
        $this->sortBy = $column;
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
                fn($f) => $field !== $field
            );
        } else {
            $this->visibleFilterFields[] = $field;
        }
    }

    public function applyFilters()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->filters = [
            'date_range' => null,
            'employees' => [],
            'status' => [],
        ];
        $this->resetPage();
    }

    public function resetForm()
    {
        $this->reset(['attendanceData', 'isEditing']);
        $this->attendanceData = [
            'id' => null,
            'firm_id' => session('firm_id'),
            'employee_id' => null,
            'employee_name' => '',
            'work_date' => null,
            'work_shift_day_id' => null,
            'attendance_status_main' => null,
            'attend_location_id' => null,
            'ideal_working_hours' => 0,
            'actual_worked_hours' => 0,
            'final_day_weightage' => 0,
            'attend_remarks' => null,
        ];
    }

    protected function initListsForFields(): void
    {
        $this->listsForFields['attendance_status_main'] = EmpAttendance::ATTENDANCE_STATUS_MAIN_SELECT;
        $this->listsForFields['employeelist'] = Employee::where('firm_id',session('firm_id'))->pluck('fname','id');
    }

    public function showAppSync($id)
    {
        $this->selectedId = $id;
        $this->modal('view-punches')->show();
    }
    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/blades/emp-attendances.blade.php'));
    }
}
