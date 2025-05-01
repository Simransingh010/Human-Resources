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
        $this->attendanceData['firm_id'] = session('firm_id', 1);
        $this->initListsForFields();
    }

    #[\Livewire\Attributes\Computed]
    public function attendancesList()
    {
        $query = EmpAttendance::with(['employee', 'work_shift_day'])
            ->where('firm_id', session('firm_id'));
//        dd($this->filters['date_range']);
        // Date range filter
        if ($this->filters['date_range']) {
            try {
                $start = Carbon::parse($this->filters['date_range']['start'])->startOfDay();
                $end = Carbon::parse($this->filters['date_range']['end'])->endOfDay();
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

    public function fetchAttendance($id)
    {
        $this->attendanceData = EmpAttendance::findOrFail($id)->toArray();
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
    public function applyFilters()
    {
        // Optional: log or track something
        $this->filters = $this->filters; // triggers reactivity
        $this->resetPage(); // ensure pagination resets after filter
    }
    public function clearFilters()
    {
        $this->reset('filters');
        $this->resetPage();
    }
    public function resetForm()
    {
        $this->reset(['attendanceData', 'isEditing']);
        $this->attendanceData['firm_id'] = session('firm_id');
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
