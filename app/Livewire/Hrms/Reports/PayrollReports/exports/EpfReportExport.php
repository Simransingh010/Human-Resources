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
use Illuminate\Support\Facades\DB;

class EpfReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithEvents
{
    protected $filters;
    protected $start;
    protected $end;
    protected $firmName;
    protected $rows;
    // EPF configuration (common defaults)
    protected float $epfWageCap = 15000.0; // statutory cap
    protected float $epfEmployeeRate = 0.12; // Employee EPF 12%
    protected float $epsEmployerRate = 0.0833; // Employer EPS 8.33%
    protected float $epfEmployerDiffRate = 0.0367; // Employer EPF part 3.67%

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

        // Detect Basic component ids for this firm
        $basicComponentIds = $this->detectBasicComponentIds($firmId);

        // Build EPF related columns from tracks
        return $employees->map(function ($e) use ($basicComponentIds) {
            $job = $e->emp_job_profile;
            $tracks = collect($e->payroll_tracks);

            // Identify wages and contributions (these component titles/types may vary per tenant)
            // Fallback strategy: use component_type or title keywords
            $basicAmount = 0;
            // Gross wages should be the total of all earning components before deductions
            $grossAmount = $tracks->where('nature', 'earning')->sum('amount_payable');
            if (!empty($basicComponentIds)) {
                $basicAmount = $tracks->whereIn('salary_component_id', $basicComponentIds)->sum('amount_payable');
            } else {
                // fallback to first regular
                $basic = $tracks->firstWhere('component_type', 'regular');
                $basicAmount = (float) ($basic->amount_full ?? 0);
            }

            // Derive EPF/EPS/EDLI wages from Basic subject to statutory cap
            $epfBase = min((float) $basicAmount, $this->epfWageCap);
            $epfWages = $epfBase; // generally same base for EPF
            $epsWages = $epfBase; // EPS base is capped at 15,000
            $edliWages = $epfBase; // EDLI wages typically share the same cap

            // Contributions computed from base
            $epfContri = round($epfBase * $this->epfEmployeeRate); // Employee 12%
            $epsContri = round($epfBase * $this->epsEmployerRate); // Employer 8.33%
            $diffContri = round($epfBase * $this->epfEmployerDiffRate); // Employer EPF 3.67%

            return [
                'uan' => $job->uanno ?? '',
                'name' => trim(($e->fname ?? '') . ' ' . ($e->lname ?? '')),
                'gross_wages' => (float) $grossAmount,
                'epf_wages' => (float) $epfWages,
                'eps_wages' => (float) $epsWages,
                'edli_wages' => (float) $edliWages,
                'epf_contri' => (float) $epfContri,
                'eps_contri' => (float) $epsContri,
                'diff_contri' => (float) $diffContri,
                'ncp_days' => 0.00,
                'refund_adv' => 0,
            ];
        })->values();
    }

    protected function detectBasicComponentIds(int $firmId): array
    {
        // 1) Try title pattern matching on SalaryComponent titles
        $patterns = [
            '/\\bbasic\\s*salary\\b/i',
            '/\\bbasic\\s*pay\\b/i',
            '/^basic$/i',
            '/^bp$/i',
            '/\\bbase\\s*salary\\b/i',
            '/\\bfundamental\\s*pay\\b/i',
            '/^basicpay$/i',
            '/^basicsalary$/i',
            '/^base$/i',
        ];

        $components = SalaryComponent::query()
            ->where('firm_id', $firmId)
            ->whereNull('deleted_at')
            ->get(['id','title','nature','component_type']);

        $matchedIds = [];
        foreach ($components as $c) {
            $title = strtolower(trim((string)$c->title));
            foreach ($patterns as $re) {
                if (preg_match($re, $title)) {
                    $matchedIds[] = $c->id;
                    break;
                }
            }
        }

        if (!empty($matchedIds)) {
            return array_values(array_unique($matchedIds));
        }

        // 2) Fallback: Pick earning component with highest average amount in selected period
        $fallback = PayrollComponentsEmployeesTrack::query()
            ->select('salary_component_id', DB::raw('AVG(amount_payable) as avg_amount'))
            ->where('firm_id', $firmId)
            ->whereBetween('salary_period_from', [$this->start, $this->end])
            ->groupBy('salary_component_id')
            ->orderByDesc('avg_amount')
            ->first();

        return $fallback ? [$fallback->salary_component_id] : [];
    }

    public function collection()
    {
        return $this->rows;
    }

    public function getRows(): Collection
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

