<?php

namespace App\Livewire\Hrms\Payroll;

use App\Models\Hrms\EmployeesSalaryDay;
use App\Models\Hrms\Employee;
use App\Models\Hrms\PayrollSlot;
use App\Models\Hrms\EmployeesSalaryExecutionGroup;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Session;
use Flux;
use Carbon\Carbon;

class LopAdjustmentStep extends Component
{
    use WithPagination;

    public $perPage = 10;
    public $sortBy = 'id';
    public $sortDirection = 'desc';
    public $slotId = null;

    // Edit Modal Properties
    public $showEditModal = false;
    public $selectedRecord = null;
    public $editForm = [
        'void_days_count' => 0,
        'lop_days_count' => 0,
        'lop_details' => '',
        'void_dates' => [], // for UI only
        'lop_dates' => [],  // for UI only
    ];

    // Field configuration for form and table
    public array $fieldConfig = [
        'employee_name' => ['label' => 'Employee', 'type' => 'text'],
        'cycle_days' => ['label' => 'Cycle Days', 'type' => 'number'],
        'void_days_count' => ['label' => 'Void Days', 'type' => 'number'],
        'lop_days_count' => ['label' => 'LOP Days', 'type' => 'number'],
        'lop_details' => ['label' => 'LOP Details', 'type' => 'textarea']
    ];

    // Filter fields configuration
    public array $filterFields = [
        'employee_id' => ['label' => 'Employee', 'type' => 'select', 'listKey' => 'employees']
    ];

    public array $listsForFields = [];
    public array $filters = [];
    public array $visibleFields = [];
    public array $visibleFilterFields = [];

    public function mount($slotId)
    {
        $this->slotId = $slotId;
        $this->ensureSalaryDayRowsForSlot();
        $this->initListsForFields();

        // Set default visible fields
        $this->visibleFields = [
            'employee_name',
            'cycle_days',
            'void_days_count',
            'lop_days_count'
            
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
        $query = EmployeesSalaryDay::query()
            ->where('firm_id', Session::get('firm_id'))
            ->when($this->slotId, fn($query) =>
                $query->where('payroll_slot_id', $this->slotId))
            ->when($this->filters['employee_id'], fn($query, $value) =>
                $query->where('employee_id', $value))
            ->with(['employee']);
        $paginated = $query->paginate($this->perPage);
        $paginated->getCollection()->transform(function ($record) {
            return [
                'id' => $record->id,
                'employee_id' => $record->employee_id,
                'employee_name' => $record->employee->fname . ' ' . $record->employee->lname,
                'cycle_days' => $record->cycle_days,
                'void_days_count' => $record->void_days_count,
                'lop_days_count' => $record->lop_days_count,
                'lop_details' => $record->lop_details,
            ];
        });
        return $paginated;
    }

    public function editLopDays($id)
    {
        $record = EmployeesSalaryDay::with('employee')->find($id);
        if ($record) {
            $this->selectedRecord = [
                'id' => $record->id,
                'employee_name' => $record->employee->fname . ' ' . $record->employee->lname,
                'cycle_days' => $record->cycle_days,
                'void_days_count' => $record->void_days_count,
                'lop_days_count' => $record->lop_days_count,
                'lop_details' => $record->lop_details,
            ];
            $details = $this->decodeLopDetails($record->lop_details, $record->cycle_days);
            $this->editForm = [
                'void_days_count' => $record->void_days_count,
                'lop_days_count' => $record->lop_days_count,
                'lop_details' => $record->lop_details,
                'void_dates' => $details['void'],
                'lop_dates' => $details['lop'],
            ];
            $this->showEditModal = true;
        }
    }

    private function ensureSalaryDayRowsForSlot(): void
    {
        try {
            if (!$this->slotId) {
                return;
            }

            $slot = PayrollSlot::where('firm_id', Session::get('firm_id'))
                ->where('id', $this->slotId)
                ->first();
            if (!$slot) {
                return;
            }

            $fromDate = Carbon::parse($slot->from_date);
            $toDate = Carbon::parse($slot->to_date);
            $cycleDays = $fromDate->diffInDays($toDate) + 1;

            // Get employees in this slot's execution group who are active
            $employeeIds = EmployeesSalaryExecutionGroup::where('firm_id', Session::get('firm_id'))
                ->where('salary_execution_group_id', $slot->salary_execution_group_id)
                ->whereHas('employee', function ($q) use ($slot) {
                    $q->where('is_inactive', false)
                        ->whereHas('emp_job_profile', function ($q2) use ($slot) {
                            $q2->where(function ($query) use ($slot) {
                                $query->whereNull('doh')
                                    ->orWhereDate('doh', '<=', $slot->to_date);
                            });
                        });
                })
                ->pluck('employee_id')
                ->toArray();

            if (empty($employeeIds)) {
                return;
            }

            // Create missing EmployeesSalaryDay rows for these employees
            foreach ($employeeIds as $employeeId) {
                EmployeesSalaryDay::firstOrCreate(
                    [
                        'firm_id' => Session::get('firm_id'),
                        'payroll_slot_id' => $this->slotId,
                        'employee_id' => $employeeId,
                    ],
                    [
                        'cycle_days' => $cycleDays,
                        'void_days_count' => 0,
                        'lop_days_count' => 0,
                        'lop_details' => null,
                    ]
                );
            }
        } catch (\Throwable $e) {
            // Fail silently; UI will just show empty state if something goes wrong
        }
    }

    private function decodeLopDetails($json, $cycle_days)
    {
        $month = null;
        $void = [];
        $lop = [];
        if ($json) {
            $data = json_decode($json, true);
            $month = $data['month'] ?? null;
            $void = $data['void'] ?? [];
            $lop = $data['lop'] ?? [];
        }
        return [
            'month' => $month,
            'void' => $void,
            'lop' => $lop,
        ];
    }

    private function encodeLopDetails($month, $void, $lop)
    {
        return json_encode([
            'month' => $month,
            'void' => array_values($void),
            'lop' => array_values($lop),
        ]);
    }

    public function updateLopDays()
    {
        // Auto-calculate days count from selected dates
        $this->editForm['void_days_count'] = is_array($this->editForm['void_dates']) ? count($this->editForm['void_dates']) : 0;
        $this->editForm['lop_days_count'] = is_array($this->editForm['lop_dates']) ? count($this->editForm['lop_dates']) : 0;

        // Always generate lop_details JSON from selected dates
        $month = null;
        if (!empty($this->editForm['void_dates'])) {
            $month = substr($this->editForm['void_dates'][0], 0, 7);
        } elseif (!empty($this->editForm['lop_dates'])) {
            $month = substr($this->editForm['lop_dates'][0], 0, 7);
        } else {
            $month = now()->format('Y-m');
        }
        $lop_details_json = $this->encodeLopDetails($month, $this->editForm['void_dates'], $this->editForm['lop_dates']);
        $this->editForm['lop_details'] = $lop_details_json;

        $this->validate([
            'editForm.void_days_count' => 'required|integer|min:0|max:' . $this->selectedRecord['cycle_days'],
            'editForm.lop_days_count' => 'required|integer|min:0|max:' . $this->selectedRecord['cycle_days'],
            'editForm.lop_details' => 'nullable|string',
            'editForm.void_dates' => 'array',
            'editForm.lop_dates' => 'array',
        ]);

        try {
            $record = EmployeesSalaryDay::find($this->selectedRecord['id']);
            if ($record) {
                $record->update([
                    'void_days_count' => $this->editForm['void_days_count'],
                    'lop_days_count' => $this->editForm['lop_days_count'],
                    'lop_details' => $lop_details_json,
                ]);

                Flux::toast(
                    variant: 'success',
                    heading: 'Success',
                    text: 'LOP days updated successfully.',
                );
            }
        } catch (\Exception $e) {
            Flux::toast(
                variant: 'error',
                heading: 'Error',
                text: 'Failed to update LOP days: ' . $e->getMessage(),
            );
        }

        $this->closeEditModal();
    }

    public function closeEditModal()
    {
        $this->showEditModal = false;
        $this->selectedRecord = null;
        $this->editForm = [
            'void_days_count' => 0,
            'lop_days_count' => 0,
            'lop_details' => '',
            'void_dates' => [],
            'lop_dates' => [],
        ];
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Payroll/blades/lop-adjustment-step.blade.php'));
    }
} 