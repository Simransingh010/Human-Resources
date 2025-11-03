<?php

namespace App\Livewire\Hrms\Reports\AttendanceReports\exports;

use App\Models\Hrms\EmpPunch;
use App\Models\Hrms\Employee;
use App\Models\Saas\Firm;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Events\AfterSheet;

class EuAttendanceReportExport implements FromCollection, WithHeadings, WithMapping, WithEvents, WithCustomStartCell
{
    protected array $filters;
    protected Carbon $start;
    protected Carbon $end;
    protected array $dateRange = [];
    protected string $firmName = '';

    public function __construct(array $filters)
    {
        $this->filters = $filters;
        $this->start = Carbon::parse($this->filters['date_range']['start'])->startOfDay();
        $this->end = Carbon::parse($this->filters['date_range']['end'])->endOfDay();

        for ($date = $this->start->copy(); $date->lte($this->end); $date->addDay()) {
            $this->dateRange[] = $date->clone();
        }

        try {
            $firm = Firm::find(session('firm_id'));
            $this->firmName = $firm?->name ?? '';
        } catch (\Throwable $e) {
            $this->firmName = '';
        }
    }

    public function collection()
    {
        $query = Employee::with([
            'emp_job_profile.department',
            'emp_job_profile.employment_type',
            'emp_job_profile.joblocation',
        ])->where('firm_id', session('firm_id'));

        if (!empty($this->filters['department_id'])) {
            $departmentIds = (array) $this->filters['department_id'];
            $query->whereHas('emp_job_profile', function ($q) use ($departmentIds) {
                $q->whereIn('department_id', $departmentIds);
            });
        }

        if (!empty($this->filters['employee_id'])) {
            $query->whereIn('id', (array) $this->filters['employee_id']);
        }

        if (!empty($this->filters['joblocation_id'])) {
            $ids = (array) $this->filters['joblocation_id'];
            $query->whereHas('emp_job_profile', function ($q) use ($ids) {
                $q->whereIn('joblocation_id', $ids);
            });
        }

        if (!empty($this->filters['employment_type_id'])) {
            $ids = (array) $this->filters['employment_type_id'];
            $query->whereHas('emp_job_profile', function ($q) use ($ids) {
                $q->whereIn('employment_type_id', $ids);
            });
        }

        $employees = $query->get()->sortBy(fn($e) => optional($e->emp_job_profile->department)->title . ' ' . ($e->fname ?? ''));

        // Build a flattened list with department header rows
        $grouped = $employees->groupBy(fn($e) => optional($e->emp_job_profile->department)->title ?? 'Unassigned');
        $rows = collect();
        foreach ($grouped as $dept => $emps) {
            $rows->push(['__type' => 'department', 'department' => $dept]);
            foreach ($emps as $emp) {
                $rows->push(['__type' => 'employee', 'model' => $emp]);
            }
        }

        return $rows;
    }

    public function headings(): array
    {
        $headers = [
            'Biometric Code',
            'Employee Name',
            'Date of Joining',
        ];

        foreach ($this->dateRange as $date) {
            $headers[] = sprintf('[%s] %s',
                $this->dayCode($date),
                $date->format('d')
            );
        }

        // Place Status Delay Duration as the last column
        $headers[] = 'Total Delay Duration';

        return $headers;
    }

    public function     map($item): array
    {
        // Department header row
        if (is_array($item) && ($item['__type'] ?? null) === 'department') {
            $label =  ($item['department'] ?? '');
            $row = [$label];
            // pad remaining columns as blanks
            $totalCols = 4 + count($this->dateRange);
            while (count($row) < $totalCols) { $row[] = ''; }
            return $row;
        }

        $employee = is_array($item) ? ($item['model'] ?? null) : $item;
        $job = $employee->emp_job_profile;

        // Pre-fetch punches for the whole range per employee
        $punches = EmpPunch::query()
            ->where('firm_id', session('firm_id'))
            ->where('employee_id', $employee->id)
            ->whereBetween('work_date', [$this->start->copy()->startOfDay(), $this->end->copy()->endOfDay()])
            ->orderBy('punch_datetime')
            ->get()
            ->groupBy(fn($p) => Carbon::parse($p->work_date)->format('Y-m-d'));

        $row = [
            $job->biometric_emp_code ?? '',
            trim(($employee->fname ?? '') . ' ' . ($employee->lname ?? '')),
            optional($job->doh)->format('d-m-Y') ?? '',
        ];

        foreach ($this->dateRange as $date) {
            $row[] = $this->buildCellForDate($date, $punches[$date->format('Y-m-d')] ?? collect());
        }

        // Append total delay duration at the end
        $row[] = $this->buildDelaySummary($punches);

        return $row;
    }

    protected function buildCellForDate(Carbon $date, Collection $dayPunches): string
    {
        $dayCode = $date->dayOfWeek; // 0 Sun .. 6 Sat
        
        // Get only first entry (in) and last entry (out) of the day
        $firstPunch = $dayPunches->first();
        $lastPunch = $dayPunches->last();
        
        $punchStrings = [];
        
       if ($firstPunch && $lastPunch) {
           // Always show first punch as "in" regardless of actual in_out value
           $punchStrings[] = Carbon::parse($firstPunch->punch_datetime)->format('H:i') .' ';

           // Always show last punch as "out" only if it's different from first punch
           if ($lastPunch->id !== $firstPunch->id) {
               $punchStrings[] = Carbon::parse($lastPunch->punch_datetime)->format('H:i') . ' ';
           }
       }
       

        [$status, $firstIn, $lastOut] = $this->deriveStatus($date, $dayPunches);

        if (empty($punchStrings) && ($dayCode === 0)) {
            return 'WO';
        }

        if (!empty($punchStrings)) {
            $cell = '';
            if ($status) {
                $cell = $status . "\n";
            }
            $cell .= implode("\n", $punchStrings);
            return $cell;
        }

        return $status ?: '';
    }

    protected function deriveStatus(Carbon $date, Collection $dayPunches): array
    {
        $officialIn = Carbon::parse($date->format('Y-m-d') . ' 09:00:00');
        $bufferEnd = $officialIn->copy()->addMinutes(20);
        $halfDayCutoff = Carbon::parse($date->format('Y-m-d') . ' 13:00:00');
        $officialOut = Carbon::parse($date->format('Y-m-d') . ' 17:00:00');

        // Get first IN punch and last OUT punch specifically
        $firstInPunch = $dayPunches->firstWhere('in_out', 'I');
        $lastOutPunch = $dayPunches->where('in_out', 'O')->last();
        
        // Fallback: if no specific IN/OUT punches, use first and last punches
        $firstIn = $firstInPunch?->punch_datetime ?? $dayPunches->first()?->punch_datetime;
        $lastOut = $lastOutPunch?->punch_datetime ?? $dayPunches->last()?->punch_datetime;

        $firstInC = $firstIn ? Carbon::parse($firstIn) : null;
        $lastOutC = $lastOut ? Carbon::parse($lastOut) : null;

        // Sunday handling
        if ($date->dayOfWeek === 0) {
            if ($firstInC) {
                return ['WOP', $firstInC, $lastOutC];
            }
            return ['WO', $firstInC, $lastOutC];
        }

        // No punches
        if (!$firstInC && !$lastOutC) {
            return ['A', null, null];
        }

        // In but no out
        if ($firstInC && !$lastOutC) {
            return ['A', $firstInC, null];
        }

        // Out but no in
        if (!$firstInC && $lastOutC) {
            return ['A', null, $lastOutC];
        }

        // Full status rules based on first IN and last OUT
        if ($firstInC && $lastOutC) {
            // Present: In by 9:00 AM and out by 5:00 PM
            if ($firstInC->lte($officialIn) && $lastOutC->gte($officialOut)) {
                return ['P', $firstInC, $lastOutC];
            }

            // Present: In by 9:20 AM (with buffer) and out by 5:00 PM
            if ($firstInC->gt($officialIn) && $firstInC->lte($bufferEnd) && $lastOutC->gte($officialOut)) {
                return ['P', $firstInC, $lastOutC];
            }

            // Half Day: In after 9:20 AMin th but out after 5:00 PM (only if first-in is on/before 5:00 PM)
            if ($firstInC->gt($bufferEnd) && $firstInC->lte($officialOut) && $lastOutC->gte($officialOut)) {
                return ['H', $firstInC, $lastOutC];
            }

            // Absent: In after 5:00 PM (very late entry) - any entry after 5:00 PM should be marked as Absent
            if ($firstInC->gt($officialOut)) {
                return ['A', $firstInC, $lastOutC];
            }

            // Absent: In by 9:00 AM but out before 1:00 PM (half day cutoff)
            if ($firstInC->lte($officialIn) && $lastOutC->lt($halfDayCutoff)) {
                return ['A', $firstInC, $lastOutC];
            }

            // Half Day: In by 9:00 AM but out before 5:00 PM (after half day cutoff)
            if ($firstInC->lte($officialIn) && $lastOutC->lt($officialOut)) {
                if ($lastOutC->lt($halfDayCutoff)) {
                    return ['A', $firstInC, $lastOutC];
                }
                return ['H', $firstInC, $lastOutC];
            }

            // Half Day: In after 9:00 AM and out before 5:00 PM (after half day cutoff)
            if ($firstInC->gt($officialIn) && $lastOutC->lt($officialOut)) {
                if ($lastOutC->lt($halfDayCutoff)) {
                    return ['A', $firstInC, $lastOutC];
                }
                return ['H', $firstInC, $lastOutC];
            }
        }

        return ['', $firstInC, $lastOutC];
    }

    protected function buildDelaySummary($groupedPunches): string
    {
        $totalMinutesLate = 0;
        foreach ($this->dateRange as $date) {
            $day = $date->format('Y-m-d');
            $punches = $groupedPunches[$day] ?? collect();
            if ($punches->isEmpty()) {
                continue;
            }

            // Consider only a TRUE IN punch (case-insensitive: I/in/IN); ignore if absent
            $firstInPunch = $punches->first(function ($p) {
                $io = strtoupper(trim((string) ($p->in_out ?? '')));
                return $io === 'I' || $io === 'IN';
            });
            $firstIn = $firstInPunch?->punch_datetime;
            if (!$firstIn) {
                continue;
            }

            // Count lateness only when first IN is between 09:00 and 09:20 inclusive
            $firstInC = Carbon::parse($firstIn);
            $officialIn = Carbon::parse($day . ' 09:00:00');
            $bufferEnd = $officialIn->copy()->addMinutes(20);
            if ($firstInC->gt($officialIn) && $firstInC->lte($bufferEnd)) {
                $totalMinutesLate += $officialIn->diffInMinutes($firstInC);
            }
        }

        $rounded = (int) round($totalMinutesLate);
        return $rounded > 0 ? ($rounded . ' min') : '';
    }

    protected function dayCode(Carbon $date): string
    {
        return match ($date->dayOfWeek) {
            1 => 'M',
            2 => 'T',
            3 => 'W',
            4 => 'Th',
            5 => 'F',
            6 => 'St',
            0 => 'S',
        };
    }

    public function startCell(): string
    {
        // Leave two rows for headings (title) before the column headers
        return 'A3';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $monthLabel = strtoupper($this->start->format('F Y'));
                $title = 'ATTENDANCE OF FACULTY/STAFF OF ' . strtoupper($this->firmName) . ' FOR THE MONTH OF ' . $monthLabel;

                // Determine how many columns we have to merge across
                $totalColumns = 4 + count($this->dateRange);
                // Convert column count to Excel letter range
                $endColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($totalColumns);

                $sheet = $event->sheet->getDelegate();
                $sheet->setCellValue('A1', $title);
                $sheet->mergeCells('A1:' . $endColumn . '1');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

                // Header row style (A3)
                $headerRange = 'A3:' . $endColumn . '3';
                $sheet->getStyle($headerRange)->getFont()->setBold(true);
                $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle($headerRange)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFEAEAEA');

                // Column widths
                $sheet->getColumnDimension('A')->setWidth(16); // Biometric Code
                $sheet->getColumnDimension('B')->setWidth(26); // Employee Name
                $sheet->getColumnDimension('C')->setWidth(16); // DOJ
                // Date columns from D to the second-last column
                for ($i = 4; $i < $totalColumns; $i++) {
                    $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
                    $sheet->getColumnDimension($col)->setWidth(14);
                }
                // Last column is Status Delay Duration
                $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($totalColumns);
                $sheet->getColumnDimension($lastCol)->setWidth(28);

                // Wrap text and alignment for entire data area
                $lastRow = $sheet->getHighestRow();
                $dataRange = 'A3:' . $endColumn . $lastRow;
                $sheet->getStyle($dataRange)->getAlignment()->setWrapText(true);
                $sheet->getStyle($dataRange)->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

                // Border around data cells
                $sheet->getStyle($dataRange)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);



                // Format department header rows: bold, merged across columns, shaded
                for ($row = 4; $row <= $lastRow; $row++) {
                    $a = (string) $sheet->getCell('A' . $row)->getValue();
                    $b = (string) $sheet->getCell('B' . $row)->getValue();
                    $c = (string) $sheet->getCell('C' . $row)->getValue();
                    $d = (string) $sheet->getCell('D' . $row)->getValue();
                    $isDeptRow = ($a !== '') && ($b === '') && ($c === '') && ($d === '');
                    if ($isDeptRow) {
                        $range = 'A' . $row . ':' . $endColumn . $row;
                        $sheet->mergeCells($range);
                        $sheet->getStyle($range)->getFont()->setBold(true)->setSize(12);
                        $sheet->getStyle($range)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                            ->getStartColor()->setARGB('FFF5F5F5');
                        $sheet->getRowDimension($row)->setRowHeight(22);
                    }
                }
            }
        ];
    }
}


