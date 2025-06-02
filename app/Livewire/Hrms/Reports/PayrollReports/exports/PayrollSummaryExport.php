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

class PayrollSummaryExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithEvents
{
    protected $filters;
    protected $start;
    protected $end;
    protected $earnings;
    protected $deductions;
    protected $allComponents;
    protected $data;
    protected $firmName;

    public function __construct($filters)
    {
        $this->filters = $filters;

        $this->start = Carbon::parse($filters['date_range']['start'])->startOfDay();
        $this->end   = Carbon::parse($filters['date_range']['end'])->endOfDay();

        $firmId = $filters['firm_id'] ?? session('firm_id');
        
        // Get firm name for the report heading
        $firm = Firm::find($firmId);
        $this->firmName = $firm ? $firm->name : '';

        // First, collect the data to determine which components are actually used
        $this->data = $this->fetchEmployees($firmId);
        
        // Extract unique component IDs from the payroll tracks
        $componentIds = [];
        foreach ($this->data as $employee) {
            if ($employee->payroll_tracks) {
                foreach ($employee->payroll_tracks as $track) {
                    $componentIds[$track->salary_component_id] = true;
                }
            }
        }
        
        // Only fetch the components that are actually used by the selected employees
        if (!empty($componentIds)) {
            $this->allComponents = SalaryComponent::whereIn('id', array_keys($componentIds))
                ->where('firm_id', $firmId)
                ->get();
        } else {
            $this->allComponents = collect([]);
        }
        
        $this->earnings = $this->allComponents->where('nature', 'earning')->values();
        $this->deductions = $this->allComponents->where('nature', 'deduction')->values();
    }

    // Helper method to fetch employees
    private function fetchEmployees($firmId)
    {
        $query = Employee::with([
            'emp_job_profile.department',
            'payroll_tracks' => function ($q) {
                $q->whereBetween('salary_period_from', [$this->start, $this->end]);
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

        return $query->get();
    }

    public function collection()
    {
        return $this->data;
    }

    // Only the FINAL header row should go here!
    public function headings(): array
    {
        // Row 1: Employee fields (will be merged down)
        $empFields = [
            'S.No',
            'Employee Code',
            'Employee Name',
            'Department',p
            'Designation'
        ];

        // Row 1: Main headers
        $headers = array_merge(
            $empFields,
            array_fill(0, $this->earnings->count(), 'Earnings'),
            ['Total Earnings'],
            array_fill(0, $this->deductions->count(), 'Deductions'),
            ['Total Deductions', 'Net Pay']
        );

        // Row 2: Component titles
        $subHeaders = array_merge(
            array_fill(0, 5, ''), // Empty cells for employee fields (will be merged)
            $this->earnings->pluck('title')->toArray(),
            [''],  // Empty cell for Total Earnings (will be merged)
            $this->deductions->pluck('title')->toArray(),
            ['', '']  // Empty cells for Total Deductions and Net Pay (will be merged)
        );

        return [$headers, $subHeaders];
    }

    public function map($employee): array
    {
        static $serial = 1;
        $job = $employee->emp_job_profile;

        $payrollTracks = collect($employee->payroll_tracks);
        $trackByComponent = $payrollTracks
            ->groupBy('salary_component_id')
            ->map(fn($items) => $items->sum('amount_payable'));

        $row = [
            $serial++,
            $job->employee_code ?? '',
            trim("{$employee->fname} {$employee->lname}"),
            $job->department->title ?? '',
            $job->designation->title ?? '',
        ];

        $earnTotal = 0;
        foreach ($this->earnings as $component) {
            $amount = $trackByComponent[$component->id] ?? 0;
            $row[] = $amount;
            $earnTotal += $amount;
        }
        $row[] = $earnTotal;

        $dedTotal = 0;
        foreach ($this->deductions as $component) {
            $amount = $trackByComponent[$component->id] ?? 0;
            $row[] = $amount;
            $dedTotal += $amount;
        }
        $row[] = $dedTotal;

        $row[] = $earnTotal - $dedTotal;

        return $row;
    }

    public function styles(Worksheet $sheet)
    {
        // We'll do most formatting in AfterSheet for dynamic merges
        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Insert 2 rows at the top for title
                $sheet->insertNewRowBefore(1, 2);

                // Dynamic column counts
                $empColCount = 5;
                $earnCount = $this->earnings->count();
                $dedCount = $this->deductions->count();
                $totalCols = $empColCount + $earnCount + 1 + $dedCount + 2;
                $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($totalCols);

                // Set titles (row 1 and 2)
                $sheet->mergeCells("A1:{$lastCol}1");
                $sheet->setCellValue("A1", $this->firmName);
                $sheet->getStyle("A1")->getFont()->setSize(26)->setBold(true);
                $sheet->getStyle("A1")->getAlignment()->setHorizontal('center');

                $sheet->mergeCells("A2:{$lastCol}2");
                $sheet->setCellValue("A2", "SALARY OF EMPLOYEE FOR THE MONTH OF " . Carbon::parse($this->start)->format('F Y'));
                $sheet->getStyle("A2")->getFont()->setSize(20)->setBold(true);
                $sheet->getStyle("A2")->getAlignment()->setHorizontal('center');

                // Headers start at row 3
                // Merge employee fields vertically (rows 3-4)
                for ($i = 1; $i <= $empColCount; $i++) {
                    $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
                    $sheet->mergeCells("{$col}3:{$col}4");
                }

                // Merge Earnings header horizontally
                $earnStart = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($empColCount + 1);
                $earnEnd = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($empColCount + $earnCount);
                $sheet->mergeCells("{$earnStart}3:{$earnEnd}3");

                // Merge Deductions header horizontally
                $dedStart = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($empColCount + $earnCount + 2);
                $dedEnd = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($empColCount + $earnCount + 1 + $dedCount);
                $sheet->mergeCells("{$dedStart}3:{$dedEnd}3");

                // Merge Total columns vertically
                $totalEarnCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($empColCount + $earnCount + 1);
                $sheet->mergeCells("{$totalEarnCol}3:{$totalEarnCol}4");

                $totalDedCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($empColCount + $earnCount + $dedCount + 2);
                $sheet->mergeCells("{$totalDedCol}3:{$totalDedCol}4");

                $netPayCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($empColCount + $earnCount + $dedCount + 3);
                $sheet->mergeCells("{$netPayCol}3:{$netPayCol}4");

                // Set styles for all headers
                $sheet->getStyle("A1:{$lastCol}4")->getFont()->setBold(true);
                $sheet->getStyle("A1:{$lastCol}4")->getAlignment()->setHorizontal('center');
                $sheet->getStyle("A1:{$lastCol}4")->getAlignment()->setVertical('center');

                // Set font size 14 for column headers
                $sheet->getStyle("A3:{$lastCol}4")->getFont()->setSize(14);

                // Add thick borders to header cells
                $sheet->getStyle("A3:{$lastCol}4")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM);

                // Auto width for all columns
                foreach (range('A', $lastCol) as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }

                // Data rows start at row 5
                $dataRowStart = 5;
                $dataRowEnd = $sheet->getHighestRow();

                // Add thick borders to data cells
                $sheet->getStyle("A{$dataRowStart}:{$lastCol}{$dataRowEnd}")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM);

                // Format number columns to show 2 decimal places
                for ($col = $empColCount + 1; $col <= $totalCols; $col++) {
                    $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                    $sheet->getStyle("{$colLetter}{$dataRowStart}:{$colLetter}{$dataRowEnd}")
                        ->getNumberFormat()
                        ->setFormatCode('#,##0.00');
                }

                // Grand Total row
                $totalsRow = $dataRowEnd + 1;
                $sheet->setCellValue("A{$totalsRow}", 'Grand Total');
                $sheet->getStyle("A{$totalsRow}:{$lastCol}{$totalsRow}")->getFont()->setBold(true);
                $sheet->getStyle("A{$totalsRow}:{$lastCol}{$totalsRow}")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM);

                // Calculate totals
                for ($colIdx = $empColCount + 1; $colIdx <= $totalCols; $colIdx++) {
                    $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
                    $sheet->setCellValue(
                        "{$colLetter}{$totalsRow}", 
                        "=SUM({$colLetter}{$dataRowStart}:{$colLetter}{$dataRowEnd})"
                    );
                    $sheet->getStyle("{$colLetter}{$totalsRow}")
                        ->getNumberFormat()
                        ->setFormatCode('#,##0.00');
                }

                // Add 2 blank rows after totals
                $notesStartRow = $totalsRow + 2;

                // Add Notes
                $sheet->setCellValue("A{$notesStartRow}", "Note:");
                $sheet->getStyle("A{$notesStartRow}")->getFont()->setBold(true);

                // Calculate the end column for notes (span 10 columns)
                $noteEndCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(10);

                // Add note points
                $notes = [

                ];

                foreach ($notes as $index => $note) {
                    $currentRow = $notesStartRow + $index;
                    $sheet->setCellValue("B{$currentRow}", $note);
                    // Merge cells for each note to span 10 columns
                    $sheet->mergeCells("B{$currentRow}:{$noteEndCol}{$currentRow}");
                    $sheet->getStyle("B{$currentRow}:{$noteEndCol}{$currentRow}")->getAlignment()
                        ->setWrapText(true)
                        ->setVertical('center');
                }

                // Add signature section (3 rows after the last note)
                $signatureRow = $notesStartRow + count($notes) + 3;

                // Add signatures
                $signatures = [

                ];

                foreach ($signatures as $col => $sign) {
                    // Name
                    $sheet->setCellValue("{$col}{$signatureRow}", $sign[0]);
                    // Designation
                    $sheet->setCellValue("{$col}" . ($signatureRow + 1), $sign[1]);
                    
                    // Center align the signature blocks and merge 4 columns for each signature
                    $endCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(
                        \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($col) + 3
                    );
                    
                    // Merge cells for name and designation
                    $sheet->mergeCells("{$col}{$signatureRow}:{$endCol}{$signatureRow}");
                    $sheet->mergeCells("{$col}" . ($signatureRow + 1) . ":{$endCol}" . ($signatureRow + 1));
                    
                    // Center align the merged cells
                    $sheet->getStyle("{$col}{$signatureRow}:{$endCol}" . ($signatureRow + 1))
                        ->getAlignment()
                        ->setHorizontal('center');
                }

                // Adjust row heights for notes and signatures
                $sheet->getRowDimension($notesStartRow)->setRowHeight(30);
                foreach (range($notesStartRow, $notesStartRow + count($notes) - 1) as $row) {
                    $sheet->getRowDimension($row)->setRowHeight(25);
                }
                $sheet->getRowDimension($signatureRow)->setRowHeight(40);
                $sheet->getRowDimension($signatureRow + 1)->setRowHeight(25);

                // Freeze panes
                $sheet->freezePane('A5');
            }
        ];
    }
}
