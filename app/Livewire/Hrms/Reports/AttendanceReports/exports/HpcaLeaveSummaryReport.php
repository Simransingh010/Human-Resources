<?php

namespace App\Livewire\Hrms\Reports\AttendanceReports\exports;

use App\Models\Hrms\EmpAttendance;
use App\Models\Hrms\EmpLeaveBalance;
use App\Models\Hrms\Employee;
use App\Models\Hrms\EmployeeJobProfile;
use App\Models\Hrms\FlexiWeekOff;
use App\Models\Hrms\Holiday;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class HpcaLeaveSummaryReport implements FromCollection, WithHeadings, WithMapping, WithStrictNullComparison, WithEvents
{
    protected array $filters;
    protected Carbon $monthStart;
    protected Carbon $monthEnd;
    protected Carbon $prevMonthEnd;

    /** @var Collection<int, Employee> */
    protected Collection $employees;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;

        // Resolve month/year from filters; default to current month
        $start = isset($filters['date_range']['start']) ? Carbon::parse($filters['date_range']['start']) : null;
        $year = (int)($filters['year'] ?? ($start?->year ?? now()->year));
        $month = (int)($filters['month'] ?? ($start?->month ?? now()->month));

        $this->monthStart = Carbon::create($year, $month, 1)->startOfDay();
        $this->monthEnd = (clone $this->monthStart)->endOfMonth();
        $this->prevMonthEnd = (clone $this->monthStart)->subDay()->endOfDay();
    }

    public function headings(): array
    {
        $title = 'Attendance of HPCA office staff for the Month of ' . $this->monthStart->format('F Y');
        return [
            [$title],
            [
                'Sr.no.',
                'Name',
                'Designation',
                'leave at credit ' . $this->prevMonthEnd->format('F') . ' closing',
                'Leave availed during month',
                'No. of holidays/Sundays in month',
                'No. of holidays on which attended work',
                'No. of compensatory leave during month',
                'Total leave availed during month',
                'Balance leave at credit',
            ],
        ];
    }

    public function collection(): Collection
    {
        $query = Employee::with(['emp_job_profile.designation'])
            ->where('firm_id', session('firm_id'));

        if (!empty($this->filters['employee_id'])) {
            $query->whereIn('id', (array) $this->filters['employee_id']);
        }

        if (!empty($this->filters['department_id'])) {
            $query->whereHas('emp_job_profile.department', function ($q) {
                $q->whereIn('id', (array) $this->filters['department_id']);
            });
        }

        if (!empty($this->filters['joblocation_id'])) {
            $query->whereHas('emp_job_profile.joblocation', function ($q) {
                $q->whereIn('id', (array) $this->filters['joblocation_id']);
            });
        }

        if (!empty($this->filters['employment_type_id'])) {
            $query->whereHas('emp_job_profile.employment_type', function ($q) {
                $q->whereIn('id', (array) $this->filters['employment_type_id']);
            });
        }

        $this->employees = $query->get();
        // Attach a sequence index used in mapping
        $this->employees = $this->employees->values();

        return $this->employees;
    }

    public function map($employee): array
    {
        $job = $employee->emp_job_profile;

        $prevCredit = $this->sumPreviousMonthBalance($employee->id);
        $leaveAvailed = $this->countLeaveDaysInMonth($employee->id);
        [$holidayDays, $sundays] = $this->countHolidaysAndSundays(); // same for all employees
        $holidaysAndSundays = $holidayDays + $sundays;
        $attendedOnHoliday = $this->countHolidayAttendance($employee->id);
        $compensatoryGranted = $attendedOnHoliday; // credited equal to attended days
        $netLeave = max(0, $leaveAvailed - $compensatoryGranted);
        $closingBalance = $prevCredit - $netLeave;

        return [
            // Sr no. will be re-numbered by sheet styling later; include placeholder index
            '',
            trim(($employee->fname ?? '') . ' ' . ($employee->lname ?? '')),
            $job?->designation?->title ?? '',
            (float) $prevCredit,
            (float) $leaveAvailed,
            (int) $holidaysAndSundays,
            (int) $attendedOnHoliday,
            (int) $compensatoryGranted,
            (float) $netLeave,
            (float) $closingBalance,
        ];
    }

    protected function sumPreviousMonthBalance(int $employeeId): float
    {
        $prevDate = $this->prevMonthEnd->toDateString();
        $rows = EmpLeaveBalance::query()
            ->where('firm_id', session('firm_id'))
            ->where('employee_id', $employeeId)
            ->whereDate('period_start', '<=', $prevDate)
            ->whereDate('period_end', '>=', $prevDate)
            ->get(['balance']);

        return (float) $rows->sum(function ($r) { return (float) ($r->balance ?? 0); });
    }

    protected function countLeaveDaysInMonth(int $employeeId): float
    {
        $rows = EmpAttendance::query()
            ->where('firm_id', session('firm_id'))
            ->where('employee_id', $employeeId)
            ->whereBetween('work_date', [$this->monthStart->toDateString(), $this->monthEnd->toDateString()])
            ->where('attendance_status_main', 'L')
            ->get(['final_day_weightage']);

        // Sum weightage if present, otherwise count as 1
        $sum = 0.0;
        foreach ($rows as $r) {
            $w = (float) ($r->final_day_weightage ?? 1);
            $sum += $w > 0 ? $w : 1;
        }
        return $sum;
    }

    protected function countHolidaysAndSundays(): array
    {
        // Holidays overlapping the month
        $firmId = session('firm_id');
        $monthStart = $this->monthStart->copy();
        $monthEnd = $this->monthEnd->copy();

        $holidays = Holiday::query()
            ->where('firm_id', $firmId)
            ->where(function ($q) use ($monthStart, $monthEnd) {
                $q->whereBetween('start_date', [$monthStart, $monthEnd])
                  ->orWhereBetween(DB::raw('COALESCE(end_date, start_date)'), [$monthStart, $monthEnd])
                  ->orWhere(function ($qq) use ($monthStart, $monthEnd) {
                      $qq->whereDate('start_date', '<=', $monthStart)
                         ->whereDate(DB::raw('COALESCE(end_date, start_date)'), '>=', $monthEnd);
                  });
            })
            ->where(function ($q) {
                $q->whereNull('is_inactive')->orWhere('is_inactive', false);
            })
            ->get(['start_date', 'end_date']);

        $holidayDates = [];
        foreach ($holidays as $h) {
            $hs = Carbon::parse($h->start_date)->startOfDay();
            $he = $h->end_date ? Carbon::parse($h->end_date)->endOfDay() : (clone $hs)->endOfDay();
            $period = CarbonPeriod::create(max($hs, $this->monthStart), min($he, $this->monthEnd));
            foreach ($period as $d) {
                $holidayDates[$d->toDateString()] = true;
            }
        }
        $holidayCount = count($holidayDates);

        // Sundays in the month
        $sundays = 0;
        $p = CarbonPeriod::create($this->monthStart, $this->monthEnd);
        foreach ($p as $d) {
            if ($d->isSunday()) {
                $sundays++;
            }
        }

        return [$holidayCount, $sundays];
    }

    protected function countHolidayAttendance(int $employeeId): int
    {
        // Count flexi week-off entries whose availed attendance date lies in month
        return (int) FlexiWeekOff::query()
            ->where('firm_id', session('firm_id'))
            ->where('employee_id', $employeeId)
            ->whereHas('availedAttendance', function ($q) {
                $q->whereBetween('work_date', [$this->monthStart->toDateString(), $this->monthEnd->toDateString()]);
            })
            ->count();
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Title row styling and merge
                $sheet->mergeCells('A1:J1');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Header row styling (row 2)
                $sheet->getStyle('A2:J2')->getFont()->setBold(true);
                $sheet->getStyle('A2:J2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('A2:J2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('00FFF2CC');

                // Column widths
                $widths = [
                    'A' => 8, 'B' => 30, 'C' => 26, 'D' => 24, 'E' => 16,
                    'F' => 26, 'G' => 30, 'H' => 30, 'I' => 26, 'J' => 22,
                ];
                foreach ($widths as $col => $w) {
                    $sheet->getColumnDimension($col)->setWidth($w);
                }

                // Add serial numbers in column A starting from row 3
                $highestRow = $sheet->getHighestRow();
                for ($r = 3; $r <= $highestRow; $r++) {
                    $sheet->setCellValueExplicit('A' . $r, (string)($r - 2), DataType::TYPE_STRING);
                }

                // Borders for entire used range
                $sheet->getStyle('A1:J' . $highestRow)
                    ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

                // Number formats and alignment so zeros show explicitly
                $integerCols = ['F','G','H'];
                foreach ($integerCols as $c) {
                    $sheet->getStyle($c . '3:' . $c . $highestRow)
                        ->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER);
                    $sheet->getStyle($c . '3:' . $c . $highestRow)
                        ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                }

                $decimalCols = ['D','E','I','J'];
                foreach ($decimalCols as $c) {
                    $sheet->getStyle($c . '3:' . $c . $highestRow)
                        ->getNumberFormat()->setFormatCode('#,##0.00');
                    $sheet->getStyle($c . '3:' . $c . $highestRow)
                        ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                }

                // Light highlight similar to screenshot for certain columns
                $highlightCols = ['D','E','H','J'];
                foreach ($highlightCols as $c) {
                    $sheet->getStyle($c . '2:' . $c . $highestRow)
                        ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('00FFF2CC');
                }
            }
        ];
    }
}


