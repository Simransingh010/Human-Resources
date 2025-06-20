<?php

namespace App\Livewire\Hrms\Reports\PayrollReports\Exports;

use App\Models\Hrms\Employee;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use App\Models\Saas\Firm;

class BankReportExport extends StringValueBinder implements FromCollection, WithHeadings, WithMapping, WithStyles, WithEvents, ShouldAutoSize, WithCustomValueBinder
{
    protected $filters; 
    protected $start;
    protected $end;
    protected $employees;

    public function __construct($filters)
    {
        $this->filters = $filters;
        $this->start = Carbon::parse($filters['date_range']['start'])->startOfDay();
        $this->end = Carbon::parse($filters['date_range']['end'])->endOfDay();
        // Ensure firm name is set
        if (empty($this->filters['firm_name'])) {
            $firmId = $this->filters['firm_id'] ?? null;
            $firm = $firmId ? Firm::find($firmId) : null;
            $this->filters['firm_name'] = $firm ? $firm->name : '';
        }
    }

    public function collection(): Collection
    {
        $firmId = $this->filters['firm_id'] ?? session('firm_id');

        $employees = Employee::with([
            'emp_job_profile:id,employee_code,employee_id',
            'bank_account',
            'payroll_tracks' => function ($query) {
                $query->whereBetween('salary_period_from', [$this->start, $this->end]);
            }
        ])
            ->where('firm_id', $firmId)
            ->when(!empty($this->filters['employee_id']), fn($q) =>
                $q->whereIn('id', $this->filters['employee_id'])
            )->get();

        // Filter out employees with zero or no amounts
        return $this->employees = $employees->filter(function ($employee) {
            $amount_earning = $employee->payroll_tracks->where('nature', 'earning')->sum('amount_payable');
            $amount_deduction = $employee->payroll_tracks->where('nature', 'deduction')->sum('amount_payable');
            $amount = $amount_earning - $amount_deduction;
            
            return $amount > 0;
        });
    }

    public function headings(): array
    {
        return [[
            'S.No',
            'Employee Code',
            'Employee Name',
            'Bank Account No.',
            'IFSC Code',
            'Net Pay Amount'
        ]];
    }

    public function map($employee): array
    {
        static $i = 1;
        $amount_earning = $employee->payroll_tracks->where('nature', 'earning')->sum('amount_payable');
        $amount_deduction = $employee->payroll_tracks->where('nature', 'deduction')->sum('amount_payable');
        $amount = $amount_earning - $amount_deduction;
        return [
            $i++,
            $employee->emp_job_profile->employee_code ?? '',
            trim("{$employee->fname} {$employee->lname}"),
            isset($employee->bank_account) ? $employee->bank_account->bankaccount : '',
            isset($employee->bank_account) ? $employee->bank_account->ifsc : '',
            is_numeric($amount) ? (float)$amount : 0
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [ // Header row style
                'font' => ['bold' => true],
                'alignment' => ['horizontal' => 'center'],
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'D9E1F2']
                ]
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            \Maatwebsite\Excel\Events\AfterSheet::class => function(\Maatwebsite\Excel\Events\AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                // Insert 3 rows at the top for title
                $sheet->insertNewRowBefore(1, 3);
                $highestColumn = $sheet->getHighestColumn();
                $sheet->mergeCells("A1:{$highestColumn}1");
                $sheet->setCellValue("A1", strtoupper($this->filters['firm_name'] ?? 'FIRM NAME'));
                $sheet->getStyle("A1")->getFont()->setSize(14)->setBold(true);
                $sheet->getStyle("A1")->getAlignment()->setHorizontal('center');
                $sheet->mergeCells("A2:{$highestColumn}2");
                $sheet->setCellValue("A2", "BANK REPORT");
                $sheet->getStyle("A2")->getFont()->setSize(12)->setBold(true);
                $sheet->getStyle("A2")->getAlignment()->setHorizontal('center');
                $month = isset($this->filters['date_range']['start']) ? \Carbon\Carbon::parse($this->filters['date_range']['start'])->format('F-Y') : '';
                $sheet->mergeCells("A3:{$highestColumn}3");
                $sheet->setCellValue("A3", $month);
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
                // Format Net Pay Amount column (F) as Indian currency '#,##,##0.00'
                $sheet->getStyle("F{$dataRowStart}:F{$dataRowEnd}")
                    ->getNumberFormat()
                    ->setFormatCode('#,##,##0.00');
                // Auto width for all columns
                foreach (range('A', $highestColumn) as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }
                // Grand Total row
                $grandTotalRow = $dataRowEnd + 1;
                $sheet->setCellValue("E{$grandTotalRow}", 'Grand Total');
                $sheet->setCellValueExplicit(
                    "F{$grandTotalRow}",
                    "=SUM(F{$dataRowStart}:F{$dataRowEnd})",
                    \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_FORMULA
                );
                $sheet->getStyle("E{$grandTotalRow}:F{$grandTotalRow}")->getFont()->setBold(true);
                $sheet->getStyle("F{$grandTotalRow}")->getNumberFormat()->setFormatCode('#,##,##0.00');
                $sheet->getStyle("E{$grandTotalRow}:F{$grandTotalRow}")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                // Freeze panes to keep header visible when scrolling
                $sheet->freezePane('A5');
            }
        ];
    }

    public function registerEvents_old(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $rowCount = $this->employees->count() + 1; // +1 for header row

                // Apply borders to all data cells
                $cellRange = 'A1:F' . $rowCount;

                $sheet->getStyle($cellRange)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                        ],
                    ],
                    'alignment' => [
                        'vertical' => 'center',
                    ],
                ]);
            },
        ];
    }

    // Add this method to control value binding for each column
    public function bindValue(Cell $cell, $value)
    {
        // Bank Account No. (D) as string
        if ($cell->getColumn() === 'D') {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);
            return true;
        }
        // Net Payable (F) as number, but only for data rows (not header or grand total)
        if ($cell->getColumn() === 'F' && is_numeric($value) && $cell->getRow() > 1) {
            $cell->setValueExplicit($value, DataType::TYPE_NUMERIC);
            return true;
        }
        // Default: treat as string
        return parent::bindValue($cell, $value);
    }
}
