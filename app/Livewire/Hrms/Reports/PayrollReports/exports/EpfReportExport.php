<?php

namespace App\Livewire\Hrms\Reports\PayrollReports\exports;

use App\Models\Hrms\Employee;
use App\Models\Hrms\PayrollComponentsEmployeesTrack;
use App\Models\Hrms\EmployeeJobProfile;
use App\Models\Hrms\SalaryComponent;
use App\Models\Saas\Firm;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EpfReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithEvents
{
    protected $filters;
    protected $start;
    protected $end;
    protected $firmName;
    protected $rows;

    public function __construct($filters)
    {
        $this->filters = $filters;
        $this->start = Carbon::parse($filters['date_range']['start'])->startOfDay();
        $this->end = Carbon::parse($filters['date_range']['end'])->endOfDay();

        $firmId = $filters['firm_id'] ?? session('firm_id');
        $firm = Firm::find($firmId);
        $this->firmName = $firm ? $firm->name : '';

        $this->rows = $this->buildRows($firmId);
    }

    protected function buildRows($firmId): Collection
    {
        $employees = Employee::with(['emp_job_profile', 'payroll_tracks' => function ($q) {
            $q->whereBetween('salary_period_from', [$this->start, $this->end]);
        }])
        ->where('firm_id', $firmId)
        ->whereHas('payroll_tracks', function ($q) {
            $q->whereBetween('salary_period_from', [$this->start, $this->end]);
        })
        ->get();

        // Filter on salary execution group if provided
        if (!empty($this->filters['salary_execution_group_id'])) {
            $employees = $employees->filter(function ($e) {
                return $e->salary_execution_groups->first()?->id == $this->filters['salary_execution_group_id'];
            });
        }

        // Build EPF related columns from tracks
        return $employees->map(function ($e) {
            $job = $e->emp_job_profile;
            $tracks = collect($e->payroll_tracks);

            // Identify wages and contributions (these component titles/types may vary per tenant)
            // Fallback strategy: use component_type or title keywords
            $basic = $tracks->firstWhere('component_type', 'regular');

            $epfWages = $tracks->filter(function ($t) {
                return stripos(optional($t->salary_component)->title ?? '', 'EPF WAGES') !== false;
            })->sum('amount_payable');

            $epsWages = $tracks->filter(function ($t) {
                return stripos(optional($t->salary_component)->title ?? '', 'EPS WAGES') !== false;
            })->sum('amount_payable');

            $edliWages = $tracks->filter(function ($t) {
                return stripos(optional($t->salary_component)->title ?? '', 'EDLI WAGES') !== false;
            })->sum('amount_payable');

            $epfContri = $tracks->filter(function ($t) {
                return ($t->component_type ?? '') === 'employee_contribution' || stripos(optional($t->salary_component)->title ?? '', 'EPF CONTRI') !== false;
            })->sum('amount_payable');

            $epsContri = $tracks->filter(function ($t) {
                return stripos(optional($t->salary_component)->title ?? '', 'EPS CONTRI') !== false;
            })->sum('amount_payable');

            $diffContri = $tracks->filter(function ($t) {
                return stripos(optional($t->salary_component)->title ?? '', 'EPF EPS DIFF') !== false;
            })->sum('amount_payable');

            return [
                'uan' => $job->uanno ?? '',
                'name' => trim(($e->fname ?? '') . ' ' . ($e->lname ?? '')),
                'gross_wages' => (float) ($basic->amount_full ?? 0),
                'epf_wages' => (float) $epfWages,
                'eps_wages' => (float) $epsWages,
                'edli_wages' => (float) $edliWages,
                'epf_contri' => (float) $epfContri,
                'eps_contri' => (float) $epsContri,
                'diff_contri' => (float) $diffContri,
                'ncp_days' => 0,
                'refund_adv' => 0,
            ];
        })->values();
    }

    public function collection()
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [
            ['UAN','Member Name','Gross Wages','EPF Wages','EPS Wages','EDLI Wages','EPF Contri Remitted','EPS Contri Remitted','EPF EPS Diff Remitted','NCP Days','Refund of Advances']
        ];
    }

    public function map($row): array
    {
        return [
            $row['uan'],
            $row['name'],
            $row['gross_wages'],
            $row['epf_wages'],
            $row['eps_wages'],
            $row['edli_wages'],
            $row['epf_contri'],
            $row['eps_contri'],
            $row['diff_contri'],
            $row['ncp_days'],
            $row['refund_adv'],
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastCol = 'K';
                // Bold header
                $sheet->getStyle("A1:{$lastCol}1")->getFont()->setBold(true);
                // Medium borders
                $sheet->getStyle("A1:{$lastCol}" . $sheet->getHighestRow())->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM);
                // Auto-size
                for ($i = 1; $i <= 11; $i++) {
                    $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }
            }
        ];
    }
}

