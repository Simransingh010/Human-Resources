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

        $query = Employee::with([
            'emp_job_profile:id,employee_code,employee_id',
            'bank_account:id,employee_id,bankaccount,ifsc',
            'payroll_tracks' => function ($query) {
                $query->whereBetween('salary_period_from', [$this->start, $this->end]);
            }
        ])
            ->where('firm_id', $firmId);

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

        return $this->employees = $query->get();
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

        $amount_earning = $employee->payroll_tracks->where('nature','earning')->sum('amount_payable');
        $amount_deduction = $employee->payroll_tracks->where('nature','deduction')->sum('amount_payable');
        $amount=$amount_earning-$amount_deduction;

        return [
            $i++,
            $employee->emp_job_profile->employee_code ?? '',
            trim("{$employee->fname} {$employee->lname}"),
            $employee->bank_account->bankaccount ?? '',
            $employee->bank_account->ifsc ?? '',
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
                    'allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN],
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
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
                $rowCount = $this->employees->count() + 1; // +1 for header row

                // Apply borders to all data cells
                $cellRange = 'A1:F' . $rowCount;

                $sheet->getStyle($cellRange)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['argb' => '000000'],
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
