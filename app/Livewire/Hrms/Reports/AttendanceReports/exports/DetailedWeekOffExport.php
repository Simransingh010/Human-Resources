<?php

namespace App\Livewire\Hrms\Reports\AttendanceReports\exports;

use App\Models\Hrms\Employee;
use App\Models\Hrms\FlexiWeekOff;
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

class DetailedWeekOffExport extends DefaultValueBinder implements FromCollection, WithHeadings, WithMapping, WithStyles, WithEvents, WithCustomValueBinder
{
    protected $filters;
    protected $start;
    protected $end;
    protected $data;
    protected $firmName;

    public function __construct($filters)
    {
        $this->filters = $filters;
        $this->start = Carbon::parse($filters['date_range']['start'])->startOfDay();
        $this->end   = Carbon::parse($filters['date_range']['end'])->endOfDay();
        $firmId = $filters['firm_id'] ?? session('firm_id');
        $firm = Firm::find($firmId);
        $this->firmName = $firm ? $firm->name : '';
        $this->data = $this->fetchWeekOffs($firmId);
    }

    private function fetchWeekOffs($firmId)
    {
        $employeeQuery = Employee::with(['emp_job_profile', 'emp_job_profile.department', 'emp_job_profile.designation'])
            ->where('firm_id', $firmId);

        if (!empty($this->filters['salary_execution_group_id'])) {
            $employeeQuery->whereHas('salary_execution_groups', function ($q) {
                $q->where('salary_execution_groups.id', $this->filters['salary_execution_group_id']);
            });
        }
        if (!empty($this->filters['employee_id'])) {
            $employeeQuery->whereIn('id', $this->filters['employee_id']);
        }
        if (!empty($this->filters['department_id'])) {
            $employeeQuery->whereHas('emp_job_profile', function ($q) {
                $q->whereIn('department_id', $this->filters['department_id']);
            });
        }
        if (!empty($this->filters['joblocation_id'])) {
            $employeeQuery->whereHas('emp_job_profile', function ($q) {
                $q->whereIn('joblocation_id', $this->filters['joblocation_id']);
            });
        }
        if (!empty($this->filters['employment_type_id'])) {
            $employeeQuery->whereHas('emp_job_profile', function ($q) {
                $q->whereIn('employment_type_id', $this->filters['employment_type_id']);
            });
        }
        $employees = $employeeQuery->get();

        $rows = [];
        foreach ($employees as $employee) {
            $weekOffs = FlexiWeekOff::where('firm_id', $employee->firm_id)
                ->where('employee_id', $employee->id)
                ->whereHas('availedAttendance', function($q) {
                    $q->whereBetween('work_date', [$this->start, $this->end]);
                })
                ->with(['availedAttendance', 'consumedAttendance'])
                ->get();
            foreach ($weekOffs as $weekOff) {
                $rows[] = [
                    'employee' => $employee,
                    'week_off' => $weekOff,
                ];
            }
        }
        return collect($rows);
    }

    public function collection()
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [[
            'S.No',
            'Employee Code',
            'Employee Name',
            'Department',
            'Designation',
            'Availed Week Off Date',
            'Consumed Week Off Date',
            'Status',
            'Remarks',
        ]];
    }

    public function map($row): array
    {
        static $serial = 1;
        $employee = $row['employee'];
        $job = $employee->emp_job_profile;
        $weekOff = $row['week_off'];
        $availedAttendance = $weekOff->availedAttendance;
        $consumedAttendance = $weekOff->consumedAttendance ?? null;
        $consumedInfo = '';
        if ($consumedAttendance) {
            $consumedEmp = $consumedAttendance->employee ?? null;
            $consumedName = $consumedEmp ? trim("{$consumedEmp->fname} {$consumedEmp->lname}") : '';
            $consumedInfo = $consumedAttendance->work_date ? $consumedAttendance->work_date->format('Y-m-d') : '';
            if ($consumedName) {
                $consumedInfo .= ' - ' . $consumedName;
            }
        }
        // Status in words
        $statusWords = [
            'A' => 'Available',
            'C' => 'Consumed',
            'L' => 'Lapsed',
            'CF' => 'Carry Forward',
        ];
        $status = $statusWords[$weekOff->week_off_Status] ?? $weekOff->week_off_Status;
        return [
            $serial++,
            $job->employee_code ?? '',
            trim("{$employee->fname} {$employee->lname}"),
            $job->department->title ?? '',
            $job->designation->title ?? '',
            $availedAttendance ? $availedAttendance->work_date->format('Y-m-d') : '',
            $consumedInfo,
            $status,
            $availedAttendance ? ($availedAttendance->attend_remarks ?? '') : '',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [];
    }

    public function bindValue(Cell $cell, $value)
    {
        // Employee Code column is B, Availed Week Off Date is F, Consumed Week Off Date is G
        if (in_array($cell->getColumn(), ['B', 'F', 'G'])) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);
            return true;
        }
        return parent::bindValue($cell, $value);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->insertNewRowBefore(1, 2);
                $totalCols = 9;
                $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($totalCols);
                $sheet->mergeCells("A1:{$lastCol}1");
                $sheet->setCellValue("A1", strtoupper($this->firmName));
                $sheet->getStyle("A1")->getFont()->setSize(16)->setBold(true);
                $sheet->getStyle("A1")->getAlignment()->setHorizontal('center');
                $sheet->mergeCells("A2:{$lastCol}2");
                $sheet->setCellValue("A2", "Detailed Week Off Report for the period " . $this->start->format('d M Y') . " to " . $this->end->format('d M Y'));
                $sheet->getStyle("A2")->getFont()->setSize(14)->setBold(true);
                $sheet->getStyle("A2")->getAlignment()->setHorizontal('center');
                $sheet->getStyle("A3:{$lastCol}3")->getFont()->setBold(true);
                $sheet->getStyle("A3:{$lastCol}3")->getAlignment()->setHorizontal('center');
                $sheet->getStyle("A3:{$lastCol}3")->getAlignment()->setVertical('center');
                $sheet->getStyle("A3:{$lastCol}3")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM);
                foreach (range('A', $lastCol) as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }
                $dataRowStart = 4;
                $dataRowEnd = $sheet->getHighestRow();
                if ($dataRowEnd >= $dataRowStart) {
                    $sheet->getStyle("A{$dataRowStart}:{$lastCol}{$dataRowEnd}")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM);
                } else {
                    $noDataRow = 4;
                    $sheet->mergeCells("A{$noDataRow}:{$lastCol}{$noDataRow}");
                    $sheet->setCellValue("A{$noDataRow}", "No data found for the selected criteria");
                    $sheet->getStyle("A{$noDataRow}")->getAlignment()->setHorizontal('center');
                    $sheet->getStyle("A{$noDataRow}")->getFont()->setBold(true);
                }
                $sheet->freezePane('A4');
            }
        ];
    }
}
