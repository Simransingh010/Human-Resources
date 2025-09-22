<?php

namespace App\Livewire\Hrms\Reports\PayrollReports\Exports;

use App\Models\Hrms\PayrollComponentsEmployeesTrack;
use App\Models\Hrms\SalaryComponent;
use App\Models\Hrms\Employee;
use App\Models\Saas\Firm;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;

class EcisReportExport extends DefaultValueBinder implements FromCollection, WithHeadings, WithMapping, WithStyles, WithEvents, WithCustomValueBinder
{
    protected $filters;
    protected $start;
    protected $end;
    protected $data;
    protected $firmName;

    // ESIC component IDs
    const ECIS_EMPLOYEE_ID = 43;
    const ECIS_EMPLOYER_ID = 47;

    public function __construct($filters)
    {
        $this->filters = $filters;
        $this->start = Carbon::parse($filters['date_range']['start'])->startOfDay();
        $this->end   = Carbon::parse($filters['date_range']['end'])->endOfDay();
        $firmId = $filters['firm_id'] ?? session('firm_id');
        $firm = Firm::find($firmId);
        $this->firmName = $firm ? $firm->name : '';
        $this->data = $this->fetchEmployees($firmId);
    }

    private function fetchEmployees($firmId)
    {
        $query = Employee::with([
            'emp_job_profile',
            'emp_personal_detail',
            'payroll_tracks' => function ($q) {
                $q->whereBetween('salary_period_from', [$this->start, $this->end]);
            }
        ])->where('firm_id', $firmId);

        if (!empty($this->filters['salary_execution_group_id'])) {
            $query->whereHas('salary_execution_groups', function ($q) {
                $q->where('salary_execution_groups.id', $this->filters['salary_execution_group_id']);
            });
        }
        if (!empty($this->filters['employee_id'])) {
            $query->whereIn('id', $this->filters['employee_id']);
        }

        $employees = $query->get();

        // Exclude employees on hold for their payroll slot for the period
        $filtered = $employees->filter(function ($employee) use ($firmId) {
            // Check if employee was hired before or during the report period
            $jobProfile = $employee->emp_job_profile;
            if ($jobProfile && $jobProfile->doh) {
                // If employee was hired after the end of the report period, exclude them
                if ($jobProfile->doh->isAfter($this->end)) {
                    return false;
                }
            }
            
            $payrollTrack = $employee->payroll_tracks->first();
            if (!$payrollTrack || !$payrollTrack->payroll_slot_id) {
                return true; // No payroll slot, include by default
            }
            $payrollSlotId = $payrollTrack->payroll_slot_id;
            $onHold = \App\Models\Hrms\SalaryHold::where('firm_id', $firmId)
                ->where('payroll_slot_id', $payrollSlotId)
                ->where('employee_id', $employee->id)
                ->exists();
            return !$onHold;
        });

        // Sort employees by salary execution groups for Firm ID 27
        if ($firmId == 27) {
            $filtered = $this->sortEmployeesBySalaryGroups($filtered);
        }

        return $filtered->values();
    }

    // Helper method to sort employees by salary execution groups for Firm ID 27
    private function sortEmployeesBySalaryGroups($employees)
    {
        // Define the desired sequence for Firm ID 27
        $desiredSequence = [
            'Director',
            'Faculty Regular', 
            'Faculty Contractual',
            'Staff Permanent',
            'Staff Contractual'
        ];

        // Create a mapping of group titles to their priority
        $priorityMap = array_flip($desiredSequence);

        return $employees->sortBy(function($employee) use ($priorityMap) {
            // Get the first salary execution group for this employee
            $salaryGroup = $employee->salary_execution_groups->first();
            
            if (!$salaryGroup) {
                // If no salary group, put at the end
                return 999;
            }

            $groupTitle = $salaryGroup->title;
            
            // Check if the group title is in our desired sequence
            if (isset($priorityMap[$groupTitle])) {
                return $priorityMap[$groupTitle];
            }

            // If not in our sequence, put at the end
            return 999;
        });
    }

    public function collection()
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [[
            'S.No',
            'CODE',
            'Actual Name',
            'Father Name',
            'Gross Salary',
            'Ecis .75%',
            'Employer 3.25%',
            'Total Payable',
        ]];
    }

    public function map($employee): array
    {
        static $serial = 1;
        $job = $employee->emp_job_profile;
        $personal = $employee->emp_personal_detail;
        $payrollTracks = collect($employee->payroll_tracks);
        // Gross Salary = sum of all earnings
        $grossSalary = $payrollTracks->where('nature', 'earning')->sum('amount_payable');

        // Calculate ECIS contributions directly from gross
        $ecisEmployee = round($grossSalary * 0.0075, 2); // 0.75%
        $ecisEmployer = round($grossSalary * 0.0325, 2); // 3.25%
        $totalPayable = round($ecisEmployee + $ecisEmployer, 2);

        return [
            $serial++,
            $job->employee_code ?? '',
            trim("{$employee->fname} {$employee->lname}"),
            $personal->fathername ?? '',
            $grossSalary,
            $ecisEmployee,
            $ecisEmployer,
            $totalPayable,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->insertNewRowBefore(1, 2);
                $highestColumn = $sheet->getHighestColumn();
                $sheet->mergeCells("A1:" . $highestColumn . "1");
                $sheet->setCellValue("A1", strtoupper($this->firmName));
                $sheet->getStyle("A1")->getFont()->setSize(18)->setBold(true);
                $sheet->getStyle("A1")->getAlignment()->setHorizontal('center');
                $sheet->mergeCells("A2:" . $highestColumn . "2");
                $sheet->setCellValue("A2", "SALARY DETAILS  FOR THE MONTH OF " . Carbon::parse($this->start)->format('F Y'));
                $sheet->getStyle("A2")->getFont()->setSize(14)->setBold(true);
                $sheet->getStyle("A2")->getAlignment()->setHorizontal('center');
                $headerRow = 3;
                $sheet->getStyle("A{$headerRow}:{$highestColumn}{$headerRow}")->getFont()->setBold(true);
                $sheet->getStyle("A{$headerRow}:{$highestColumn}{$headerRow}")->getAlignment()->setHorizontal('center');
                $sheet->getStyle("A{$headerRow}:{$highestColumn}{$headerRow}")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                $dataRowStart = $headerRow + 1;
                $dataRowEnd = $sheet->getHighestRow();
                $sheet->getStyle("A{$dataRowStart}:{$highestColumn}{$dataRowEnd}")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                foreach (range('A', $highestColumn) as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }
                foreach (['E', 'F', 'G', 'H'] as $col) {
                    $sheet->getStyle("{$col}{$dataRowStart}:{$col}{$dataRowEnd}")
                        ->getNumberFormat()
                        ->setFormatCode('#,##0.00');
                }
                $totalsRow = $dataRowEnd + 1;
                $sheet->setCellValue("A{$totalsRow}", 'Grand Total');
                foreach (['E', 'F', 'G', 'H'] as $col) {
                    $sheet->setCellValue(
                        "{$col}{$totalsRow}",
                        "=SUM({$col}{$dataRowStart}:{$col}{$dataRowEnd})"
                    );
                    $sheet->getStyle("{$col}{$totalsRow}")
                        ->getNumberFormat()
                        ->setFormatCode('#,##0.00');
                }
                $sheet->getStyle("A{$totalsRow}:{$highestColumn}{$totalsRow}")->getFont()->setBold(true);
                $sheet->getStyle("A{$totalsRow}:{$highestColumn}{$totalsRow}")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                $sheet->freezePane('A4');
            }
        ];
    }

    public function bindValue(Cell $cell, $value)
    {
        if ($cell->getColumn() === 'B' && $cell->getRow() > 3) {
            $cell->setValueExplicit((string)$value, DataType::TYPE_STRING);
            return true;
        }
        return parent::bindValue($cell, $value);
    }
} 