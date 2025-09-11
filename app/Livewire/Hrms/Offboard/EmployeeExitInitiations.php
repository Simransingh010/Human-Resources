<?php

namespace App\Livewire\Hrms\Offboard;

use App\Models\Hrms\Employee;
use App\Models\Hrms\EmployeeExit;
use App\Models\Hrms\EmployeeJobProfile;
use App\Models\Hrms\ExitApprovalAction;
use App\Models\Hrms\ExitApprovalStep;
use App\Models\Hrms\ExitApprovalsStepsTrack;
use App\Models\Hrms\ExitApprovalActionsTrack;
use App\Models\Settings\Department;
use App\Models\Settings\Designation;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;
use Flux;

class EmployeeExitInitiations extends Component
{
    use WithPagination;

    public $perPage = 10;
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $isEditing = false;

    // Field configuration for form and table
    public array $fieldConfig = [
        'employee_id' => ['label' => 'Employee', 'type' => 'select', 'listKey' => 'employees'],
        'exit_type' => ['label' => 'Exit Type', 'type' => 'select', 'listKey' => 'exit_types'],
        'exit_reason' => ['label' => 'Exit Reason', 'type' => 'text'],
        'exit_request_date' => ['label' => 'Exit Request Date', 'type' => 'date'],
        'notice_period_days' => ['label' => 'Notice Period (Days)', 'type' => 'number'],
        'last_working_day' => ['label' => 'Last Working Day', 'type' => 'date'],
        'actual_relieving_date' => ['label' => 'Actual Relieving Date', 'type' => 'date'],
        'remarks' => ['label' => 'Remarks', 'type' => 'textarea'],
    ];

    // Filters for list
    public array $filterFields = [
        'employee_id' => ['label' => 'Employee', 'type' => 'select', 'listKey' => 'employees'],
        'exit_type' => ['label' => 'Exit Type', 'type' => 'select', 'listKey' => 'exit_types'],
        'status' => ['label' => 'Status', 'type' => 'select', 'listKey' => 'status_options'],
    ];

    public array $listsForFields = [];
    public array $filters = [];
    public array $visibleFields = [];
    public array $visibleFilterFields = [];

    public $formData = [
        'id' => null,
        'firm_id' => null,
        'employee_id' => '',
        'exit_type' => '',
        'exit_reason' => '',
        'exit_request_date' => '',
        'notice_period_days' => 0,
        'last_working_day' => '',
        'actual_relieving_date' => '',
        'status' => 'initiated',
        'remarks' => '',
    ];

    // Auto-filled from employee master
    public $employeeInfo = [
        'employee_code' => null,
        'department' => null,
        'designation' => null,
    ];

    public function mount(): void
    {
        $this->initListsForFields();

        $this->visibleFields = ['employee_id', 'exit_type', 'exit_reason', 'exit_request_date', 'notice_period_days', 'last_working_day', 'actual_relieving_date'];
        $this->visibleFilterFields = ['employee_id', 'exit_type', 'status'];

        $this->filters = array_fill_keys(array_keys($this->filterFields), '');

        if (empty($this->formData['exit_request_date'])) {
            $this->formData['exit_request_date'] = date('Y-m-d');
        }
    }

    protected function initListsForFields(): void
    {
        $firmId = Session::get('firm_id');
        $cacheKey = "employee_exit_initiations_lists_{$firmId}";

        $this->listsForFields = Cache::remember($cacheKey, 300, function () use ($firmId) {
            return [
                'employees' => Employee::where('firm_id', $firmId)
                    ->where(function ($q) {
                        $q->whereNull('is_inactive')->orWhere('is_inactive', false);
                    })
                    ->with('emp_job_profile')
                    ->orderBy('fname')
                    ->get()
                    ->mapWithKeys(function (Employee $employee) {
                        $fullName = trim("{$employee->fname} {$employee->mname} {$employee->lname}") ?: "Employee #{$employee->id}";
                        $code = optional($employee->emp_job_profile)->employee_code;
                        $label = $code ? "$fullName ($code)" : $fullName;
                        return [$employee->id => $label];
                    })
                    ->toArray(),
                'exit_types' => EmployeeExit::EXIT_TYPES,
                'status_options' => [
                    'initiated' => 'Initiated',
                    'pending' => 'Pending',
                    'in_progress' => 'In Progress',
                    'completed' => 'Completed',
                    'cancelled' => 'Cancelled',
                ],
            ];
        });
    }

    public function applyFilters(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->filters = array_fill_keys(array_keys($this->filterFields), '');
        $this->resetPage();
    }

    public function toggleColumn(string $field): void
    {
        if (in_array($field, $this->visibleFields)) {
            $this->visibleFields = array_filter($this->visibleFields, fn ($f) => $f !== $field);
        } else {
            $this->visibleFields[] = $field;
        }
    }

    public function toggleFilterColumn(string $field): void
    {
        if (in_array($field, $this->visibleFilterFields)) {
            $this->visibleFilterFields = array_filter($this->visibleFilterFields, fn ($f) => $f !== $field);
        } else {
            $this->visibleFilterFields[] = $field;
        }
    }

    #[Computed]
    public function list()
    {
        $firmId = Session::get('firm_id');

        return EmployeeExit::query()
            ->with(['employee.emp_job_profile.department', 'employee.emp_job_profile.designation'])
            ->where('firm_id', $firmId)
            ->when($this->filters['employee_id'], fn ($q, $v) => $q->where('employee_id', $v))
            ->when($this->filters['exit_type'], fn ($q, $v) => $q->where('exit_type', $v))
            ->when($this->filters['status'], fn ($q, $v) => $q->where('status', $v))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);
    }

    protected function rules(): array
    {
        return [
            'formData.employee_id' => 'required|exists:employees,id',
            'formData.exit_type' => 'required|string|max:255',
            'formData.exit_reason' => 'required|string|max:255',
            'formData.exit_request_date' => 'required|date',
            'formData.notice_period_days' => 'required|integer|min:0',
            'formData.last_working_day' => 'nullable|date',
            'formData.actual_relieving_date' => 'nullable|date',
            'formData.remarks' => 'nullable|string|max:1000',
        ];
    }

    // Auto-fill when employee changes
    public function updatedFormDataEmployeeId($value): void
    {
        $this->populateEmployeeInfo((int) $value);
    }

    protected function populateEmployeeInfo(?int $employeeId): void
    {
        $this->employeeInfo = [
            'employee_code' => null,
            'department' => null,
            'designation' => null,
        ];
        if (!$employeeId) {
            return;
        }
        $employee = Employee::with(['emp_job_profile.department', 'emp_job_profile.designation'])
            ->find($employeeId);
        if (!$employee) {
            return;
        }
        $this->employeeInfo['employee_code'] = optional($employee->emp_job_profile)->employee_code;
        $this->employeeInfo['department'] = optional(optional($employee->emp_job_profile)->department)->title;
        $this->employeeInfo['designation'] = optional(optional($employee->emp_job_profile)->designation)->title;
    }

    public function store(): void
    {
        $validated = $this->validate();
        $data = collect($validated['formData'])
            ->map(fn ($v) => $v === '' ? null : $v)
            ->toArray();
        $data['firm_id'] = Session::get('firm_id');
        $data['initiated_by_user_id'] = auth()->id();
        $data['status'] = $data['status'] ?? 'initiated';

        // Prevent duplicate active exits for the same employee
        $hasActiveExit = EmployeeExit::where('firm_id', $data['firm_id'])
            ->where('employee_id', $data['employee_id'])
            ->whereIn('status', ['initiated', 'pending', 'in_progress'])
            ->exists();

        if ($hasActiveExit) {
            Flux::toast(
                variant: 'error',
                heading: 'Exit already in progress',
                text: 'This employee already has an exit under process. You cannot generate multiple exit processes for the same employee.'
            );
            return;
        }

        DB::transaction(function () use ($data) {
            // Create exit request
            $exit = EmployeeExit::create($data);

            // Determine employee department and designation
            $job = EmployeeJobProfile::with(['department', 'designation'])
                ->where('employee_id', $exit->employee_id)
                ->latest('id')
                ->first();

            $departmentId = optional($job)->department_id;
            $designationId = optional($job)->designation_id;

            // Pull Exit Approval Steps matching department/designation
            $steps = ExitApprovalStep::query()
                ->where('firm_id', $exit->firm_id)
                ->where('is_inactive', false)
                ->when($departmentId, fn ($q) => $q->where('exit_employee_department_id', $departmentId))
                ->when($designationId, fn ($q) => $q->where('exit_employee_designation_id', $designationId))
                ->orderBy('flow_order')
                ->get();

            $firstFlowOrder = optional($steps->first())->flow_order;

            foreach ($steps as $step) {
                $stepTrack = ExitApprovalsStepsTrack::create([
                    'firm_id' => $exit->firm_id,
                    'exit_id' => $exit->id,
                    'employee_id' => $exit->employee_id,
                    'exit_employee_department_id' => $step->exit_employee_department_id,
                    'exit_employee_designation_id' => $step->exit_employee_designation_id,
                    'flow_order' => $step->flow_order,
                    'approval_type' => $step->approval_type,
                    'department_id' => $step->department_id,
                    'status' => ($step->flow_order === $firstFlowOrder) ? 'pending' : 'blocked',
                ]);

                // Actions under this step
                $actions = ExitApprovalAction::where('exit_approval_step_id', $step->id)
                    ->where('is_inactive', false)
                    ->get();

                foreach ($actions as $action) {
                    ExitApprovalActionsTrack::create([
                        'firm_id' => $exit->firm_id,
                        'exit_approvals_steps_track_id' => $stepTrack->id,
                        'employee_id' => $exit->employee_id,
                        'exit_approval_step_id' => $step->id,
                        'clearance_type' => null,
                        'clearance_item' => $action->clearance_item,
                        'clearance_desc' => $action->clearance_desc,
                        'status' => 'pending',
                    ]);
                }
            }
        });

        $this->resetForm();
        $this->modal('mdl-employee-exit-initiation')->close();

        Flux::toast(
            variant: 'success',
            heading: 'Exit initiated.',
            text: 'Exit request created and approval workflow seeded.',
        );
    }

    public function resetForm(): void
    {
        $this->reset(['formData']);
        $this->formData['exit_request_date'] = date('Y-m-d');
        $this->formData['notice_period_days'] = 0;
        $this->formData['status'] = 'initiated';
        $this->employeeInfo = [
            'employee_code' => null,
            'department' => null,
            'designation' => null,
        ];
        $this->isEditing = false;
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Offboard/blades/employee-exit-initiation.blade.php'));
    }
}
