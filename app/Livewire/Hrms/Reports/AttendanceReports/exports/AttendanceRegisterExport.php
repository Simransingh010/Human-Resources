<?php

namespace App\Livewire\Hrms\Reports\AttendanceReports\Exports;

use App\Models\Hrms\Employee;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class AttendanceRegisterExport implements FromCollection, WithHeadings, WithMapping, WithEvents
{
    protected array $filters;
    protected array $dateRange = [];
    protected Carbon $start;
    protected Carbon $end;

    public function __construct(array $filters)
    {
        $this->filters = $filters;
        $this->start   = Carbon::parse($filters['date_range']['start'])->startOfDay();
        $this->end     = Carbon::parse($filters['date_range']['end'])->endOfDay();

        for ($date = $this->start->copy(); $date->lte($this->end); $date->addDay()) {
            $this->dateRange[] = $date->format('Y-m-d');
        }
    }

    public function collection()
    {
        $query = Employee::with([
            'emp_job_profile.department',
            'emp_job_profile.employment_type',
            'emp_job_profile.joblocation',
            'emp_attendances' => fn($q) => $q
                ->with('punches')
                ->whereBetween('work_date', [$this->start, $this->end]),
        ])
            ->where('firm_id', session('firm_id'));

        // apply existing filters...
        if (! empty($this->filters['employee_id'])) {
            $query->whereIn('id', $this->filters['employee_id']);
        }
        if (! empty($this->filters['department_id'])) {
            $query->whereHas('emp_job_profile.department', fn($q) =>
            $q->whereIn('id', $this->filters['department_id'])
            );
        }
        if (! empty($this->filters['joblocation_id'])) {
            $query->whereHas('emp_job_profile.joblocation', fn($q) =>
            $q->whereIn('id', $this->filters['joblocation_id'])
            );
        }
        if (! empty($this->filters['employment_type_id'])) {
            $query->whereHas('emp_job_profile.employment_type', fn($q) =>
            $q->whereIn('id', $this->filters['employment_type_id'])
            );
        }

        return $query->get();
    }

    public function headings(): array
    {
        $headers = [
            'Employee Code', 'Employee Name', 'Location', 'Department', 'Employment Type',
        ];

        foreach ($this->dateRange as $date) {
            $headers[] = Carbon::parse($date)->format('d-M');
        }

        $headers[] = 'Total Days Present';
        return $headers;
    }

    public function map($employee): array
    {
        $job = $employee->emp_job_profile;
        $base = [
            $job->employee_code ?? '',
            trim("{$employee->fname} {$employee->lname}"),
            $job->joblocation->name ?? '',
            $job->department->title ?? '',
            $job->employment_type->title ?? '',
        ];

        $totalPresent = 0;
        $atts = $employee->emp_attendances
            ->keyBy(fn($a) => $a->work_date->format('Y-m-d'));

        $cells = [];
        foreach ($this->dateRange as $date) {
            if (! isset($atts[$date])) {
                $cells[] = '';
                continue;
            }

            $attendance = $atts[$date];
            $punches    = $attendance->punches;
            $inPunch    = $punches->where('in_out','in')->sortBy('punch_datetime')->first();
            $outPunch   = $punches->where('in_out','out')->sortByDesc('punch_datetime')->first();

            // 12-hour format with AM/PM
            $inTime  = $inPunch  ? Carbon::parse($inPunch->punch_datetime)->format('h:i A') : '';
            $outTime = $outPunch ? Carbon::parse($outPunch->punch_datetime)->format('h:i A') : '';

            // decode JSON details up to city
            $decode = function(?string $json) {
                if (! $json || ! is_array($data = json_decode($json,true))) {
                    return '';
                }
                $parts = [];
                foreach (['road','neighbourhood','suburb','city'] as $k) {
                    if (!empty($data[$k])) {
                        $parts[] = $data[$k];
                        if ($k === 'city') break;
                    }
                }
                return implode(', ', $parts);
            };

            $inLoc  = $decode($inPunch->punch_details ?? null);
            $outLoc = $decode($outPunch->punch_details ?? null);

            // build a RichText cell
            $rt = new RichText();
            if ($inTime) {
                $r = $rt->createTextRun($inTime.' ');
                $r->getFont()->setBold(true);
            }
            if ($inLoc) {
                $rt->createTextRun("({$inLoc}) ");
            }
            // separator
            $sep = $rt->createTextRun('| ');
            $sep->getFont()->setBold(false);
            if ($outTime) {
                $r2 = $rt->createTextRun($outTime.' ');
                $r2->getFont()->setBold(true);
            }
            if ($outLoc) {
                $rt->createTextRun("({$outLoc})");
            }

            if ($inTime) {
                $totalPresent++;
            }

            $cells[] = $rt;
        }

        return array_merge($base, $cells, [$totalPresent]);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $maxRow = $sheet->getHighestRow();
                $maxCol = $sheet->getHighestColumn();

                // 1) Make every column ~20 characters wide...
                $sheet->getDefaultColumnDimension()->setWidth(20);

                // 2) ...and every row 20 points tall.
                $sheet->getDefaultRowDimension()->setRowHeight(20);

                // 3) Wrap text, top-align, thin borders
                $sheet->getStyle("A1:{$maxCol}{$maxRow}")
                    ->applyFromArray([
                        'alignment' => [
                            'wrapText' => true,
                            'vertical' => Alignment::VERTICAL_TOP,
                        ],
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color'       => ['argb' => 'FF000000'],
                            ],
                        ],
                    ]);

                // 4) Bold the header row
                $sheet->getStyle("A1:{$maxCol}1")
                    ->getFont()
                    ->setBold(true);
            },
        ];
    }
}
