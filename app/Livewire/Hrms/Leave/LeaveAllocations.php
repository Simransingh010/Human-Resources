<?php

namespace App\Livewire\Hrms\Leave;

use App\Models\Batch;
use App\Models\BatchItem;
use App\Models\Hrms\Employee;
use App\Models\Settings\Department;
use App\Models\Hrms\LeavesQuotaTemplate;
use App\Models\Hrms\EmpLeaveBalance;
use App\Models\Hrms\EmpLeaveTransaction;
use App\Services\BulkOperationService;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Flux;
use App\Models\Hrms\LeaveType;

class LeaveAllocations extends Component
{
    use WithPagination;

    public $perPage = 10;
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $showItemsModal = false;
    public $selectedBatchId = null;
    public $isEditing = false;
    public $selectedEmployees = [];
    public $leaveQuotaTemplates = [];
    public $departmentsWithEmployees = [];
    public $filteredDepartmentsWithEmployees = [];
    public $selectedTemplate = null;
    public $templateSetups = [];
    public $employeeSearch = '';
    public $leaveBalanceFilter = ''; // New property for leave balance filter

    // Constants for leave balance filter options
    const LEAVE_BALANCE_FILTER_ALL = '';
    const LEAVE_BALANCE_FILTER_WITH = 'with_balance';
    const LEAVE_BALANCE_FILTER_WITHOUT = 'without_balance';

    // New property for leave balance filter options
    public array $leaveBalanceFilterOptions = [
        self::LEAVE_BALANCE_FILTER_ALL => 'All Employees',
        self::LEAVE_BALANCE_FILTER_WITH => 'With Leave Balance',
        self::LEAVE_BALANCE_FILTER_WITHOUT => 'Without Leave Balance'
    ];

    // New properties for employment type filtering
    public $employmentTypes = [];
    public $selectedEmploymentType = null;
    public $allDepartmentsWithEmployees = []; // Store the original unfiltered data

    // New properties for allocation
    public $allocationType = '';
    public $leaveTypes = [];
    public $selectedLeaveType = null;
    public $allocatedDays = null;
    public $periodStart;
    public $periodEnd;

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

    public $formData = [
        'id' => null,
        'firm_id' => null,
        'template_id' => null,
    ];

    protected function rules()
    {
        $baseRules = [
            'allocationType' => 'required|in:template,direct',
            'selectedEmployees' => 'required|array|min:1',
            'selectedEmployees.*' => 'exists:employees,id',
            'periodStart' => 'required|date',
        ];

        if ($this->allocationType === 'template') {
            return array_merge($baseRules, [
                'formData.template_id' => 'required|exists:leaves_quota_templates,id',
                'periodEnd' => 'nullable|date'
            ]);
        } else {
            return array_merge($baseRules, [
                'selectedLeaveType' => 'required|exists:leave_types,id',
                'allocatedDays' => 'required|numeric|min:0',
                'periodEnd' => 'required|date|after:periodStart'
            ]);
        }
    }

    public function mount()
    {
        $this->initListsForFields();
        $this->formData['firm_id'] = session('firm_id');
        $this->loadLeaveQuotaTemplates();
        $this->loadLeaveTypes();
        $this->loadDepartmentsWithEmployees();
        $this->loadEmploymentTypes();

        // Set default start date only
        $this->periodStart = now()->format('Y-m-d');

        // Set default visible fields
        $this->visibleFields = ['title', 'modulecomponent', 'action', 'created_at', 'items_count'];
        $this->visibleFilterFields = ['title', 'modulecomponent', 'action'];

        // Initialize filters
        $this->filters = array_fill_keys(array_keys($this->filterFields), '');

        // Initialize filtered departments
        $this->filteredDepartmentsWithEmployees = $this->departmentsWithEmployees;
    }

    public function updatedAllocationType()
    {
        // Reset related fields when allocation type changes
        $this->formData['template_id'] = null;
        $this->selectedTemplate = null;
        $this->templateSetups = [];
        $this->selectedLeaveType = null;
        $this->allocatedDays = null;
        $this->periodEnd = null;
    }

    public function updatedEmployeeSearch()
    {
        $this->filterEmployees();
    }

    protected function initListsForFields(): void
    {
        $this->listsForFields['modules'] = [
            'leave' => 'Leave',
            'attendance' => 'Attendance',
            'payroll' => 'Payroll'
        ];

        $this->listsForFields['actions'] = [
            'pending' => 'Pending',
            'approved' => 'Approved',
            'rejected' => 'Rejected'
        ];
    }

    protected function loadLeaveQuotaTemplates()
    {
        $this->leaveQuotaTemplates = LeavesQuotaTemplate::where('firm_id', session('firm_id'))
            ->where('is_inactive', false)
            ->with(['leaves_quota_template_setups.leave_type'])
            ->get();
    }

    public function updatedFormDataTemplateId($value)
    {
        if ($value) {
            $this->selectedTemplate = $this->leaveQuotaTemplates->find($value);
            if (!$this->selectedTemplate) {
                Flux::toast(
                    variant: 'error',
                    heading: 'Error',
                    text: 'Selected template not found.',
                );
                return;
            }

            $this->templateSetups = $this->selectedTemplate->leaves_quota_template_setups;
            if ($this->templateSetups->isEmpty()) {
                Flux::toast(
                    variant: 'warning',
                    heading: 'Warning',
                    text: 'Selected template has no leave types configured.',
                );
                return;
            }

            // Calculate period end based on template's period settings
            if ($this->periodStart && $this->selectedTemplate) {
                $startDate = Carbon::parse($this->periodStart);
                $endDate = match ($this->selectedTemplate->alloc_period_unit) {
                    'day' => $startDate->copy()->addDays($this->selectedTemplate->alloc_period_value),
                    'week' => $startDate->copy()->addWeeks($this->selectedTemplate->alloc_period_value),
                    'month' => $startDate->copy()->addMonths($this->selectedTemplate->alloc_period_value),
                    'year' => $startDate->copy()->addYears($this->selectedTemplate->alloc_period_value),
                    default => $startDate->copy()->addYear()
                };
                $this->periodEnd = $endDate->format('Y-m-d');
            }
        } else {
            $this->selectedTemplate = null;
            $this->templateSetups = collect();
            $this->periodEnd = null;
        }
    }

    protected function loadDepartmentsWithEmployees()
    {
        // Load all data once with necessary relationships
        $departments = Department::with([
            'employees' => function ($query) {
                $query->where('is_inactive', false)
                    ->with(['emp_job_profile.employment_type']);
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
                    // Check for leave balances directly using the EmpLeaveBalance model
                    $hasLeaveBalance = EmpLeaveBalance::where('employee_id', $employee->id)
                        ->where('firm_id', session('firm_id'))
                        ->whereNull('deleted_at')
                        ->exists();

                    return [
                        'id' => (int) $employee->id,
                        'fname' => $employee->fname,
                        'lname' => $employee->lname,
                        'email' => $employee->email,
                        'phone' => $employee->phone,
                        'employment_type_id' => $employee->emp_job_profile?->employment_type_id,
                        'employment_type' => $employee->emp_job_profile?->employment_type?->title ?? 'N/A',
                        'has_leave_balance' => $hasLeaveBalance
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

                // Leave balance filter
                $matchesLeaveBalance = match ($this->leaveBalanceFilter) {
                    self::LEAVE_BALANCE_FILTER_WITH => $employee['has_leave_balance'],
                    self::LEAVE_BALANCE_FILTER_WITHOUT => !$employee['has_leave_balance'],
                    default => true
                };

                return $matchesEmploymentType && $matchesSearch && $matchesLeaveBalance;
            });

            // Preserve the original employee data structure
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

    public function updatedSelectedEmploymentType()
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
                return (string) $id; // Convert to string for consistency with wire:model
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
                    return (string) $id; // Convert to string for consistency with wire:model
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
                    return (string) $id; // Convert to string for consistency
                })
                ->toArray();
            $this->selectedEmployees = array_values(array_diff($this->selectedEmployees, $employeeIds));
        }
    }

    public function store()
    {
        try {
            $this->validate();

            if ($this->allocationType === 'template' && empty($this->templateSetups)) {
                throw new \Exception("Selected template has no leave types configured.");
            }

            // Get the actual selected employee IDs and ensure they're integers
            $actualSelectedEmployeeIds = array_map('intval', $this->selectedEmployees);

            // Get employee details for the actually selected IDs
            $selectedEmployeeDetails = Employee::with(['emp_job_profile.employment_type'])
                ->whereIn('id', $actualSelectedEmployeeIds)
                ->get()
                ->map(function ($employee) {
                    return [
                        'id' => $employee->id,
                        'name' => $employee->fname . ' ' . $employee->lname,
                        'employment_type' => $employee->emp_job_profile?->employment_type?->title ?? 'N/A',
                        'email' => $employee->email,
                        'allocation_type' => $this->allocationType,
                        'period' => [
                            'start' => $this->periodStart,
                            'end' => $this->periodEnd
                        ],
                        'template' => $this->allocationType === 'template' ?
                            $this->selectedTemplate?->name : null,
                        'leave_type' => $this->allocationType === 'direct' ?
                            LeaveType::find($this->selectedLeaveType)?->leave_title : null,
                        'days' => $this->allocationType === 'direct' ?
                            $this->allocatedDays : null
                    ];
                })
                ->toArray();

            DB::beginTransaction();
            try {
                $periodStart = Carbon::parse($this->periodStart)->startOfDay();
                $periodEnd = $this->allocationType === 'template'
                    ? Carbon::parse($this->periodEnd)->endOfDay()
                    : Carbon::parse($this->periodEnd)->endOfDay();

                // Get leave type IDs
                $leaveTypeIds = $this->allocationType === 'template'
                    ? $this->templateSetups->pluck('leave_type_id')
                    : [$this->selectedLeaveType];

                if (empty($leaveTypeIds)) {
                    throw new \Exception("No leave types selected for allocation.");
                }

                // Start the batch operation
                $batchTitle = $this->allocationType === 'template'
                    ? "Leave Quota Assignment - Template: {$this->selectedTemplate->name}"
                    : "Direct Leave Assignment - Type: " . LeaveType::find($this->selectedLeaveType)->leave_title;

                $batch = BulkOperationService::start(
                    'leave_quota_assignment',
                    'bulk_allocation',
                    $batchTitle
                );

                // Process each selected employee - use converted IDs
                foreach ($actualSelectedEmployeeIds as $employeeId) {
                    if ($this->allocationType === 'template') {
                        if (!$this->templateSetups) {
                            throw new \Exception("Template setups not loaded properly.");
                        }

                        foreach ($this->templateSetups as $setup) {
                            if (!$setup->days_assigned) {
                                throw new \Exception("Invalid days assigned in template for leave type: " . $setup->leave_type->leave_title);
                            }
                            $this->createLeaveBalance($batch, $employeeId, $setup->leave_type_id, $setup->days_assigned, $periodStart, $periodEnd);
                        }
                    } else {
                        $this->createLeaveBalance($batch, $employeeId, $this->selectedLeaveType, $this->allocatedDays, $periodStart, $periodEnd);
                    }
                }

                DB::commit();
                $this->selectedBatchId = $batch->id;

                Flux::toast(
                    heading: 'Success',
                    text: 'Leave assignments saved successfully.',
                );

                $this->resetForm();
                $this->modal('mdl-batch')->close();

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            Flux::toast(
                variant: 'error',
                heading: 'Error',
                text: 'Failed to assign leave: ' . $e->getMessage(),
            );
        }
    }
    protected function createLeaveBalance($batch, $employeeId, $leaveTypeId, $days, $periodStart, $periodEnd)
    {
        DB::beginTransaction();

        try {
            $firmId = session('firm_id');

            $balanceData = [
                'firm_id' => $firmId,
                'employee_id' => $employeeId,
                'leave_type_id' => $leaveTypeId,
                'period_start' => $periodStart,
                'period_end' => $periodEnd
            ];

            // Lock existing row including trashed
            $existingBalance = EmpLeaveBalance::withTrashed()
                ->where($balanceData)
                ->lockForUpdate()
                ->first();

            if ($existingBalance) {
                if ($existingBalance->trashed()) {
                    $existingBalance->restore();
                }

                $oldData = [
                    'allocated_days' => $existingBalance->allocated_days,
                    'balance' => $existingBalance->balance
                ];

                $newAllocatedDays = $existingBalance->allocated_days + $days;
                $newBalance = $existingBalance->balance + $days;

                $existingBalance->update([
                    'allocated_days' => $newAllocatedDays,
                    'consumed_days' => 0,
                    'carry_forwarded_days' => 0,
                    'lapsed_days' => 0,
                    'balance' => $newBalance,
                    'updated_at' => now()
                ]);

                BatchItem::create([
                    'batch_id' => $batch->id,
                    'operation' => 'update',
                    'model_type' => EmpLeaveBalance::class,
                    'model_id' => $existingBalance->id,
                    'old_data' => json_encode($oldData),
                    'new_data' => json_encode([
                        'allocated_days' => $newAllocatedDays,
                        'balance' => $newBalance
                    ])
                ]);

                Flux::toast(
                    variant: 'info',
                    heading: 'Balance Updated',
                    text: "Added {$days} days to existing leave balance for employee ID {$employeeId}. New balance: {$newBalance} days.",
                );

                $this->createLeaveTransaction($batch, $existingBalance, $days);

                DB::commit();
                return;
            }

            // Create new record if not exists
            $balance = EmpLeaveBalance::create(array_merge($balanceData, [
                'allocated_days' => $days,
                'consumed_days' => 0,
                'carry_forwarded_days' => 0,
                'lapsed_days' => 0,
                'balance' => $days,
                'created_at' => now(),
                'updated_at' => now()
            ]));

            BatchItem::create([
                'batch_id' => $batch->id,
                'operation' => 'insert',
                'model_type' => EmpLeaveBalance::class,
                'model_id' => $balance->id,
                'new_data' => json_encode($balance->toArray())
            ]);

            $this->createLeaveTransaction($batch, $balance, $days);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception(
                "Error processing leave balance for employee ID {$employeeId}: " . $e->getMessage()
            );
        }
    }

    // protected function createLeaveBalance($batch, $employeeId, $leaveTypeId, $days, $periodStart, $periodEnd)
    // {
    //     try {
    //         $balanceData = [
    //             'firm_id' => session('firm_id'),
    //             'employee_id' => $employeeId,
    //             'leave_type_id' => $leaveTypeId,
    //             'period_start' => $periodStart,
    //             'period_end' => $periodEnd
    //         ];

    //         $existingBalance = EmpLeaveBalance::withTrashed()
    //             ->where($balanceData)
    //             ->first();

    //         if ($existingBalance) {
    //             if ($existingBalance->trashed()) {
    //                 $existingBalance->restore();
    //             }

    //             $oldData = [
    //                 'allocated_days' => $existingBalance->allocated_days,
    //                 'balance' => $existingBalance->balance
    //             ];

    //             $newAllocatedDays = $existingBalance->allocated_days + $days;
    //             $newBalance = $existingBalance->balance + $days;

    //             $existingBalance->update([
    //                 'allocated_days' => $newAllocatedDays,
    //                 'consumed_days' => 0,
    //                 'carry_forwarded_days' => 0,
    //                 'lapsed_days' => 0,
    //                 'balance' => $newBalance,
    //                 'updated_at' => now()
    //             ]);

    //             BatchItem::create([
    //                 'batch_id' => $batch->id,
    //                 'operation' => 'update',
    //                 'model_type' => EmpLeaveBalance::class,
    //                 'model_id' => $existingBalance->id,
    //                 'old_data' => json_encode($oldData),
    //                 'new_data' => json_encode([
    //                     'allocated_days' => $newAllocatedDays,
    //                     'balance' => $newBalance
    //                 ])
    //             ]);

    //             $balance = $existingBalance;

    //             Flux::toast(
    //                 variant: 'info',
    //                 heading: 'Balance Updated',
    //                 text: "Added {$days} days to existing leave balance for employee ID {$employeeId}. New balance: {$newBalance} days.",
    //             );

    //             $this->createLeaveTransaction($batch, $balance, $days);
    //             return;
    //         }

    //         // If no record exists, create new
    //         $balance = EmpLeaveBalance::create(array_merge($balanceData, [
    //             'allocated_days' => $days,
    //             'consumed_days' => 0,
    //             'carry_forwarded_days' => 0,
    //             'lapsed_days' => 0,
    //             'balance' => $days,
    //             'created_at' => now(),
    //             'updated_at' => now()
    //         ]));

    //         BatchItem::create([
    //             'batch_id' => $batch->id,
    //             'operation' => 'insert',
    //             'model_type' => EmpLeaveBalance::class,
    //             'model_id' => $balance->id,
    //             'new_data' => json_encode($balance->toArray())
    //         ]);

    //         $this->createLeaveTransaction($batch, $balance, $days);

    //     } catch (\Exception $e) {
    //         throw new \Exception(
    //             "Error processing leave balance for employee ID {$employeeId}: " . $e->getMessage()
    //         );
    //     }
    // }

    /**
     * Create a leave transaction record
     */
    protected function createLeaveTransaction($batch, $balance, $days)
    {
        $transactionData = [
            'firm_id' => session('firm_id'),
            'leave_balance_id' => $balance->id,
            'transaction_type' => 'alc',
            'transaction_date' => now(),
            'amount' => $days,
            'created_by' => auth()->id(),
            'reference_id' => $batch->id
        ];

        $transaction = EmpLeaveTransaction::create($transactionData);

        // Create batch item for transaction
        BatchItem::create([
            'batch_id' => $batch->id,
            'operation' => 'insert',
            'model_type' => EmpLeaveTransaction::class,
            'model_id' => $transaction->id,
            'new_data' => json_encode($transactionData)
        ]);
    }

    public function rollbackBatch($batchId)
    {
        try {
            DB::transaction(function () use ($batchId) {
                $batch = Batch::with('items')->findOrFail($batchId);

                // First force delete all transactions
                $transactionItems = $batch->items()
                    ->where('model_type', EmpLeaveTransaction::class)
                    ->get();

                foreach ($transactionItems as $item) {
                    if ($transaction = EmpLeaveTransaction::withTrashed()->find($item->model_id)) {
                        $transaction->forceDelete();
                    }
                }

                // Then force delete all balances
                $balanceItems = $batch->items()
                    ->where('model_type', EmpLeaveBalance::class)
                    ->get();

                foreach ($balanceItems as $item) {
                    if ($balance = EmpLeaveBalance::withTrashed()->find($item->model_id)) {
                        $balance->forceDelete();
                    }
                }

                // Force delete the batch itself
                $batch->forceDelete();
            });

            Flux::toast(
                heading: 'Success',
                text: 'Leave quota assignments rolled back and permanently deleted.',
            );
        } catch (\Exception $e) {
            Flux::toast(
                variant: 'error',
                heading: 'Error',
                text: 'Failed to rollback: ' . $e->getMessage(),
            );
        }
    }

    public function edit($id)
    {
        $batch = Batch::findOrFail($id);
        $this->formData = $batch->toArray();
        $this->isEditing = true;
        $this->modal('mdl-batch')->show();
    }

    public function resetForm()
    {
        $this->formData = [
            'id' => null,
            'firm_id' => session('firm_id'),
            'template_id' => null,
        ];
        $this->selectedEmployees = [];
        $this->selectedTemplate = null;
        $this->templateSetups = [];
        $this->isEditing = false;
        $this->allocationType = '';
        $this->selectedLeaveType = null;
        $this->allocatedDays = null;
        $this->periodStart = now()->format('Y-m-d');
        $this->periodEnd = null;
        $this->employeeSearch = '';
        $this->selectedEmploymentType = null;

        // Reset the filtered departments to show all employees
        $this->loadDepartmentsWithEmployees();
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
        return Batch::query()
            ->where('modulecomponent', 'leave_quota_assignment')
            ->when($this->filters['title'] ?? null, fn($query, $value) =>
                $query->where('title', 'like', "%{$value}%"))
            ->withCount('items as items_count')
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);
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

    public function delete($id)
    {
        $batch = Batch::findOrFail($id);

        if ($batch->items()->count() > 0) {
            $batch->items()->delete();
        }

        $batch->delete();

        Flux::toast(
            variant: 'success',
            heading: 'Record Deleted.',
            text: 'Batch has been deleted successfully',
        );
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Leave/blades/leave-allocations.blade.php'));
    }

    protected function loadLeaveTypes()
    {
        $this->leaveTypes = LeaveType::where('firm_id', session('firm_id'))
            ->where('is_inactive', false)
            ->get();
    }

    protected function loadEmploymentTypes()
    {
        $this->employmentTypes = \App\Models\Settings\EmploymentType::where('firm_id', session('firm_id'))
            ->where('is_inactive', false)
            ->get();
    }

    public function updatedLeaveBalanceFilter()
    {
        $this->filterEmployees();
    }

    public function clearEmployeeSearch()
    {
        $this->employeeSearch = '';
        $this->selectedEmploymentType = null;
        $this->leaveBalanceFilter = self::LEAVE_BALANCE_FILTER_ALL;
        $this->filterEmployees();
    }
}