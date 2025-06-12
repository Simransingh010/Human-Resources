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

class BankReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithEvents, ShouldAutoSize
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
        return [
            'S.No',
            'Employee Code',
            'Employee Name',
            'Bank Account No.',
            'IFSC Code',
            'Net Pay Amount'
        ];
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
            number_format($amount, 0, '.', '')
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
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $rowCount = $this->employees->count();
                $headerRow = 1;
                $dataStartRow = $headerRow + 1;
                $dataEndRow = $dataStartRow + $rowCount - 1;

                // Set bank account number column to text format
                $sheet->getStyle('D' . $dataStartRow . ':' . 'D' . $dataEndRow)
                    ->getNumberFormat()
                    ->setFormatCode(NumberFormat::FORMAT_TEXT);

                // Apply border and style to data and header rows
                $sheet->getStyle("A{$headerRow}:F{$dataEndRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                        ],
                    ],
                    'alignment' => [
                        'vertical' => 'center',
                    ],
                ]);

                // Calculate total
                $grandTotal = $this->employees->sum(function ($e) {
                    $earn = $e->payroll_tracks->where('nature', 'earning')->sum('amount_payable');
                    $deduct = $e->payroll_tracks->where('nature', 'deduction')->sum('amount_payable');
                    return $earn - $deduct;
                });

                $totalRow = $dataEndRow + 1;

                // Add Grand Total row
                $sheet->setCellValue("E{$totalRow}", 'Grand Total');
                $sheet->setCellValue("F{$totalRow}", number_format($grandTotal, 0, '.', ''));

                $sheet->getStyle("E{$totalRow}:F{$totalRow}")->applyFromArray([
                    'font' => ['bold' => true],
                    'alignment' => ['horizontal' => 'right'],
                    'borders' => [
                        'top' => [
                            'borderStyle' => Border::BORDER_THIN,
                        ],
                    ],
                ]);
            },
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
}
