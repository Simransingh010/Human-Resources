<?php

namespace App\Livewire\Hrms\Reports\PayrollReports\exports;

use App\Models\Hrms\Employee;
use App\Models\Hrms\EmployeePersonalDetail;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class HpcaEpfReportExport extends EpfReportExport implements WithHeadings, WithMapping, WithEvents, WithColumnFormatting
{
    protected array $uanToFatherName = [];

    public function headings(): array
    {
        // Multi-row header to match screenshot structure
        $periodLabel = 'SALARY DETAILS  FOR THE MONTH OF ' . now()->format('F Y');
        return [
            ['EPF DETAILS'],
            [$periodLabel],
            ['Code','Actual Name','Father Name','UAN NO','Gross Salary','Basic Salary','Epf On','EPF','EMP Contr.','EPF payable'],
        ];
    }

    public function columnFormats(): array
    {
        // Ensure UAN column (D) is always treated as text
        return [
            'D' => NumberFormat::FORMAT_TEXT,
        ];
    }

    public function map($row): array
    {
        // Reuse normalized fields from base rows; fill optional fields if unavailable
        $code = '';
        $uan = (string)($row['uan'] ?? '');
        $fatherName = $this->resolveFatherNameByUan($uan);

        $epf = (float)($row['epf_contri'] ?? 0); // employee 12%
        // Per HPCA requirement: employer contribution should equal employee EPF
        $employerTotal = $epf;
        $epfPayable = $epf * 2; // total remittance

        return [
            $code,
            $row['name'] ?? '',
            $fatherName,
            '\'' . $uan,
            (float)($row['gross_wages'] ?? 0),
            (float)($row['eps_wages'] ?? 0) + ((float)($row['epf_wages'] ?? 0) - (float)($row['eps_wages'] ?? 0)), // fallback basic ~ wages base
            (float)($row['epf_wages'] ?? 0),
            $epf,
            $employerTotal,
            $epfPayable,
        ];
    }

    protected function resolveFatherNameByUan(string $uan): string
    {
        if ($uan === '') {
            return '';
        }

        if (array_key_exists($uan, $this->uanToFatherName)) {
            return $this->uanToFatherName[$uan];
        }

        $employee = Employee::whereHas('emp_job_profile', function($q) use ($uan) {
            $q->where('uanno', $uan);
        })->first();

        if (!$employee) {
            return $this->uanToFatherName[$uan] = '';
        }

        $detail = EmployeePersonalDetail::where('employee_id', $employee->id)->first();
        return $this->uanToFatherName[$uan] = trim((string)($detail->fathername ?? ''));
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Title row styling
                $sheet->mergeCells('A1:J1');
                $sheet->setCellValue('A1', 'EPF DETAILS');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(18);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Period row styling
                $sheet->mergeCells('A2:J2');
                $sheet->setCellValue('A2', 'SALARY DETAILS  FOR THE MONTH OF ' . now()->format('JULY Y')); // visually similar
                $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);
                $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Header row (row 3)
                $sheet->getStyle('A3:J3')->getFont()->setBold(true);
                $sheet->getStyle('A3:J3')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('00FFFF99');
                $sheet->getStyle('A3:J3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Borders for entire table
                $highestRow = $sheet->getHighestRow();
                $sheet->getStyle("A1:J{$highestRow}")
                    ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

                // Column widths
                $widths = [
                    'A' => 8, 'B' => 28, 'C' => 26, 'D' => 18, 'E' => 14,
                    'F' => 14, 'G' => 12, 'H' => 10, 'I' => 12, 'J' => 14,
                ];
                foreach ($widths as $col => $w) {
                    $sheet->getColumnDimension($col)->setWidth($w);
                }

                // Force UAN column (D) to text to avoid scientific notation
                $sheet->getStyle('D4:D' . $highestRow)->getNumberFormat()->setFormatCode('@');

                // Currency/number format for amount columns
                $amountCols = ['E','F','G','H','I','J'];
                foreach ($amountCols as $col) {
                    $sheet->getStyle("{$col}4:{$col}{$highestRow}")
                        ->getNumberFormat()->setFormatCode('#,##0.00');
                    $sheet->getStyle("{$col}4:{$col}{$highestRow}")
                        ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                }

                // Highlight sample rows similar to screenshot (optional visual cue)
                // Here we lightly highlight every 8th row; adjust as needed
                for ($r = 4; $r <= $highestRow; $r += 8) {
                    $sheet->getStyle("A{$r}:J{$r}")
                        ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('00FFF2CC');
                }
            }
        ];
    }
}


