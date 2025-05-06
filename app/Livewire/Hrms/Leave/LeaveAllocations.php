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
    public $selectedTemplate = null;
    public $templateSetups = [];

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

    protected $rules = [
        'formData.template_id' => 'required|exists:leaves_quota_templates,id',
        'selectedEmployees' => 'required|array|min:1',
        'selectedEmployees.*' => 'exists:employees,id',
    ];

    public function mount()
    {
        $this->initListsForFields();
        $this->formData['firm_id'] = session('firm_id');
        $this->loadLeaveQuotaTemplates();
        $this->loadDepartmentsWithEmployees();

        // Set default visible fields
        $this->visibleFields = ['title', 'modulecomponent', 'action', 'created_at', 'items_count'];
        $this->visibleFilterFields = ['title', 'modulecomponent', 'action'];

        // Initialize filters
        $this->filters = array_fill_keys(array_keys($this->filterFields), '');
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
            ->with('leaves_quota_template_setups.leave_type')
            ->get();
    }

    public function updatedFormDataTemplateId($value)
    {
        if ($value) {
            $this->selectedTemplate = $this->leaveQuotaTemplates->find($value);
            $this->templateSetups = $this->selectedTemplate->leaves_quota_template_setups;
        } else {
            $this->selectedTemplate = null;
            $this->templateSetups = [];
        }
    }

    protected function loadDepartmentsWithEmployees()
    {
        $departments = Department::with([
            'employees' => function ($query) {
                $query->where('is_inactive', false);
            }
        ])
            ->where('firm_id', session('firm_id'))
            ->where('is_inactive', false)
            ->get();

        $this->departmentsWithEmployees = $departments->map(function ($department) {
            return [
                'id' => $department->id,
                'title' => $department->title,
                'employees' => $department->employees->map(function ($employee) {
                    return [
                        'id' => $employee->id,
                        'fname' => $employee->fname,
                        'lname' => $employee->lname,
                        'email' => $employee->email,
                        'phone' => $employee->phone,
                    ];
                })->toArray()
            ];
        })->toArray();
    }

    public function selectAllEmployeesGlobal()
    {
        $allEmployeeIds = collect($this->departmentsWithEmployees)
            ->pluck('employees')
            ->flatten(1)
            ->pluck('id')
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
            $employeeIds = collect($department['employees'])->pluck('id')->toArray();
            $this->selectedEmployees = array_unique(array_merge($this->selectedEmployees, $employeeIds));
        }
    }

    public function deselectAllEmployees($departmentId)
    {
        $department = collect($this->departmentsWithEmployees)->firstWhere('id', $departmentId);
        if ($department) {
            $employeeIds = collect($department['employees'])->pluck('id')->toArray();
            $this->selectedEmployees = array_values(array_diff($this->selectedEmployees, $employeeIds));
        }
    }

    public function store()
    {
        $this->validate();

        try {
            DB::transaction(function () {
                $periodStart = Carbon::now()->startOfDay();
                $periodEnd = Carbon::now()->addYear()->endOfDay();

                // Check for existing active leave balances
                $existingBalances = EmpLeaveBalance::whereIn('employee_id', $this->selectedEmployees)
                    ->where('period_end', '>=', now())
                    ->whereHas('leave_type', function ($query) {
                        $query->whereIn('id', $this->templateSetups->pluck('leave_type_id'));
                    })
                    ->get();

                if ($existingBalances->isNotEmpty()) {
                    $employeeIds = $existingBalances->pluck('employee_id')->unique();
                    $employees = Employee::whereIn('id', $employeeIds)->get();
                    $employeeNames = $employees->pluck('fname')->implode(', ');

                    throw new \Exception("Cannot assign leave quota. Active leave balances already exist for employees: " . $employeeNames);
                }

                // Start the batch operation
                $batch = BulkOperationService::start(
                    'leave_quota_assignment',
                    'bulk_allocation',
                    "Leave Quota Assignment - Template: {$this->selectedTemplate->name}"
                );

                // Process each selected employee
                foreach ($this->selectedEmployees as $employeeId) {
                    foreach ($this->templateSetups as $setup) {
                        // Create leave balance record
                        $balanceData = [
                            'firm_id' => session('firm_id'),
                            'employee_id' => $employeeId,
                            'leave_type_id' => $setup->leave_type_id,
                            'period_start' => $periodStart->format('Y-m-d H:i:s'),
                            'period_end' => $periodEnd->format('Y-m-d H:i:s'),
                            'allocated_days' => $setup->days_assigned,
                            'consumed_days' => 0,
                            'carry_forwarded_days' => 0,
                            'lapsed_days' => 0,
                            'balance' => $setup->days_assigned
                        ];

                        $balance = EmpLeaveBalance::create($balanceData);

                        // Create batch item for balance
                        BatchItem::create([
                            'batch_id' => $batch->id,
                            'operation' => 'insert',
                            'model_type' => EmpLeaveBalance::class,
                            'model_id' => $balance->id,
                            'new_data' => json_encode($balanceData)
                        ]);

                        // Create transaction record
                        $transactionData = [
                            'firm_id' => session('firm_id'),
                            'leave_balance_id' => $balance->id,
                            'transaction_type' => 'ALLOCATION',
                            'transaction_date' => now()->format('Y-m-d H:i:s'),
                            'amount' => $setup->days_assigned,
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
                }

                $this->selectedBatchId = $batch->id;
            });

            Flux::toast(
                heading: 'Success',
                text: 'Leave quota assignments saved successfully.',
            );

            $this->resetForm();
            $this->modal('mdl-batch')->close();

        } catch (\Exception $e) {
            Flux::toast(
                variant: 'error',
                heading: 'Error',
                text: 'Failed to assign leave quota: ' . $e->getMessage(),
            );
        }
    }

    public function rollbackBatch($batchId)
    {
        try {
            DB::transaction(function () use ($batchId) {
                $batch = Batch::with('items')->findOrFail($batchId);

                // First delete all transactions
                $transactionItems = $batch->items()
                    ->where('model_type', EmpLeaveTransaction::class)
                    ->get();

                foreach ($transactionItems as $item) {
                    if ($transaction = EmpLeaveTransaction::find($item->model_id)) {
                        $transaction->delete();
                    }
                }

                // Then delete all balances
                $balanceItems = $batch->items()
                    ->where('model_type', EmpLeaveBalance::class)
                    ->get();

                foreach ($balanceItems as $item) {
                    if ($balance = EmpLeaveBalance::find($item->model_id)) {
                        $balance->delete();
                    }
                }

                // Update batch status
                $batch->update(['action' => 'rolled_back']);
            });

            Flux::toast(
                heading: 'Success',
                text: 'Leave quota assignments rolled back successfully.',
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
}