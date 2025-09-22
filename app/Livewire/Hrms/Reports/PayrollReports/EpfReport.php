<?php

namespace App\Livewire\Hrms\Reports\PayrollReports;

use App\Livewire\Hrms\Reports\PayrollReports\exports\EpfReportExport;
use App\Models\Saas\Firm;
use Illuminate\Support\Str;
use App\Models\Hrms\Employee;
use App\Models\Hrms\EmployeeJobProfile;
use App\Models\Hrms\SalaryExecutionGroup;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

        $export = $this->resolveExporter();

        return Excel::download(
            $export,
            'epf-report-' . now()->format('Ymd_His') . '.xlsx'
        );
    }

    public function exportText(): StreamedResponse
    {
        $this->validate([
            'filters.date_range.start' => 'required|date',
            'filters.date_range.end' => 'required|date|after_or_equal:filters.date_range.start',
        ]);

        $export = $this->resolveExporter();
        $rows = $export->getRows();

        $filename = 'epf-ecr-' . now()->format('Ymd_His') . '.txt';

        return response()->streamDownload(function () use ($rows) {
            // Header as per sample screenshot is not required; only data rows
            foreach ($rows as $r) {
                $line = [
                    (string) ($r['uan'] ?? ''),
                    (string) ($r['name'] ?? ''),
                    number_format((float) ($r['gross_wages'] ?? 0), 0, '.', ''),
                    number_format((float) ($r['epf_wages'] ?? 0), 0, '.', ''),
                    number_format((float) ($r['eps_wages'] ?? 0), 0, '.', ''),
                    number_format((float) ($r['edli_wages'] ?? 0), 0, '.', ''),
                    number_format((float) ($r['epf_contri'] ?? 0), 0, '.', ''),
                    number_format((float) ($r['eps_contri'] ?? 0), 0, '.', ''),
                    number_format((float) ($r['diff_contri'] ?? 0), 0, '.', ''),
                    number_format((float) ($r['ncp_days'] ?? 0), 2, '.', ''),
                    number_format((float) ($r['refund_adv'] ?? 0), 0, '.', ''),
                ];
                echo implode('#', $line) . "\r\n";
            }
        }, $filename, [
            'Content-Type' => 'text/plain',
        ]);
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Reports/PayrollReports/blades/epf-report.blade.php'));
    }

    protected function resolveExporter(): EpfReportExport
    {
        $firmId = $this->filters['firm_id'] ?? session('firm_id');
        $firm = $firmId ? Firm::find($firmId) : null;
        $short = $firm?->short_name ?: '';

        // Build StudlyCase short name, e.g., "HPCA" -> "Hpca"
        $studly = Str::studly(strtolower($short));
        $baseNamespace = __NAMESPACE__ . '\\exports\\';
        $candidate = $baseNamespace . $studly . 'EpfReportExport';

        if (class_exists($candidate)) {
            return new $candidate($this->filters);
        }

        return new EpfReportExport($this->filters);
    }
}





