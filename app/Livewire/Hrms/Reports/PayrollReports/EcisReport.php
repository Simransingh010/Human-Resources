<?php

namespace App\Livewire\Hrms\Reports\PayrollReports;

//use App\Livewire\Hrms\Reports\PayrollReports\Exports\EcisReportExport;
use App\Livewire\Hrms\Reports\PayrollReports\exports\EcisReportExport;
use App\Models\Hrms\Employee;
use App\Models\Hrms\EmployeeJobProfile;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;
use Flux;

class ECISReport extends Component
{
    public $filters = [
        'date_range' => null,
        'employee_id' => null,
        'department_id' => null,
        'joblocation_id' => null,
        'employment_type_id' => null,
        'salary_execution_group_id' => null,
    ];
//C:\Users\Anhad Tahseen\PhpstormProjects\HRMS_12\app\Livewire\Hrms\Reports\PayrollReports\EcisReport.php
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
            new EcisReportExport($this->filters),
            'ecis-report-' . now()->format('Ymd_His') . '.xlsx'
        );
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Reports/PayrollReports/blades/ecis-report.blade.php'));
    }
}
