<?php

namespace App\Livewire\Hrms\Reports\PayrollReports;

use App\Livewire\Hrms\Reports\PayrollReports\exports\EpfReportExport;
use App\Models\Hrms\Employee;
use App\Models\Hrms\EmployeeJobProfile;
use App\Models\Hrms\SalaryExecutionGroup;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

class EpfReport extends Component
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
    }

    protected function initListsForFields(): void
    {
        $this->listsForFields['employees'] = Employee::select('id', 'fname', 'lname')
            ->where('firm_id', session('firm_id'))
            ->get()
            ->mapWithKeys(function ($e) {
                return [$e->id => trim(($e->fname ?? '') . ' ' . ($e->lname ?? ''))];
            })
            ->toArray();

        $this->listsForFields['departments'] = EmployeeJobProfile::with('department')
            ->where('firm_id', session('firm_id'))
            ->get()
            ->pluck('department.title', 'department.id')
            ->unique()
            ->filter()
            ->toArray();

        $this->listsForFields['locations'] = EmployeeJobProfile::with('joblocation')
            ->where('firm_id', session('firm_id'))
            ->get()
            ->pluck('joblocation.name', 'joblocation.id')
            ->unique()
            ->filter()
            ->toArray();

        $this->listsForFields['employment_types'] = EmployeeJobProfile::with('employment_type')
            ->where('firm_id', session('firm_id'))
            ->get()
            ->pluck('employment_type.title', 'employment_type.id')
            ->unique()
            ->filter()
            ->toArray();

        $this->listsForFields['salary_execution_groups'] = SalaryExecutionGroup::where('firm_id', session('firm_id'))
            ->where('is_inactive', false)
            ->pluck('title', 'id')
            ->toArray();
    }

    public function export()
    {
        $this->validate([
            'filters.date_range.start' => 'required|date',
            'filters.date_range.end' => 'required|date|after_or_equal:filters.date_range.start',
        ]);

        return Excel::download(
            new EpfReportExport($this->filters),
            'epf-report-' . now()->format('Ymd_His') . '.xlsx'
        );
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Reports/PayrollReports/blades/epf-report.blade.php'));
    }
}


