<?php

namespace App\Livewire\Hrms\Reports\AttendanceReports;

use App\Livewire\Hrms\Reports\AttendanceReports\exports\LeaveSummaryExport;
use App\Models\Saas\Firm;
use App\Models\Hrms\Employee;
use App\Models\Hrms\EmployeeJobProfile;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;

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
        $export = $this->resolveExporter();

        return Excel::download(
            $export,
            'leave-summary-' . now()->format('Ymd_His') . '.xlsx'
        );
    }

    public function render()
    {
        $firmId = $this->filters['firm_id'] ?? session('firm_id');
        $firm = $firmId ? \App\Models\Saas\Firm::find($firmId) : null;
        $short = strtoupper((string)($firm?->short_name ?? ''));

        $blade = $short === 'HPCA'
            ? 'leave-summary-report-hpca.blade.php'
            : 'leave-summary-report.blade.php';

        return view()->file(app_path('Livewire/Hrms/Reports/AttendanceReports/blades/' . $blade));
    }

    protected function resolveExporter(): object
    {
        $firmId = $this->filters['firm_id'] ?? session('firm_id');
        $firm = $firmId ? Firm::find($firmId) : null;
        $short = $firm?->short_name ?: '';

        $studly = Str::studly(strtolower($short));
        $baseNamespace = __NAMESPACE__ . '\\exports\\';
        $candidate = $baseNamespace . $studly . 'LeaveSummaryReport';

        if (class_exists($candidate)) {
            return new $candidate($this->filters);
        }

        return new LeaveSummaryExport($this->filters);
    }
}


