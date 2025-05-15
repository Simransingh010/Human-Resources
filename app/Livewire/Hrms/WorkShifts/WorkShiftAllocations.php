<?php

namespace App\Livewire\Hrms\WorkShifts;

use App\Models\Batch;
use App\Models\BatchItem;
use App\Models\Hrms\Employee;
use App\Models\Settings\Department;
use App\Models\Hrms\WorkShift;
use App\Models\Hrms\EmpWorkShift;
use App\Models\Hrms\WorkShiftDay;
use App\Models\Hrms\EmpAttendance;
use App\Services\BulkOperationService;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Flux;

class WorkShiftAllocations extends Component
{
    use WithPagination;

    public $perPage = 10;
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $showItemsModal = false;
    public $selectedBatchId = null;
    public $isEditing = false;
    public $selectedEmployees = [];
    public $departmentsWithEmployees = [];
    public $filteredDepartmentsWithEmployees = [];
    public $employeeSearch = '';
    public $workShiftFilter = '';

    // Properties for work shift allocation
    public $selectedWorkShift = null;
    public $workShifts = [];
    public $workShiftDays = [];

    // New properties for employment type filtering
    public $employmentTypes = [];
    public $selectedEmploymentType = null;
    public $allDepartmentsWithEmployees = []; // Store the original unfiltered data

    // Field configuration for form and table
    public array $fieldConfig = [
        'title' => ['label' => 'Batch Title', 'type' => 'text'],
        'modulecomponent' => ['label' => 'Module', 'type' => 'select', 'listKey' => 'modules'],
        'action' => ['label' => 'Action', 'type' => 'select', 'listKey' => 'actions'],
        'created_at' => ['label' => 'Created Date', 'type' => 'date'],
        'items_count' => ['label' => 'Items Count', 'type' => 'badge'],
    ];

    // Filter fields configuration
    public array $filterFields = [
        'title' => ['label' => 'Batch Title', 'type' => 'text'],
        'modulecomponent' => ['label' => 'Module', 'type' => 'select', 'listKey' => 'modules'],
        'action' => ['label' => 'Action', 'type' => 'select', 'listKey' => 'actions'],
    ];

    public array $listsForFields = [];
    public array $filters = [];
    public array $visibleFields = [];
    public array $visibleFilterFields = [];

    protected function rules()
    {
        return [
            'selectedWorkShift' => 'required|exists:work_shifts,id',
            'selectedEmployees' => 'required|array|min:1',
            'selectedEmployees.*' => 'exists:employees,id'
        ];
    }

    public function mount()
    {
        $this->initListsForFields();
        $this->loadWorkShifts();
        $this->loadDepartmentsWithEmployees();
        $this->loadEmploymentTypes();

        // Set default visible fields
        $this->visibleFields = ['title', 'modulecomponent', 'action', 'created_at', 'items_count'];
        $this->visibleFilterFields = ['title', 'modulecomponent', 'action'];

        // Initialize filters
        $this->filters = array_fill_keys(array_keys($this->filterFields), '');

        // Initialize filtered departments
        $this->filteredDepartmentsWithEmployees = $this->departmentsWithEmployees;
    }

    public function updatedSelectedWorkShift($value)
    {
        if ($value) {
            $this->workShiftDays = WorkShiftDay::where('work_shift_id', $value)
                ->orderBy('work_date')
                ->get();

            if ($this->workShiftDays->isEmpty()) {
                Flux::toast(
                    variant: 'warning',
                    heading: 'Warning',
                    text: 'No work shift days found for this shift. Please generate shift days first.',
                );
            }
        } else {
            $this->workShiftDays = collect();
        }
    }

    protected function initListsForFields(): void
    {
        $this->listsForFields['modules'] = [
            'work_shift' => 'Work Shift',
            'attendance' => 'Attendance',
            'payroll' => 'Payroll'
        ];

        $this->listsForFields['actions'] = [
            'pending' => 'Pending',
            'approved' => 'Approved',
            'rejected' => 'Rejected'
        ];
    }

    protected function loadWorkShifts()
    {
        $this->workShifts = WorkShift::where('firm_id', session('firm_id'))
            ->where('is_inactive', false)
            ->get();
    }

    protected function loadDepartmentsWithEmployees()
    {
        // Load all data once with necessary relationships
        $departments = Department::with([
            'employees' => function ($query) {
                $query->where('is_inactive', false)
                    ->with(['emp_job_profile.employment_type', 'emp_work_shifts']);
            }
        ])
            ->where('firm_id', session('firm_id'))
            ->where('is_inactive', false)
            ->get();

        // Store the complete data
        $this->allDepartmentsWithEmployees = $departments->map(function ($department) {
            return [
                'id' => $department->id,
                'title' => $department->title,
                'employees' => $department->employees->map(function ($employee) {
                    // Check for active work shift
                    $hasActiveWorkShift = $employee->emp_work_shifts()
                        ->where(function ($query) {
                        $query->whereNull('end_date')
                            ->orWhere('end_date', '>=', now());
                    })
                        ->exists();

                    return [
                        'id' => (int) $employee->id,
                        'fname' => $employee->fname,
                        'lname' => $employee->lname,
                        'email' => $employee->email,
                        'phone' => $employee->phone,
                        'employment_type_id' => $employee->emp_job_profile?->employment_type_id,
                        'employment_type' => $employee->emp_job_profile?->employment_type?->title ?? 'N/A',
                        'has_active_work_shift' => $hasActiveWorkShift
                    ];
                })->toArray()
            ];
        })->toArray();

        $this->filterEmployees();
    }

    protected function filterEmployees()
    {
        // Start with the original unfiltered data
        $departments = collect($this->allDepartmentsWithEmployees);

        // Apply filters while preserving original employee data
        $filteredDepartments = $departments->map(function ($department) {
            $filteredEmployees = collect($department['employees'])->filter(function ($employee) {
                // Employment type filter
                $matchesEmploymentType = !$this->selectedEmploymentType ||
                    $employee['employment_type_id'] == $this->selectedEmploymentType;

                // Search filter - case insensitive
                $searchTerm = strtolower($this->employeeSearch);
                $employeeName = strtolower($employee['fname'] . ' ' . $employee['lname']);
                $employeeEmail = strtolower($employee['email'] ?? '');
                $employeePhone = strtolower($employee['phone'] ?? '');

                $matchesSearch = empty($this->employeeSearch) ||
                    str_contains($employeeName, $searchTerm) ||
                    str_contains($employeeEmail, $searchTerm) ||
                    str_contains($employeePhone, $searchTerm);

                // Work shift filter
                $matchesWorkShift = match ($this->workShiftFilter) {
                    'with_shift' => $employee['has_active_work_shift'],
                    'without_shift' => !$employee['has_active_work_shift'],
                    default => true
                };

                return $matchesEmploymentType && $matchesSearch && $matchesWorkShift;
            });

            return [
                'id' => $department['id'],
                'title' => $department['title'],
                'employees' => $filteredEmployees->values()->all()
            ];
        })->filter(function ($department) {
            return !empty($department['employees']);
        })->values()->all();

        $this->departmentsWithEmployees = $filteredDepartments;
        $this->filteredDepartmentsWithEmployees = $filteredDepartments;
    }

    public function updatedEmployeeSearch()
    {
        $this->filterEmployees();
    }

    public function updatedSelectedEmploymentType()
    {
        $this->filterEmployees();
    }

    public function updatedWorkShiftFilter()
    {
        $this->filterEmployees();
    }

    public function selectAllEmployeesGlobal()
    {
        $allEmployeeIds = collect($this->departmentsWithEmployees)
            ->pluck('employees')
            ->flatten(1)
            ->pluck('id')
            ->map(function ($id) {
                return (string) $id;
            })
            ->toArray();

        $this->selectedEmployees = array_unique($allEmployeeIds);
    }

    public function deselectAllEmployeesGlobal()
    {
        $this->selectedEmployees = [];
    }

    public function selectAllEmployees($departmentId)
    {
        $department = collect($this->departmentsWithEmployees)->firstWhere('id', $departmentId);
        if ($department) {
            $employeeIds = collect($department['employees'])
                ->pluck('id')
                ->map(function ($id) {
                    return (string) $id;
                })
                ->toArray();
            $this->selectedEmployees = array_unique(array_merge($this->selectedEmployees, $employeeIds));
        }
    }

    public function deselectAllEmployees($departmentId)
    {
        $department = collect($this->departmentsWithEmployees)->firstWhere('id', $departmentId);
        if ($department) {
            $employeeIds = collect($department['employees'])
                ->pluck('id')
                ->map(function ($id) {
                    return (string) $id;
                })
                ->toArray();
            $this->selectedEmployees = array_values(array_diff($this->selectedEmployees, $employeeIds));
        }
    }

    public function store()
    {
        try {
            $this->validate();

            if ($this->workShiftDays->isEmpty()) {
                throw new \Exception("No work shift days found. Please generate shift days first.");
            }

            // Start batch operation
            $workShiftTitle = WorkShift::find($this->selectedWorkShift)->shift_title;
            $batchTitle = "Work Shift Assignment - Shift: {$workShiftTitle}";

            DB::beginTransaction();

            $batch = Batch::create([
                'firm_id' => session('firm_id'),
                'user_id' => auth()->id(),
                'modulecomponent' => 'work_shift_assignment',
                'action' => 'bulk_allocation',
                'title' => $batchTitle
            ]);

            $periodStart = $this->workShiftDays->first()->work_date;
            $periodEnd = $this->workShiftDays->last()->work_date;

            $actualSelectedEmployeeIds = array_map('intval', $this->selectedEmployees);

            foreach ($actualSelectedEmployeeIds as $employeeId) {
                // Create work shift assignment
                $assignmentData = [
                    'firm_id' => session('firm_id'),
                    'employee_id' => $employeeId,
                    'work_shift_id' => $this->selectedWorkShift,
                    'start_date' => $periodStart,
                    'end_date' => $periodEnd
                ];

                // Check for overlapping assignments
                $overlapping = EmpWorkShift::where('employee_id', $employeeId)
                    ->where(function ($query) use ($periodStart, $periodEnd) {
                        $query->whereBetween('start_date', [$periodStart, $periodEnd])
                            ->orWhereBetween('end_date', [$periodStart, $periodEnd])
                            ->orWhere(function ($q) use ($periodStart, $periodEnd) {
                                $q->where('start_date', '<=', $periodStart)
                                    ->where('end_date', '>=', $periodEnd);
                            });
                    })
                    ->first();

                if ($overlapping) {
                    // Store original end date for rollback
                    $originalEndDate = $overlapping->end_date;

                    // End the existing assignment one day before the new one starts
                    $overlapping->end_date = $periodStart->copy()->subDay();
                    $overlapping->save();

                    BatchItem::create([
                        'batch_id' => $batch->id,
                        'operation' => 'update',
                        'model_type' => EmpWorkShift::class,
                        'model_id' => $overlapping->id,
                        'original_data' => json_encode(['end_date' => $originalEndDate]),
                        'new_data' => json_encode(['end_date' => $overlapping->end_date])
                    ]);
                }

                // Create new work shift assignment
                $assignment = EmpWorkShift::create($assignmentData);

                BatchItem::create([
                    'batch_id' => $batch->id,
                    'operation' => 'insert',
                    'model_type' => EmpWorkShift::class,
                    'model_id' => $assignment->id,
                    'new_data' => json_encode($assignment->toArray())
                ]);

                // Create attendance records for each day
                foreach ($this->workShiftDays as $day) {
                    $attendanceData = [
                        'firm_id' => session('firm_id'),
                        'employee_id' => $employeeId,
                        'work_shift_day_id' => $day->id,
                        'work_date' => $day->work_date,
                        'attendance_status_main' => $day->day_status_main ?? 'P',
                        'ideal_working_hours' => $this->calculateWorkingHours($day->start_time, $day->end_time),
                        'actual_worked_hours' => 0,
                        'final_day_weightage' => $day->paid_percent ? ($day->paid_percent / 100) : 1
                    ];

                    // $attendance = EmpAttendance::create($attendanceData);

                    // BatchItem::create([
                    //     'batch_id' => $batch->id,
                    //     'operation' => 'insert',
                    //     'model_type' => EmpAttendance::class,
                    //     'model_id' => $attendance->id,
                    //     'new_data' => json_encode($attendance->toArray())
                    // ]);
                }
            }

            DB::commit();

            $this->selectedBatchId = $batch->id;

            Flux::toast(
                heading: 'Success',
                text: 'Work shift assignments and attendance records created successfully.',
            );

            $this->resetForm();
            $this->modal('mdl-batch')->close();

        } catch (\Exception $e) {
            DB::rollBack();
            Flux::toast(
                variant: 'error',
                heading: 'Error',
                text: 'Failed to assign work shift: ' . $e->getMessage(),
            );
        }
    }

    public function rollbackBatch($batchId)
    {
        try {
            DB::transaction(function () use ($batchId) {
                $batch = Batch::with([
                    'items' => function ($query) {
                        $query->orderBy('id', 'desc');
                    }
                ])->findOrFail($batchId);

                foreach ($batch->items as $item) {
                    switch ($item->model_type) {
                        case EmpWorkShift::class:
                        case EmpAttendance::class:
                            $model = $item->model_type::withTrashed()->find($item->model_id);

                            if ($model) {
                                if ($item->operation === 'insert') {
                                    $model->forceDelete();
                                } elseif ($item->operation === 'update' && $item->original_data) {
                                    $originalData = json_decode($item->original_data, true);
                                    if (is_array($originalData)) {
                                        $model->update($originalData);
                                    }
                                }
                            }
                            break;
                    }
                }

                $batch->update(['action' => 'bulk_allocation_rolled_back']);
            });

            Flux::toast(
                heading: 'Success',
                text: 'Work shift assignments and attendance records rolled back successfully.',
            );
        } catch (\Exception $e) {
            Flux::toast(
                variant: 'error',
                heading: 'Error',
                text: 'Failed to rollback: ' . $e->getMessage(),
            );
        }
    }

    protected function calculateWorkingHours($startTime, $endTime)
    {
        if (!$startTime || !$endTime) {
            return 0;
        }
        return Carbon::parse($startTime)->diffInHours(Carbon::parse($endTime));
    }

    public function showBatchItems($batchId)
    {
        try {
            $batch = Batch::with([
                'items' => function ($query) {
                    $query->orderBy('created_at', 'desc');
                }
            ])->findOrFail($batchId);

            $this->selectedBatchId = $batch->id;
            $this->showItemsModal = true;
        } catch (\Exception $e) {
            Flux::toast(
                variant: 'error',
                heading: 'Error',
                text: 'Failed to load batch items: ' . $e->getMessage(),
            );
        }
    }

    public function closeItemsModal()
    {
        $this->showItemsModal = false;
        $this->selectedBatchId = null;
    }

    public function resetForm()
    {
        $this->selectedWorkShift = null;
        $this->selectedEmployees = [];
        $this->employeeSearch = '';
        $this->selectedEmploymentType = null;
        $this->workShiftFilter = '';
        $this->workShiftDays = collect();

        // Reset the filtered departments to show all employees
        $this->loadDepartmentsWithEmployees();
    }

    public function clearEmployeeSearch()
    {
        $this->employeeSearch = '';
        $this->selectedEmploymentType = null;
        $this->workShiftFilter = '';
        $this->filterEmployees();
    }

    protected function loadEmploymentTypes()
    {
        $this->employmentTypes = \App\Models\Settings\EmploymentType::where('firm_id', session('firm_id'))
            ->where('is_inactive', false)
            ->get();
    }

    #[Computed]
    public function list()
    {
        return Batch::query()
            ->where('modulecomponent', 'work_shift_assignment')
            ->when($this->filters['title'] ?? null, fn($query, $value) =>
                $query->where('title', 'like', "%{$value}%"))
            ->withCount('items as items_count')
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/WorkShifts/blades/work-shift-allocations.blade.php'));
    }
}