<?php

namespace App\Livewire\Hrms\Reports\PayrollReports\Exports;

use App\Models\Hrms\PayrollComponentsEmployeesTrack;
use App\Models\Hrms\SalaryComponent;
use App\Models\Hrms\Employee;
use App\Models\Hrms\EmployeePersonalDetail;
use App\Models\Saas\Firm;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;

class TdsReportExport extends DefaultValueBinder implements FromCollection, WithHeadings, WithMapping, WithStyles, WithEvents, WithCustomValueBinder
{
    protected $filters;
    protected $start;
    protected $end;
    protected $data;
    protected $firmName;
    protected $month;
    protected $year;

    public function __construct($filters)
    {
        $this->filters = $filters;

        $this->start = Carbon::parse($filters['date_range']['start'])->startOfDay();
        $this->end   = Carbon::parse($filters['date_range']['end'])->endOfDay();
        
        // Get month and year for the report heading
        $this->month = Carbon::parse($filters['date_range']['start'])->format('F');
        $this->year = Carbon::parse($filters['date_range']['start'])->format('Y');

        $firmId = $filters['firm_id'] ?? session('firm_id');
        
        // Get firm name for the report heading
        $firm = Firm::find($firmId);
        $this->firmName = $firm ? $firm->name : '';

        // Collect the data
        $this->data = $this->fetchEmployees($firmId);
    }

    // Helper method to fetch employees
    private function fetchEmployees($firmId)
    {
        $query = Employee::with([
            'emp_job_profile',
            'emp_personal_detail',
            'payroll_tracks' => function ($q) {
                $q->whereBetween('salary_period_from', [$this->start, $this->end])
                  ->whereHas('salary_component', function($q) {
                      $q->where('component_type', 'tax');
                  });
            }
        ])->where('firm_id', $firmId);

        // Apply salary execution group filter if selected
        if (!empty($this->filters['salary_execution_group_id'])) {
            $query->whereHas('salary_execution_groups', function ($q) {
                $q->where('salary_execution_groups.id', $this->filters['salary_execution_group_id']);
            });
        }

        // Apply employee filter if selected
        if (!empty($this->filters['employee_id'])) {
            $query->whereIn('id', $this->filters['employee_id']);
        }
        
        // Apply department filter if selected
        if (!empty($this->filters['department_id'])) {
            $query->whereHas('emp_job_profile', function ($q) {
                $q->whereIn('department_id', $this->filters['department_id']);
            });
        }
        
        // Apply job location filter if selected
        if (!empty($this->filters['joblocation_id'])) {
            $query->whereHas('emp_job_profile', function ($q) {
                $q->whereIn('joblocation_id', $this->filters['joblocation_id']);
            });
        }
        
        // Apply employment type filter if selected
        if (!empty($this->filters['employment_type_id'])) {
            $query->whereHas('emp_job_profile', function ($q) {
                $q->whereIn('employment_type_id', $this->filters['employment_type_id']);
            });
        }

        return $query->get();
    }

    public function collection()
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [[
            'SR. NO.',
            'DATE',
            'STAFF',
            'NAME OF PARTY',
            'PAN NO.',
            'Total Amount to be shown in Form 16',
            'Total TDS Amount to be shown in Form 16'
        ]];
    }

    public function map($employee): array
    {
        static $serial = 1;
        $job = $employee->emp_job_profile;
        
        // Get PAN number from personal details
        $panNo = $employee->emp_personal_detail->panno ?? '';
        
        // Calculate total earnings for the month
        $totalEarnings = PayrollComponentsEmployeesTrack::where('employee_id', $employee->id)
            ->where('firm_id', $this->filters['firm_id'])
            ->where('nature', 'earning')
            ->whereBetween('salary_period_from', [$this->start, $this->end])
            ->sum('amount_payable');

        // Calculate total deductions for the month (excluding TDS)
        $totalDeductions = PayrollComponentsEmployeesTrack::where('employee_id', $employee->id)
            ->where('firm_id', $this->filters['firm_id'])
            ->where('nature', 'deduction')
           ->whereBetween('salary_period_from', [$this->start, $this->end])
            ->sum('amount_payable');

        // Calculate monthly gross salary
        $monthlyGrossSalary = $totalEarnings - $totalDeductions;
        
        // Calculate monthly TDS specifically using component_type='tds'
        $monthlyTds = PayrollComponentsEmployeesTrack::where('employee_id', $employee->id)
            ->where('firm_id', $this->filters['firm_id'])
            ->where('component_type', 'tax')  // Using exact component type as defined in SalaryComponent
            ->whereBetween('salary_period_from', [$this->start, $this->end])
            ->sum('amount_payable');
        
        // Ensure numeric values are properly formatted
        $monthlyGrossSalary = is_numeric($monthlyGrossSalary) ? (float)$monthlyGrossSalary : 0;
        $monthlyTds = is_numeric($monthlyTds) ? (float)$monthlyTds : 0;
            
        // Determine if staff is teaching or non-teaching
        $staffType = $job && isset($job->is_teaching_staff) && $job->is_teaching_staff ? 'Teaching' : 'Non-Teaching';
        
        $row = [
            $serial++,
            $this->end->format('d.m.Y'),
            $staffType,
            trim("{$employee->fname} {$employee->mname} {$employee->lname}"),
            strtoupper($panNo),
            $monthlyGrossSalary,
            $monthlyTds
        ];

        return $row;
    }

    public function bindValue(Cell $cell, $value)
    {
        // Only apply numeric formatting for data rows (not the header row)
        // Header is on row 4 (after 3 inserted rows)
        if ($cell->getRow() > 4 && in_array($cell->getColumn(), ['F', 'G'])) {
            if (is_numeric($value)) {
                $cell->setValueExplicit((float)$value, DataType::TYPE_NUMERIC);
                return true;
            }
            $cell->setValueExplicit(0, DataType::TYPE_NUMERIC);
            return true;
        }
        return parent::bindValue($cell, $value);
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

                // Insert 2 rows at the top for title
                $sheet->insertNewRowBefore(1, 3);

                // Get the highest column letter
                $highestColumn = $sheet->getHighestColumn();

                // Set titles (row 1 and 2)
                $sheet->mergeCells("A1:{$highestColumn}1");
                $sheet->setCellValue("A1", strtoupper($this->firmName));
                $sheet->getStyle("A1")->getFont()->setSize(14)->setBold(true);
                $sheet->getStyle("A1")->getAlignment()->setHorizontal('center');

                $sheet->mergeCells("A2:{$highestColumn}2");
                $sheet->setCellValue("A2", "TDS FROM SALARY (192-B)");
                $sheet->getStyle("A2")->getFont()->setSize(12)->setBold(true);
                $sheet->getStyle("A2")->getAlignment()->setHorizontal('center');
                
                $sheet->mergeCells("A3:{$highestColumn}3");
                $sheet->setCellValue("A3", "{$this->month}-{$this->year}");
                $sheet->getStyle("A3")->getFont()->setSize(12)->setBold(true);
                $sheet->getStyle("A3")->getAlignment()->setHorizontal('center');

                // Set header row style (now row 4)
                $headerRow = 4;
                $sheet->getStyle("A{$headerRow}:{$highestColumn}{$headerRow}")->getFont()->setBold(true);
                $sheet->getStyle("A{$headerRow}:{$highestColumn}{$headerRow}")->getAlignment()->setHorizontal('center');
                $sheet->getStyle("A{$headerRow}:{$highestColumn}{$headerRow}")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

                // Set data rows borders
                $dataRowStart = $headerRow + 1;
                $dataRowEnd = $sheet->getHighestRow();
                $sheet->getStyle("A{$dataRowStart}:{$highestColumn}{$dataRowEnd}")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

                // Format numeric columns (F and G)
                foreach (['F', 'G'] as $col) {
                    $sheet->getStyle("{$col}{$dataRowStart}:{$col}{$dataRowEnd}")
                        ->getNumberFormat()
                        ->setFormatCode('#,##0.00');
                }

                // Auto width for all columns
                foreach (range('A', $highestColumn) as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }
                
                // Freeze panes to keep header visible when scrolling
                $sheet->freezePane('A5');
            }
        ];
    }
}
