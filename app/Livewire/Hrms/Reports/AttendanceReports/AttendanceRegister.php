<?php

namespace App\Livewire\Hrms\Reports\AttendanceReports;


use App\Livewire\Hrms\Reports\AttendanceReports\exports\AttendanceRegisterExport;
use App\Models\Hrms\EmpAttendance;
use App\Models\Hrms\Employee;
use App\Models\Hrms\EmployeeJobProfile;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Carbon;
use Flux;

class AttendanceRegister extends Component
{

    public $filters = [
        'date_range' => null,
        'employee_id' => null,
        'department_id' => null,
        'joblocation_id' => null,
        'employment_type_id' => null,
    ];

    public array $listsForFields = [];

    public function mount()
    {
        $this->initListsForFields();
        $this->filters['date_from'] = now()->startOfMonth()->format('Y-m-d');
        $this->filters['date_to'] = now()->format('Y-m-d');
    }

    protected function initListsForFields(): void
    {
        $this->listsForFields['employees'] = Employee::select('id', 'fname', 'lname')->where('firm_id',session('firm_id'))
            ->get()
            ->pluck('fname', 'id')
            ->toArray();

        $this->listsForFields['departments'] = EmployeeJobProfile::with('department')->where('firm_id',session('firm_id'))
            ->get()
            ->pluck('department.title', 'department.id')
            ->unique()
            ->filter()
            ->toArray();

        $this->listsForFields['locations'] = EmployeeJobProfile::with('joblocation')->where('firm_id',session('firm_id'))
            ->get()
            ->pluck('joblocation.name', 'joblocation.id')
            ->unique()
            ->filter()
            ->toArray();

        $this->listsForFields['employment_types'] = EmployeeJobProfile::with('employment_type')->where('firm_id',session('firm_id'))
            ->get()
            ->pluck('employment_type.title', 'employment_type.id')
            ->unique()
            ->filter()
            ->toArray();
    }

    public function export()
    {
        $validated = $this->validate([
            'filters.date_range.start' => 'required|date',
            'filters.date_range.end' => 'required|date|after_or_equal:filters.date_range.start',
        ]);

        return Excel::download(
            new AttendanceRegisterExport($this->filters),
            'attendance-register-' . now()->format('Ymd_His') . '.xlsx'
        );
    }


    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Reports/AttendanceReports/blades/attendance-register.blade.php'));
    }
}
