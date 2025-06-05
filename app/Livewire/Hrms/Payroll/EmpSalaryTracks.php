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

    public function mount($slotId = null)
    {
        $this->slotId = $slotId;
        $this->initListsForFields();

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

    #[Computed]
    public function list()
    {
        $tracks = PayrollComponentsEmployeesTrack::query()
            ->where('firm_id', Session::get('firm_id'))
            ->when($this->slotId, fn($query) =>
                $query->where('payroll_slot_id', $this->slotId))
            ->when($this->filters['employee_id'], fn($query, $value) =>
                $query->where('employee_id', $value))
            ->with(['employee', 'salary_component', 'payroll_slot'])
            ->get()
            ->groupBy(function ($track) {
                // Create a unique key combining employee_id and period
                return $track->employee_id . '_' . $track->salary_period_from->format('Y-m-d') . '_' . $track->salary_period_to->format('Y-m-d');
            })
            ->map(function ($employeeTracks) {
                $firstTrack = $employeeTracks->first();
                $income = $employeeTracks->where('nature', 'earning')->sum('amount_payable');
                $deduction = $employeeTracks->where('nature', 'deduction')->sum('amount_payable');
                $netSalary = $income - $deduction;

                return [
                    'id' => $firstTrack->employee_id . '_' . $firstTrack->salary_period_from->format('Y-m-d') . '_' . $firstTrack->salary_period_to->format('Y-m-d'),
                    'employee_name' => $firstTrack->employee->fname . ' ' . $firstTrack->employee->lname,
                    'income' => $income,
                    'deduction' => $deduction,
                    'net_salary' => $netSalary,
                    'period' => $firstTrack->salary_period_from->format('jS F Y') . ' to ' . $firstTrack->salary_period_to->format('jS F Y'),
                    'employee_id' => $firstTrack->employee_id,
                    'from_date' => $firstTrack->salary_period_from,
                    'to_date' => $firstTrack->salary_period_to
                ];
            })
            ->values();

        // Convert to paginator
        $page = $this->page ?? 1;
        $perPage = $this->perPage;
        $items = $tracks->slice(($page - 1) * $perPage, $perPage);

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $tracks->count(),
            $perPage,
            $page,
            ['path' => request()->url()]
        );
    }

    public function showSalarySlip($employeeId, $fromDate = null, $toDate = null)
    {
        try {
            // Load employee with job profile
            $this->selectedEmployee = Employee::with('emp_job_profile.department', 'emp_job_profile.designation')
                ->findOrFail($employeeId);

            $this->selectedEmployeeName = $this->selectedEmployee->fname . ' ' . $this->selectedEmployee->lname;

            // Get salary components from PayrollComponentsEmployeesTrack
            $query = PayrollComponentsEmployeesTrack::where('employee_id', $employeeId)
                ->where('firm_id', Session::get('firm_id'));

            // Add date range filter if provided
            if ($fromDate && $toDate) {
                $query->where('salary_period_from', $fromDate)
                    ->where('salary_period_to', $toDate);
            }

            $this->rawComponents = $query->with(['salary_component'])->get();

           dd($this->rawComponents->toArray());

            $this->salaryComponents = [];
            $this->totalEarnings = 0;
            $this->totalDeductions = 0;

            // Group components by nature (earnings/deductions)
            foreach ($this->rawComponents as $component) {
                $componentData = [
                    'title' => $component->salary_component->title,
                    'amount' => $component->amount_payable,
                    'nature' => $component->nature,
                    'component_type' => $component->component_type,
                    'amount_type' => $component->amount_type
                ];

                $this->salaryComponents[] = $componentData;

                // Calculate totals based on nature
                if ($component->nature === 'earning') {
                    $this->totalEarnings += $component->amount_payable;
                } elseif ($component->nature === 'deduction') {
                    $this->totalDeductions += $component->amount_payable;
                }
            }

            // Sort components by nature and title
            $this->salaryComponents = collect($this->salaryComponents)->sortBy([
                ['nature', 'desc'], // earnings first
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

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Payroll/blades/emp-salary-tracks.blade.php'));
    }
}

