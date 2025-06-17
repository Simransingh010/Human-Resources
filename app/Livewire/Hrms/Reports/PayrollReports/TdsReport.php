<?php

namespace App\Livewire\Hrms\Reports\PayrollReports;
use App\Livewire\Hrms\Reports\PayrollReports\exports\TdsReportExport;
//use Apppp\Livewire\Hrms\Reports\PayrollReports\exports\TdsReportExport
use App\Models\Hrms\Employee;
use App\Models\Hrms\EmployeeJobProfile;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Carbon;

class TdsReport extends Component
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
        
        // Set default date range to current month
        $this->filters['date_range'] = [
            'start' => Carbon::now()->startOfMonth()->format('Y-m-d'),
            'end' => Carbon::now()->endOfMonth()->format('Y-m-d'),
        ];
    }

    protected function initListsForFields(): void
    {
        $this->listsForFields['employees'] = Employee::select('id', 'fname', 'lname')
            ->where('firm_id', session('firm_id'))
            ->get()
            ->map(function ($employee) {
                return [
                    'id' => $employee->id,
                    'name' => trim($employee->fname . ' ' . $employee->lname)
                ];
            })
            ->pluck('name', 'id')
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

        // Add firm_id to filters
        $this->filters['firm_id'] = session('firm_id');

        // Build file name based on filters
        $parts = [];
        // Department
        if (!empty($this->filters['department_id'])) {
            $departments = $this->listsForFields['departments'];
            $deptNames = [];
            foreach ((array)$this->filters['department_id'] as $deptId) {
                if (isset($departments[$deptId])) {
                    $deptNames[] = strtolower(str_replace(' ', '_', $departments[$deptId]));
                }
            }
            if ($deptNames) {
                $parts[] = implode('_', $deptNames);
            }
        }
        // Salary Execution Group
        if (!empty($this->filters['salary_execution_group_id'])) {
            $groups = $this->listsForFields['salary_execution_groups'];
            $groupNames = [];
            foreach ((array)$this->filters['salary_execution_group_id'] as $groupId) {
                if (isset($groups[$groupId])) {
                    $groupNames[] = strtolower(str_replace(' ', '_', $groups[$groupId]));
                }
            }
            if ($groupNames) {
                $parts[] = implode('_', $groupNames);
            }
        }
        // If neither department nor group, use 'all'
        if (empty($parts)) {
            $parts[] = 'all_departments';
        }
        // Month
        $month = Carbon::parse($this->filters['date_range']['start'])->format('F');
        $month = strtolower($month);
        $parts[] = $month;
        $parts[] = 'tds_report';
        $filename = implode('_', $parts) . '.xlsx';

        return Excel::download(
            new TdsReportExport($this->filters),
            $filename
        );
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Reports/PayrollReports/blades/tds-report.blade.php'));
    }
}
