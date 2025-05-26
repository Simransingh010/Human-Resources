<?php

namespace App\Livewire\Hrms\Reports\PayrollReports;

use App\Livewire\Hrms\Reports\PayrollReports\exports\PayrollSummaryExport;
use App\Models\Hrms\EmpAttendance;
use App\Models\Hrms\Employee;
use App\Models\Hrms\EmployeeJobProfile;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Carbon;
use Flux;


//D:\HRMS_12\app\Livewire\Hrms\Reports\AttendanceReports\exports\PayrollSummaryExport.php
//use function App\Livewire\Hrms\Reports\PayrollReports\app_path;
//use function App\Livewire\Hrms\Reports\PayrollReports\now;
//use function App\Livewire\Hrms\Reports\PayrollReports\session;
//use function App\Livewire\Hrms\Reports\PayrollReports\view;

class PayrollReports extends Component
{

    public $filters = [
        'date_range' => null,
        'employee_id' => null,
        'department_id' => null,
        'joblocation_id' => null,
        'employment_type_id' => null,
        'salary_execution_group_id' => null,
    ];

    public array $listsForFields = [];

    public function mount()
    {
        $this->initListsForFields();
        $this->filters['date_from'] = '2025-04-01';
        $this->filters['date_to'] = '2025-04-30';
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

        $this->listsForFields['salary_execution_groups'] = \App\Models\Hrms\SalaryExecutionGroup::where('firm_id', session('firm_id'))
            ->where('is_inactive', false)
            ->pluck('title', 'id')
            ->toArray();
    }

    public function export()
    {
        $validated = $this->validate([
            'filters.date_range.start' => 'required|date',
            'filters.date_range.end' => 'required|date|after_or_equal:filters.date_range.start',
        ]);

        return Excel::download(
            new PayrollSummaryExport($this->filters),
            'payroll-report-' . now()->format('Ymd_His') . '.xlsx'
        );
    }


    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Reports/PayrollReports/blades/payroll-reports.blade.php'));
    }
}
