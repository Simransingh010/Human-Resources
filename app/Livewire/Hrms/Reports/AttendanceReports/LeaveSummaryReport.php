<?php

namespace App\Livewire\Hrms\Reports\AttendanceReports;

use App\Livewire\Hrms\Reports\AttendanceReports\exports\LeaveSummaryExport;
use App\Models\Hrms\Employee;
use App\Models\Hrms\EmployeeJobProfile;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

class LeaveSummaryReport extends Component
{
    public $filters = [
        'employee_id' => null,
        'department_id' => null,
        'joblocation_id' => null,
        'employment_type_id' => null,
    ];

    public array $listsForFields = [];

    public function mount(): void
    {
        $this->initListsForFields();
    }

    protected function initListsForFields(): void
    {
        $this->listsForFields['employees'] = Employee::select('id', 'fname', 'lname')
            ->where('firm_id', session('firm_id'))
            ->get()
            ->mapWithKeys(function ($e) {
                $name = trim(($e->fname ?? '') . ' ' . ($e->lname ?? ''));
                return [$e->id => $name !== '' ? $name : 'Employee #' . $e->id];
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
    }

    public function export()
    {
        return Excel::download(
            new LeaveSummaryExport($this->filters),
            'leave-summary-' . now()->format('Ymd_His') . '.xlsx'
        );
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Reports/AttendanceReports/blades/leave-summary-report.blade.php'));
    }
}


