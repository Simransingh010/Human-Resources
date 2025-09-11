<?php

namespace App\Livewire\Hrms\Reports\PayrollReports\Exports;

use App\Models\Hrms\PayrollComponentsEmployeesTrack;
use App\Models\Hrms\PayrollSlot;
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
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;

class NpsReportExport extends DefaultValueBinder implements FromCollection, WithHeadings, WithMapping, WithStyles, WithEvents, WithCustomValueBinder
{
    protected $filters;
    protected $start;
    protected $end;
    protected $npsComponents;
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

        // First, collect the data to determine which NPS components are actually used
        // Get all employee contribution deduction components for the firm
        $this->npsComponents = SalaryComponent::where('firm_id', $firmId)
            ->where('component_type', 'employee_contribution')
            ->where('nature', 'deduction')
            ->get();

        // Now collect the data with employees filtered to only those
        // who have NPS components assigned to them
        $this->data = $this->fetchEmployees($firmId);

    }

    // Helper method to fetch employees
    private function fetchEmployees($firmId)
    {
        $query = Employee::with([
            'emp_job_profile',
            'emp_job_profile.department',
            'payroll_tracks' => function ($q) {
                $q->whereBetween('salary_period_from', [$this->start, $this->end])
                  ->whereHas('salary_component', function($q) {
                      $q->where('component_type', 'employee_contribution')
                        ->where('nature', 'deduction');
                  });
            }
        ])->where('firm_id', $firmId);

        // Compute relevant payroll slots overlapping the report period (and group if filtered)
        $slotQuery = PayrollSlot::where('firm_id', $firmId)
            ->where('from_date', '<=', $this->end)
            ->where('to_date', '>=', $this->start);
        if (!empty($this->filters['salary_execution_group_id'])) {
            $slotQuery->where('salary_execution_group_id', $this->filters['salary_execution_group_id']);
        }
        $relevantSlotIds = $slotQuery->pluck('id');

        // Ensure only employees that have NPS components assigned in salary_components_employees
        $npsComponentIds = $this->npsComponents
            ->filter(function ($component) {
                $title = strtolower(trim($component->title ?? ''));
                // Consider any component with 'nps' in its title as an NPS component
                return strpos($title, 'nps') !== false;
            })
            ->pluck('id');

        if ($npsComponentIds->isEmpty()) {
            // No NPS components configured → no employees are eligible
            $query->whereRaw('1 = 0');
        } else {
            $start = $this->start;
            $end = $this->end;
            $query->whereHas('salary_components_employees', function ($q) use ($npsComponentIds, $start, $end, $firmId) {
                $q->where('firm_id', $firmId)
                  ->whereIn('salary_component_id', $npsComponentIds)
                  // Effective during the period
                  ->where('effective_from', '<=', $end)
                  ->where(function ($eff) use ($start) {
                      $eff->whereNull('effective_to')
                          ->orWhere('effective_to', '>=', $start);
                  });
            });
        }

        // Exclude employees on hold for any relevant payroll slot in the period
        if ($relevantSlotIds->isNotEmpty()) {
            $ids = $relevantSlotIds->toArray();
            $query->whereNotExists(function ($sub) use ($firmId, $ids) {
                $sub->selectRaw('1')
                    ->from('salary_holds as sh')
                    ->whereColumn('sh.employee_id', 'employees.id')
                    ->where('sh.firm_id', $firmId)
                    ->whereIn('sh.payroll_slot_id', $ids);
            });
        }

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

        $employees = $query->get();

        // Exclude employees hired after the period end
        $filtered = $employees->filter(function ($employee) use ($firmId) {
            // Check if employee was hired before or during the report period
            $jobProfile = $employee->emp_job_profile;
            if ($jobProfile && $jobProfile->doh) {
                // If employee was hired after the end of the report period, exclude them
                if ($jobProfile->doh->isAfter($this->end)) {
                    return false;
                }
            }

            return true;
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
        // Define headers to match the image
        $headers = [
            'SR. NO.',
            'EMP ID',
            'Name',
            'PRAN NO',
            'NPS (14%)',
            'NPS (10%)',
            'Voluntary Contribution for NPS',
            'Total'
        ];

        return [$headers];
    }

    public function map($employee): array
    {
        static $serial = 1;
        $job = $employee->emp_job_profile;

        $payrollTracks = collect($employee->payroll_tracks);
        $trackByComponent = $payrollTracks
            ->groupBy('salary_component_id')
            ->map(fn($items) => $items->sum('amount_payable'));

        // Find NPS 14% and NPS 10% components
        $nps14Amount = 0;
        $nps10Amount = 0;
        $voluntaryContribution = 0;
        
        foreach ($this->npsComponents as $component) {
            $amount = $trackByComponent[$component->id] ?? 0;
            $title = strtolower(trim($component->title));
            if (strpos($title, '14%') !== false || 
                (strpos($title, 'nps') !== false && strpos($title, 'employer') !== false)) {
                $nps14Amount = $amount;
            } else if (strpos($title, '10%') !== false || 
                      (strpos($title, 'nps') !== false && strpos($title, 'employee') !== false)) {
                $nps10Amount = $amount;
            }
            // Robust match for Voluntary Contribution for NPS
            else if (strpos($title, 'voluntary contribution for nps') !== false) {
                $voluntaryContribution = $amount;

            }
        }
        
        // Calculate total (now includes voluntary contribution)
        $npsTotal = $nps14Amount + $nps10Amount + $voluntaryContribution;
        
        $row = [
            $serial++,
            $job->employee_code ?? '',
            trim("Dr. {$employee->fname} {$employee->lname}"),
            $job->pran_number ?? '',
            $nps14Amount,
            $nps10Amount,
            $voluntaryContribution ,
            $npsTotal
        ];

        return $row;
    }

    public function styles(Worksheet $sheet)
    {
        // We'll do most formatting in AfterSheet for dynamic merges
        return [];
    }

    public function bindValue(Cell $cell, $value)
    {
        // Column D contains PRAN numbers
        if ($cell->getColumn() === 'D') {
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

                // Insert 2 rows at the top for title
                $sheet->insertNewRowBefore(1, 2);

                // Dynamic column counts - now we have 8 columns
                $totalCols = 8;
                $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($totalCols);

                // Set titles (row 1 and 2)
                $sheet->mergeCells("A1:{$lastCol}1");
                $sheet->setCellValue("A1", strtoupper($this->firmName));
                $sheet->getStyle("A1")->getFont()->setSize(16)->setBold(true);
                $sheet->getStyle("A1")->getAlignment()->setHorizontal('center');

                $sheet->mergeCells("A2:{$lastCol}2");
                $sheet->setCellValue("A2", "Detail of NPS for the M/o " . Carbon::parse($this->start)->format('F-Y'));
                $sheet->getStyle("A2")->getFont()->setSize(14)->setBold(true);
                $sheet->getStyle("A2")->getAlignment()->setHorizontal('center');

                // Headers start at row 3
                // Set styles for all headers
                $sheet->getStyle("A3:{$lastCol}3")->getFont()->setBold(true);
                $sheet->getStyle("A3:{$lastCol}3")->getAlignment()->setHorizontal('center');
                $sheet->getStyle("A3:{$lastCol}3")->getAlignment()->setVertical('center');

                // Add thick borders to header cells
                $sheet->getStyle("A3:{$lastCol}3")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM);

                // Auto width for all columns
                foreach (range('A', $lastCol) as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }

                // Data rows start at row 4
                $dataRowStart = 4;
                $dataRowEnd = $sheet->getHighestRow();

                // Check if we have any data rows
                if ($dataRowEnd >= $dataRowStart) {
                    // Add thick borders to data cells
                    $sheet->getStyle("A{$dataRowStart}:{$lastCol}{$dataRowEnd}")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM);

                    // Format numeric columns (E, F, G)
                    foreach (['E', 'F', 'G'] as $col) {
                        $sheet->getStyle("{$col}{$dataRowStart}:{$col}{$dataRowEnd}")
                            ->getNumberFormat()
                            ->setFormatCode('#,##0.00');
                    }

                    // Grand Total row
                    $totalsRow = $dataRowEnd + 1;
                    $sheet->setCellValue("A{$totalsRow}", 'Grand Total');
                    $sheet->getStyle("A{$totalsRow}:{$lastCol}{$totalsRow}")->getFont()->setBold(true);
                    $sheet->getStyle("A{$totalsRow}:{$lastCol}{$totalsRow}")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM);

                    // Calculate totals for columns E, F, G
                    foreach (['E', 'F', 'G'] as $col) {
                        $sheet->setCellValue(
                            "{$col}{$totalsRow}", 
                            "=SUM({$col}{$dataRowStart}:{$col}{$dataRowEnd})"
                        );
                        $sheet->getStyle("{$col}{$totalsRow}")
                            ->getNumberFormat()
                            ->setFormatCode('#,##0.00');
                    }
                } else {
                    // No data rows, add a "No Data" message
                    $noDataRow = 4;
                    $sheet->mergeCells("A{$noDataRow}:{$lastCol}{$noDataRow}");
                    $sheet->setCellValue("A{$noDataRow}", "No data found for the selected criteria");
                    $sheet->getStyle("A{$noDataRow}")->getAlignment()->setHorizontal('center');
                    $sheet->getStyle("A{$noDataRow}")->getFont()->setBold(true);
                }

                // Freeze panes
                $sheet->freezePane('A4');
            }
        ];
    }
}
