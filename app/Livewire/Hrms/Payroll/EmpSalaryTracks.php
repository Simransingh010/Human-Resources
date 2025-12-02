<?php

namespace App\Livewire\Hrms\Payroll;

use App\Models\Hrms\PayrollComponentsEmployeesTrack;
use App\Models\Hrms\Employee;
use App\Models\Hrms\SalaryComponent;
use App\Models\Hrms\SalaryComponentGroup;
use App\Models\Hrms\PayrollSlot;
use App\Models\Hrms\SalaryTemplate;
use App\Models\Hrms\SalaryCycle;
use App\Models\Hrms\SalaryExecutionGroup;
use App\Models\Hrms\EmployeesSalaryExecutionGroup;
use App\Models\Hrms\SalaryComponentsEmployee;
use App\Models\Hrms\EmployeesSalaryDay;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Session;
use Flux;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\View;

class EmpSalaryTracks extends Component
{
    use WithPagination;

    public $perPage = 10;
    public $sortBy = 'id';
    public $sortDirection = 'desc';
    public $slotId = null;

    // Salary Slip Modal Properties
    public $showSalarySlipModal = false;
    public $selectedEmployee = null;
    public $selectedEmployeeName = '';
    public $salaryComponents = [];

    public $firmWideLogo = null; // Assuming this is set somewhere in your application
    public $firmSquareLogo = null;


    public $totalEarnings = 0;
    public $totalDeductions = 0;
    public $netSalary = 0;
    public $netSalaryInWords = '';
    public $rawComponents = null;

    // Field configuration for form and table
    public array $fieldConfig = [
        'employee_name' => ['label' => 'Employee', 'type' => 'text'],
        'income' => ['label' => 'Income', 'type' => 'number'],
        'deduction' => ['label' => 'Deduction', 'type' => 'number'],
        'net_salary' => ['label' => 'Net Salary', 'type' => 'number'],
        'period' => ['label' => 'Period', 'type' => 'text']
    ];
    // Filter fields configuration
    public array $filterFields = [
        'employee_id' => ['label' => 'Employee', 'type' => 'select', 'listKey' => 'employees']
    ];

    public array $listsForFields = [];
    public array $filters = [];
    public array $visibleFields = [];
    public array $visibleFilterFields = [];

    public $readyToLoad = false;

    public function mount($slotId = null)
    {
        $this->resetPage();
        $this->slotId = $slotId;

        // Set default visible fields
        $this->visibleFields = [
            'employee_name',
            'income',
            'deduction',
            'net_salary',
            'period'
        ];

        $this->visibleFilterFields = [
            'employee_id'
        ];

        // Initialize filters
        $this->filters = array_fill_keys(array_keys($this->filterFields), '');
    }

    public function loadData()
    {
        if ($this->readyToLoad) {
            return;
        }

        $this->initListsForFields();
        $this->readyToLoad = true;
    }

    protected function initListsForFields(): void
    {
        // Get employees for dropdown
        $this->listsForFields['employees'] = Employee::where('firm_id', Session::get('firm_id'))
            ->where('is_inactive', false)
            ->get()
            ->mapWithKeys(function ($employee) {
                return [$employee->id => $employee->fname . ' ' . $employee->lname];
            })
            ->toArray();
    }

    public function applyFilters()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->filters = array_fill_keys(array_keys($this->filterFields), '');
        $this->resetPage();
    }

    public function toggleColumn(string $field)
    {
        if (in_array($field, $this->visibleFields)) {
            $this->visibleFields = array_filter(
                $this->visibleFields,
                fn($f) => $f !== $field
            );
        } else {
            $this->visibleFields[] = $field;
        }
    }

    public function toggleFilterColumn(string $field)
    {
        if (in_array($field, $this->visibleFilterFields)) {
            $this->visibleFilterFields = array_filter(
                $this->visibleFilterFields,
                fn($f) => $f !== $field
            );
        } else {
            $this->visibleFilterFields[] = $field;
        }
    }

    /**
     * Returns paginated salary tracks, grouped and aggregated in SQL for optimal performance.
     * Uses Eloquent's paginate() so Livewire pagination works natively.
     */
    #[Computed]
    public function salaryTracks()
    {
        $firmId = Session::get('firm_id');
        $query = PayrollComponentsEmployeesTrack::query()
            ->selectRaw('
                employee_id,
                payroll_slot_id,
                MIN(salary_period_from) as from_date,
                MAX(salary_period_to) as to_date,
                SUM(CASE WHEN nature = "earning" THEN amount_payable ELSE 0 END) as income,
                SUM(CASE WHEN nature = "deduction" THEN amount_payable ELSE 0 END) as deduction
            ')
            ->where('firm_id', $firmId)
            ->when($this->slotId, fn($q) => $q->where('payroll_slot_id', $this->slotId))
            ->when($this->filters['employee_id'], fn($q, $value) => $q->where('employee_id', $value))
            ->groupBy('employee_id', 'payroll_slot_id', 'salary_period_from', 'salary_period_to')
            ->orderByRaw('MIN(salary_period_from) DESC, employee_id')
            ->with(['employee']);

        // Paginate the grouped/aggregated results
        $paginated = $query->paginate($this->perPage);

        // Transform each row to match the previous structure
        $paginated->getCollection()->transform(function ($row) {
            $employee = $row->employee;
            $fromDate = $row->from_date instanceof \Carbon\Carbon ? $row->from_date : \Carbon\Carbon::parse($row->from_date);
            $toDate = $row->to_date instanceof \Carbon\Carbon ? $row->to_date : \Carbon\Carbon::parse($row->to_date);
            $netSalary = $row->income - $row->deduction;
            return [
                'id' => $row->employee_id . '_' . $fromDate->format('Y-m-d') . '_' . $toDate->format('Y-m-d'),
                'employee_name' => $employee ? ($employee->fname . ' ' . $employee->lname) : '',
                'income' => (float) $row->income,
                'deduction' => (float) $row->deduction,
                'net_salary' => (float) $netSalary,
                'period' => $fromDate->format('jS F Y') . ' to ' . $toDate->format('jS F Y'),
                'employee_id' => $row->employee_id,
                'from_date' => $fromDate,
                'to_date' => $toDate,
                'payroll_slot_id' => $row->payroll_slot_id,
            ];
        });

        return $paginated;
    }

    public function showSalarySlip($employeeId, $fromDate = null, $toDate = null, $payrollSlotId = null)
    {
        try {
            // Load employee with job profile
            $this->selectedEmployee = Employee::with('emp_personal_detail','emp_job_profile.department', 'emp_job_profile.designation')
                ->findOrFail($employeeId);

            $this->selectedEmployeeName = $this->selectedEmployee->fname . ' ' . $this->selectedEmployee->lname;

            // Get firm logos
            $firm = \App\Models\Saas\Firm::find(Session::get('firm_id'));
            if ($firm) {
                $this->firmSquareLogo = $firm->getFirstMediaUrl('squareLogo');
                $this->firmWideLogo = $firm->getFirstMediaUrl('wideLogo');
            }

            // Get salary components from PayrollComponentsEmployeesTrack
            $query = PayrollComponentsEmployeesTrack::where('employee_id', $employeeId)
                ->where('firm_id', Session::get('firm_id'));

            // Add date range filter if provided
            if ($fromDate && $toDate) {
                $query->where('salary_period_from', $fromDate)
                    ->where('salary_period_to', $toDate);
            }

            // Get the payroll slot ID from the parameters - this is what's causing duplicates
            if ($payrollSlotId === null) {
                if ($this->slotId) {
                    $payrollSlotId = $this->slotId;
                } else {
                    // Try to determine the payroll slot ID from the date range
                    $payrollSlot = PayrollSlot::where('firm_id', Session::get('firm_id'))
                        ->where('from_date', $fromDate)
                        ->where('to_date', $toDate)
                        ->first();
                    
                    if ($payrollSlot) {
                        $payrollSlotId = $payrollSlot->id;
                    }
                }
            }
            
            // Filter by specific payroll slot if available
            if ($payrollSlotId) {
                $query->where('payroll_slot_id', $payrollSlotId);
            }

            $this->rawComponents = $query->with(['salary_component.salary_component_group:id,title'])->get();

            $grouped = [];
            $ungrouped = [];

            foreach ($this->rawComponents as $component) {
                $groupId = $component->salary_component->salary_component_group_id ?? null;
                $nature = $component->nature;
                $isArrear = $component->component_type === 'salary_arrear';
                $arrearInfo = null;
                if ($isArrear && $component->salary_arrear_id) {
                    $arrear = \App\Models\Hrms\SalaryArrear::find($component->salary_arrear_id);
                    if ($arrear) {
                        $arrearInfo = [
                            'effective_from' => $arrear->effective_from,
                            'effective_to' => $arrear->effective_to,
                        ];
                    }
                }
                if ($groupId) {
                    $groupTitle = $component->salary_component->salary_component_group?->title ?? 'Other';
                    $grouped[$nature][$groupId]['title'] = $groupTitle;
                    $grouped[$nature][$groupId]['amount'] = ($grouped[$nature][$groupId]['amount'] ?? 0) + $component->amount_payable;
                } else {
                    $title = $component->salary_component->title;
                    if ($isArrear) {
                        $title .= ' (Arrear';
                        if ($arrearInfo) {
                            $from = $arrearInfo['effective_from'] ? \Carbon\Carbon::parse($arrearInfo['effective_from'])->format('M Y') : '';
                            $to = $arrearInfo['effective_to'] ? \Carbon\Carbon::parse($arrearInfo['effective_to'])->format('M Y') : '';
                            if ($from && $to && $from != $to) {
                                $title .= ' for ' . $from . ' - ' . $to;
                            } elseif ($from) {
                                $title .= ' for ' . $from;
                            } elseif ($to) {
                                $title .= ' for ' . $to;
                            }
                        }
                        $title .= ')';
                    }
                    $ungrouped[$nature][] = [
                        'title' => $title,
                        'amount' => $component->amount_payable,
                        'nature' => $nature,
                        'component_type' => $component->component_type,
                        'amount_type' => $component->amount_type,
                        'is_arrear' => $isArrear,
                        'arrear_info' => $arrearInfo
                    ];
                }
            }

            $this->salaryComponents = [];
            $this->totalEarnings = 0;
            $this->totalDeductions = 0;

            // Add grouped earnings
            if (!empty($grouped['earning'])) {
                foreach ($grouped['earning'] as $group) {
                    $this->salaryComponents[] = [
                        'title' => $group['title'],
                        'amount' => $group['amount'],
                        'nature' => 'earning',
                    ];
                    $this->totalEarnings += $group['amount'];
                }
            }
            // Add ungrouped earnings
            if (!empty($ungrouped['earning'])) {
                foreach ($ungrouped['earning'] as $comp) {
                    $this->salaryComponents[] = $comp;
                    $this->totalEarnings += $comp['amount'];
                }
            }
            // Add grouped deductions
            if (!empty($grouped['deduction'])) {
                foreach ($grouped['deduction'] as $group) {
                    $this->salaryComponents[] = [
                        'title' => $group['title'],
                        'amount' => $group['amount'],
                        'nature' => 'deduction',
                    ];
                    $this->totalDeductions += $group['amount'];
                }
            }
            // Add ungrouped deductions
            if (!empty($ungrouped['deduction'])) {
                foreach ($ungrouped['deduction'] as $comp) {
                    $this->salaryComponents[] = $comp;
                    $this->totalDeductions += $comp['amount'];
                }
            }

            // Sort by nature (earnings first), then title
            $this->salaryComponents = collect($this->salaryComponents)->sortBy([
                ['nature', 'desc'],
                ['title', 'asc']
            ])->values()->all();

            $this->netSalary = $this->totalEarnings - $this->totalDeductions;
            $this->netSalaryInWords = $this->numberToWords($this->netSalary);
            $this->showSalarySlipModal = true;

        } catch (\Exception $e) {
            Flux::toast(
                variant: 'error',
                heading: 'Error',
                text: 'Failed to load salary slip: ' . $e->getMessage(),
            );
        }
    }

    public function closeSalarySlipModal()
    {
        $this->showSalarySlipModal = false;
        $this->selectedEmployee = null;
        $this->salaryComponents = [];
        $this->totalEarnings = 0;
        $this->totalDeductions = 0;
        $this->netSalary = 0;
        $this->netSalaryInWords = '';
        $this->rawComponents = null;
    }

    protected function numberToWords($number)
    {
        $f = new \NumberFormatter("en", \NumberFormatter::SPELLOUT);
        return ucfirst($f->format($number)) . ' Rupees Only';
    }

    public function downloadPDF($employeeId, $fromDate = null, $toDate = null, $payrollSlotId = null)
    {
        try {
            // Load the same data as showSalarySlip (this will set selectedEmployee etc.)
            $this->showSalarySlip($employeeId, $fromDate, $toDate, $payrollSlotId);

            $firmSquareLogoData = null;
            $firmWideLogoData = null;

            // Re-fetch firm and prepare base64 encoded logos specifically for PDF
            $firm = \App\Models\Saas\Firm::find(Session::get('firm_id'));
            if ($firm) {
                $squareMedia = $firm->getFirstMedia('squareLogo');
                if ($squareMedia) {
                    $firmSquareLogoData = 'data:' . $squareMedia->mime_type . ';base64,' . base64_encode(file_get_contents($squareMedia->getPath()));
                }

                $wideMedia = $firm->getFirstMedia('wideLogo');
                if ($wideMedia) {
                    $firmWideLogoData = 'data:' . $wideMedia->mime_type . ';base64,' . base64_encode(file_get_contents($wideMedia->getPath()));
                }
            }

            // Generate PDF
            $pdf = PDF::loadView('livewire.hrms.payroll.blades.salary-slip-pdf', [
                'selectedEmployee' => $this->selectedEmployee,
                'salaryComponents' => $this->salaryComponents,
                'totalEarnings' => $this->totalEarnings,
                'totalDeductions' => $this->totalDeductions,
                'netSalary' => $this->netSalary,
                'rawComponents' => $this->rawComponents,
                'firmSquareLogo' => $firmSquareLogoData, // Pass base64 data
                'firmWideLogo' => $firmWideLogoData,     // Pass base64 data
                'netSalaryInWords' => $this->netSalaryInWords,
            ]);

            // Set paper size to A4
            $pdf->setPaper('a4');

            // Generate filename
            $filename = $this->selectedEmployeeName . '_Salary_Slip_' .
                       ($this->rawComponents->first() ?
                        date('F_Y', strtotime($this->rawComponents->first()->salary_period_from)) :
                        '') . '.pdf';

            // Download the PDF
            return response()->streamDownload(function () use ($pdf) {
                echo $pdf->output();
            }, $filename);

        } catch (\Exception $e) {
            Flux::toast(
                variant: 'error',
                heading: 'Error',
                text: 'Failed to generate PDF: ' . $e->getMessage(),
            );
        }
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Payroll/blades/emp-salary-tracks.blade.php'));
    }
}

