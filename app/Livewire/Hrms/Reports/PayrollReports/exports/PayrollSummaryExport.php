<?php

namespace App\Livewire\Hrms\Reports\PayrollReports\Exports;

use App\Models\Hrms\PayrollComponentsEmployeesTrack;
use App\Models\Hrms\SalaryComponent;
use App\Models\Hrms\Employee;
use App\Models\Hrms\SalaryArrear;
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

class PayrollSummaryExport extends DefaultValueBinder implements FromCollection, WithHeadings, WithMapping, WithStyles, WithEvents, WithCustomValueBinder
{
    protected $filters;
    protected $start;
    protected $end;
    protected $earnings;
    protected $deductions;
    protected $allComponents;   
    protected $data;
    protected $rows; // flattened rows including arrear rows
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

        // First, collect the employees and their payroll tracks
        $this->data = $this->fetchEmployees($firmId);

        // Build flattened rows: normal salary row + one arrear row per arrear record
        $this->rows = collect();

        // Extract unique component IDs from payroll tracks and arrears
        $componentIds = [];

        foreach ($this->data as $employee) {
            // add regular salary row
            $this->rows->push([
                'type' => 'salary',
                'employee' => $employee,
            ]);

            // collect component ids from payroll tracks
            if ($employee->payroll_tracks) {
                foreach ($employee->payroll_tracks as $track) {
                    $componentIds[$track->salary_component_id] = true;
                }
            }

            // Determine employee payroll slot for this period (if any)
            $employeeSlotId = optional($employee->payroll_tracks->first())->payroll_slot_id;

            // fetch arrears for this employee disbursed in this slot (fallback to date range)
            $arrears = $this->fetchEmployeeArrears($firmId, $employee->id, $employeeSlotId);
            // Aggregate arrears by effective month (M Y) and component
            $arrearsByMonth = [];
            foreach ($arrears as $arrear) {
                if ($arrear->salary_component_id) {
                    $componentIds[$arrear->salary_component_id] = true;
                }
                $monthKey = $arrear->effective_from ? Carbon::parse($arrear->effective_from)->format('M Y') : 'Arrears';
                $remaining = max(0, (float)$arrear->total_amount - (float)$arrear->paid_amount);
                $amountToShow = (float)($arrear->installment_amount ?: 0);
                if ($amountToShow <= 0) {
                    $amountToShow = $remaining > 0 ? $remaining : (float)$arrear->total_amount;
                }
                if (!isset($arrearsByMonth[$monthKey])) {
                    $arrearsByMonth[$monthKey] = [];
                }
                if (!isset($arrearsByMonth[$monthKey][$arrear->salary_component_id])) {
                    $arrearsByMonth[$monthKey][$arrear->salary_component_id] = 0;
                }
                $arrearsByMonth[$monthKey][$arrear->salary_component_id] += round($amountToShow, 2);
            }
            foreach ($arrearsByMonth as $monthKey => $componentTotals) {
                $this->rows->push([
                    'type' => 'arrear',
                    'employee' => $employee,
                    'arrear_month' => $monthKey,
                    'arrear_amounts' => $componentTotals,
                ]);
            }
        }

        // Only fetch the components that are actually used by the selected employees (tracks + arrears)
        $this->allComponents = !empty($componentIds)
            ? SalaryComponent::whereIn('id', array_keys($componentIds))
                ->where('firm_id', $firmId)
                ->get()
            : collect([]);

        $this->earnings = $this->allComponents->where('nature', 'earning')->values();
        $this->deductions = $this->allComponents->where('nature', 'deduction')->values();
    }

    // Helper method to fetch employees
    private function fetchEmployees($firmId)
    {
        $query = Employee::with([
            'emp_job_profile.department',
            'emp_job_profile.joblocation',
            'emp_personal_detail',
            'salary_execution_groups',
            'payroll_tracks' => function ($q) {
                $q->whereBetween('salary_period_from', [$this->start, $this->end]);
            }
        ])->where('firm_id', $firmId)
          // Ensure only employees having at least one payroll track in range are included
          ->whereHas('payroll_tracks', function ($q) {
              $q->whereBetween('salary_period_from', [$this->start, $this->end]);
          });

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

        $employees = $query->get();

        // For each employee, determine their payroll slot for the period and exclude if on hold
        $filtered = $employees->filter(function($employee) use ($firmId) {
            // Check if employee was hired before or during the report period
            $jobProfile = $employee->emp_job_profile;
            if ($jobProfile && $jobProfile->doh) {
                // If employee was hired after the end of the report period, exclude them
                if ($jobProfile->doh->isAfter($this->end)) {
                    return false;
                }
            }
            
            // Find the payroll slot for this employee for the period (from their payroll_tracks)
            $payrollTrack = $employee->payroll_tracks->first();
            if (!$payrollTrack || !$payrollTrack->payroll_slot_id) {
                // No payroll tracks/slot for this period — exclude from report as requested
                return false;
            }
            $payrollSlotId = $payrollTrack->payroll_slot_id;
            // Check if this employee is on hold for this slot
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

    // Fetch arrears for a given employee within the selected period
    private function fetchEmployeeArrears($firmId, $employeeId, $payrollSlotId = null)
    {
        $q = SalaryArrear::query()
            ->where('firm_id', $firmId)
            ->where('employee_id', $employeeId)
            ->whereNull('deleted_at')
            ->where('is_inactive', false)
            ->whereIn('arrear_status', ['pending', 'partially_paid']);

        if ($payrollSlotId) {
            // Include arrears explicitly scheduled for this payroll slot (regardless of effective month)
            // OR arrears without a slot but whose effective_from falls within the report period
            $q->where(function($sub) use ($payrollSlotId) {
                $sub->where('disburse_wef_payroll_slot_id', $payrollSlotId)
                    ->orWhere(function($s) {
                        $s->whereNull('disburse_wef_payroll_slot_id')
                          ->whereBetween('effective_from', [$this->start, $this->end]);
                    });
            });
        } else {
            // No slot info; fallback to effective_from within the report period
            $q->whereBetween('effective_from', [$this->start, $this->end]);
        }

        return $q->get();
    }

    public function collection()
    {
        // Return the flattened rows collection for mapping
        return $this->rows ?? collect();
    }

    // Only the FINAL header row should go here!
    public function headings(): array
    {
        // Row 1: Employee fields (will be merged down)
        $empFields = [
            'S.No',
            'Employee Code',
            'Paylevel',
            'Employee Name',
            'Department',
            'Designation',
            'Sub-center Name',
            'PAN Card',
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
            array_fill(0, 8, ''), // Empty cells for employee fields (will be merged)
            $this->earnings->pluck('title')->toArray(),
            [''],  // Empty cell for Total Earnings (will be merged)
            $this->deductions->pluck('title')->toArray(),
            ['', '']  // Empty cells for Total Deductions and Net Pay (will be merged)
        );

        return [$headers, $subHeaders];
    }

    public function map($item): array
    {
        static $serial = 1;
        $type = $item['type'] ?? 'salary';
        $employee = $item['employee'];
        $job = $employee->emp_job_profile;
        $personal = $employee->emp_personal_detail;

        if ($type === 'salary') {
            // Exclude arrear tracks from the regular salary row to avoid double counting
            $payrollTracks = collect($employee->payroll_tracks)
                ->filter(function ($t) {
                    return ($t->component_type ?? null) !== 'salary_arrear';
                });
            $trackByComponent = $payrollTracks
                ->groupBy('salary_component_id')
                ->map(fn($items) => round($items->sum('amount_payable'), 2));

            $row = [
                $serial++,
                $job->employee_code ?? '',
                $job->paylevel ?? '',
                trim("{$employee->fname} {$employee->lname}"),
                $job->department->title ?? '',
                $job->designation->title ?? '',
                $job->joblocation->name ?? '',
                $personal->panno ?? '',
            ];

            $earnTotal = 0;
            foreach ($this->earnings as $component) {
                $amount = round($trackByComponent[$component->id] ?? 0, 2);
                $row[] = $amount;
                $earnTotal += $amount;
            }
            $row[] = round($earnTotal, 2);

            $dedTotal = 0;
            foreach ($this->deductions as $component) {
                $amount = round($trackByComponent[$component->id] ?? 0, 2);
                $row[] = $amount;
                $dedTotal += $amount;
            }
            $row[] = round($dedTotal, 2);

            $row[] = round($earnTotal - $dedTotal, 2);

            return $row;
        }

        // arrear row (aggregated per month)
        $arrearMonth = $item['arrear_month'] ?? '';

        $row = [
            $serial++,
            $job->employee_code ?? '',
            "Arrears {$arrearMonth}",
            trim("{$employee->fname} {$employee->lname}"),
            $job->department->title ?? '',
            $job->designation->title ?? '',
            $job->joblocation->name ?? '',
            $personal->panno ?? '',
        ];

        // Component-wise aggregated arrear amounts for this month
        $arrearComponentTotals = collect($item['arrear_amounts'] ?? []);

        $earnTotal = 0;
        foreach ($this->earnings as $component) {
            $amount = round((float) ($arrearComponentTotals[$component->id] ?? 0), 2);
            $row[] = $amount;
            $earnTotal += $amount;
        }
        $row[] = round($earnTotal, 2);

        $dedTotal = 0;
        foreach ($this->deductions as $component) {
            // Only place amount if the arrear component is actually a deduction
            $amount = round((float) ($arrearComponentTotals[$component->id] ?? 0), 2);
            $row[] = $amount;
            $dedTotal += $amount;
        }
        $row[] = round($dedTotal, 2);

        $row[] = round($earnTotal - $dedTotal, 2);

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
                $empColCount = 8; // Updated for new columns
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

                // Merge Earnings header horizontally (only if there are earnings)
                if ($earnCount > 0) {
                    $earnStart = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($empColCount + 1);
                    $earnEnd = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($empColCount + $earnCount);
                    $sheet->mergeCells("{$earnStart}3:{$earnEnd}3");
                }

                // Merge Deductions header horizontally (only if there are deductions)
                if ($dedCount > 0) {
                    // Calculate the correct start column for deductions
                    $dedStartCol = $empColCount + $earnCount + 2; // After employee cols + earnings + total earnings
                    $dedEndCol = $dedStartCol + $dedCount - 1;
                    
                    $dedStart = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($dedStartCol);
                    $dedEnd = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($dedEndCol);
                    $sheet->mergeCells("{$dedStart}3:{$dedEnd}3");
                }

                // Merge Total columns vertically
                $totalEarnCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($empColCount + $earnCount + 1);
                $sheet->mergeCells("{$totalEarnCol}3:{$totalEarnCol}4");

                // Calculate the correct column for total deductions
                $totalDedCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($empColCount + $earnCount + $dedCount + 2);
                $sheet->mergeCells("{$totalDedCol}3:{$totalDedCol}4");

                // Calculate the correct column for net pay
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
                for (
                    $colIdx = 1;
                    $colIdx <= \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($lastCol);
                    $colIdx++
                ) {
                    $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
                    $sheet->getColumnDimension($colLetter)->setAutoSize(true);
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

    // Ensure Paylevel column is always text in Excel and round financial values
    public function bindValue(Cell $cell, $value)
    {
        // Paylevel is column C (3rd column)
        if ($cell->getColumn() === 'C' && $cell->getRow() > 4) {
            $cell->setValueExplicit((string)$value, DataType::TYPE_STRING);
            return true;
        }
        
        // Round financial values to 2 decimal places for all numeric columns
        if (is_numeric($value) && $cell->getRow() > 4) {
            $cell->setValueExplicit(round((float)$value, 2), DataType::TYPE_NUMERIC);
            return true;
        }
        
        return parent::bindValue($cell, $value);
    }
}
