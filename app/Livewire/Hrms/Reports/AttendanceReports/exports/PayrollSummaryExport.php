<?php

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

public function __construct($filters)
{
$this->filters = $filters;

$this->start = Carbon::parse($filters['date_range']['start'])->startOfDay();
$this->end   = Carbon::parse($filters['date_range']['end'])->endOfDay();
$firmId = $filters['firm_id'] ?? session('firm_id');

// Fetch all unique component IDs in the period/firm
$componentIds = PayrollComponentsEmployeesTrack::where('firm_id', $firmId)
->whereBetween('salary_period_from', [$this->start, $this->end])
->pluck('salary_component_id')
->unique()
->toArray();

// Fetch the component objects, group by nature
$this->allComponents = SalaryComponent::whereIn('id', $componentIds)
->orderBy('sequence')
->get();
$this->earnings = $this->allComponents->where('nature', 'earning')->values();
$this->deductions = $this->allComponents->where('nature', 'deduction')->values();
}

public function collection()
{
$firmId = $this->filters['firm_id'] ?? session('firm_id');

return $this->data = Employee::with([
'emp_job_profile.department',
'payroll_tracks' => function ($q) {
$q->whereBetween('salary_period_from', [$this->start, $this->end]);
}
])->where('firm_id', $firmId)
->when(!empty($this->filters['employee_id']), fn($q) =>
$q->whereIn('id', $this->filters['employee_id'])
)->get();
}

public function headings(): array
{
// 1st row: Title
// 2nd row: Subtitle
// 3rd row: Main merged headers (Earnings, Deductions)
// 4th row: Actual head names
$earnCount = $this->earnings->count();
$dedCount = $this->deductions->count();

// Employee fields
$empFields = ['S.No', 'Employee Code', 'Employee Name', 'Department', 'Designation'];

// Row 1: (titles set in event)
// Row 2: (subtitle set in event)
// Row 3:
$header3 = array_merge($empFields, [
...array_fill(0, $earnCount, null), // placeholder for earning merges
'Total Earnings',
...array_fill(0, $dedCount, null),  // placeholder for deduction merges
'Total Deductions',
'Net Pay',
]);
// Set merged section labels only at correct start index:
$header3[count($empFields)] = "Earnings";
$header3[count($empFields) + $earnCount + 1] = "Deductions";

// Row 4: Head titles
$header4 = array_merge($empFields,
$this->earnings->pluck('title')->toArray(),
['Total Earnings'],
$this->deductions->pluck('title')->toArray(),
['Total Deductions', 'Net Pay']
);

return [
$header3,
$header4
];
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

// Cell styling (titles, headers)
public function styles(Worksheet $sheet)
{
// We'll do most formatting in the AfterSheet event for dynamic merges
return [
// Optionally, bold titles/headers here
];
}

// Handle merges, formulas, bold, align, vertical totals
public function registerEvents(): array
{
return [
AfterSheet::class => function(AfterSheet $event) {
$sheet = $event->sheet->getDelegate();

$rowOffset = 2; // Titles: row 1 and 2, data starts from row 3

// Dynamic column count
$empColCount = 5;
$earnCount = $this->earnings->count();
$dedCount = $this->deductions->count();
$totalCols = $empColCount + $earnCount + 1 + $dedCount + 2;

$lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($totalCols);

// Set titles (merged across all columns)
$sheet->mergeCells("A1:{$lastCol}1");
$sheet->setCellValue("A1", "INDIAN INSTITUTE OF MANAGEMENT SIRMAUR");
$sheet->mergeCells("A2:{$lastCol}2");
$sheet->setCellValue("A2", "SALARY OF EMPLOYEE FOR THE MONTH OF ..."); // customize as needed

// Merge "Earnings" header
$earnStart = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($empColCount + 1);
$earnEnd = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($empColCount + $earnCount);
$sheet->mergeCells("{$earnStart}3:{$earnEnd}3");
$sheet->setCellValue("{$earnStart}3", "Earnings");

// Merge "Deductions" header
$dedStart = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($empColCount + $earnCount + 2);
$dedEnd = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($empColCount + $earnCount + 1 + $dedCount);
$sheet->mergeCells("{$dedStart}3:{$dedEnd}3");
$sheet->setCellValue("{$dedStart}3", "Deductions");

// Merge static fields in headers
for ($i = 1; $i <= $empColCount; $i++) {
$col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
$sheet->mergeCells("{$col}3:{$col}4");
}
// Total Earnings/Deductions/Net Pay merges
$col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($empColCount + $earnCount + 1);
$sheet->mergeCells("{$col}3:{$col}4");
$col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($empColCount + $earnCount + 1 + $dedCount + 1);
$sheet->mergeCells("{$col}3:{$col}4");
$col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($empColCount + $earnCount + 1 + $dedCount + 2);
$sheet->mergeCells("{$col}3:{$col}4");

// Set row heights, bold, alignment
$sheet->getStyle("A1:{$lastCol}4")->getFont()->setBold(true);
$sheet->getStyle("A1:{$lastCol}4")->getAlignment()->setHorizontal('center');
$sheet->getStyle("A1:{$lastCol}4")->getAlignment()->setVertical('center');

// Add vertical totals for each numeric column after last data row
$dataRowStart = 5;
$dataRowEnd = $sheet->getHighestRow();

// Totals row (after data)
$totalsRow = $dataRowEnd + 1;
$sheet->setCellValue("A{$totalsRow}", 'Grand Total');
for ($colIdx = $empColCount + 1; $colIdx <= $totalCols; $colIdx++) {
$colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
$sheet->setCellValue("{$colLetter}{$totalsRow}", "=SUM({$colLetter}{$dataRowStart}:{$colLetter}{$dataRowEnd})");
}
}
];
}
}
