<?php

namespace App\Livewire\Hrms\Leave;

use App\Models\Hrms\LeaveApprovalRule;
use App\Models\Hrms\LeaveType;
use App\Models\Settings\Department;
use App\Models\Hrms\Employee;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Session;
use Flux;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LeaveApprovalRules extends Component
{
    use WithPagination;

    protected string $paginationEmployeeList = 'employees';

    public $isEditing = false;
    public $statuses = [];
    public $departmentsWithEmployees = [];
    public $filteredDepartmentsWithEmployees = [];
    public $selectedEmployees = [];
    public $employeeSearch = '';
    public $selectedRuleForEmployees = null;
    public $employeePerPage = 10;
    public $employeeFilters = [];
    public $deleteRuleId = null;
    public $readyToLoad = false;
    public $loadedEmployees = [];

    public $employeeFilterFields = [
        'department_id' => ['label' => 'Department', 'type' => 'select', 'listKey' => 'departmentlist'],
        'designation_id' => ['label' => 'Designation', 'type' => 'select', 'listKey' => 'designationlist'],
    ];

    public array $filterFields = [
        'leave_type_id' => ['label' => 'Leave Type', 'type' => 'select', 'listKey' => 'leave_types_list'],
        'approver_id' => ['label' => 'Approver', 'type' => 'select', 'listKey' => 'approvers_list'],
        'is_inactive' => ['label' => 'Status', 'type' => 'select', 'listKey' => 'status_list'],
    ];

    public array $listsForFields = [];
    public array $filters = [];
    public array $visibleFilterFields = [];

    public $formData = [
        'id' => null,
        'firm_id' => null,
        'leave_type_id' => '',
        'approver_id' => '',
        'approval_level' => 1,
        'approval_mode' => '',
        'auto_approve' => false,
        'min_days' => null,
        'max_days' => null,
        'period_start' => null,
        'period_end' => null,
        'is_inactive' => false,
    ];

    protected $rules = [
        'formData.leave_type_id' => 'required|exists:leave_types,id',
        'formData.approver_id' => 'nullable|exists:users,id',
        'formData.approval_level' => 'nullable|integer|min:1',
        'formData.approval_mode' => 'nullable|string',
        'formData.auto_approve' => 'boolean',
        'formData.min_days' => 'nullable|numeric|min:0',
        'formData.max_days' => 'nullable|numeric|min:0',
        'formData.period_start' => 'required|date',
        'formData.period_end' => 'required|date|after:formData.period_start',
        'formData.is_inactive' => 'boolean',
        'selectedEmployees' => 'array',
    ];

    protected $messages = [
        'formData.leave_type_id.required' => 'Please select a leave type',
        'formData.period_start.required' => 'Start date is required',
        'formData.period_end.required' => 'End date is required',
        'formData.period_end.after' => 'End date must be after start date',
    ];

    public function loadData()
    {
        $this->readyToLoad = true;
    }

    public function mount()
    {
        $this->initListsForFields();
        $this->visibleFilterFields = ['leave_type_id', 'approver_id', 'is_inactive'];
        $this->filters = array_fill_keys(array_keys($this->filterFields), '');
        $this->employeeFilters = array_fill_keys(array_keys($this->employeeFilterFields), '');
        $this->formData['period_start'] = now()->format('Y-m-d');
        $this->formData['period_end'] = now()->addYear()->format('Y-m-d');
    }

    protected function initListsForFields(): void
    {
        $firmId = session('firm_id');

        $this->listsForFields['leave_types_list'] = LeaveType::where('firm_id', $firmId)
            ->pluck('leave_title', 'id')->toArray();

        $this->listsForFields['approvers_list'] = User::whereHas('firms', fn($q) => $q->where('firms.id', $firmId))
            ->get(['id', 'name', 'phone'])
            ->mapWithKeys(fn($u) => [$u->id => $u->name . ($u->phone ? " ({$u->phone})" : '')])
            ->toArray();

        $this->listsForFields['approval_modes'] = [
            'sequential' => 'Sequential',
            'parallel' => 'Parallel',
            'any' => 'Any',
            'view_only' => 'View Only'
        ];

        $this->listsForFields['status_list'] = ['0' => 'Active', '1' => 'Inactive'];

        $this->listsForFields['departmentlist'] = Department::where('firm_id', $firmId)
            ->pluck('title', 'id')->toArray();

        $this->listsForFields['designationlist'] = \App\Models\Settings\Designation::where('firm_id', $firmId)
            ->pluck('title', 'id')->toArray();
    }

    #[Computed]
    public function groupedRules()
    {
        if (!$this->readyToLoad) {
            return collect();
        }

        // Single optimized query - load everything needed including employee names
        $rules = LeaveApprovalRule::query()
            ->select([
                'id', 'leave_type_id', 'approver_id', 'approval_level', 'approval_mode',
                'auto_approve', 'min_days', 'max_days', 'period_start', 'period_end', 'is_inactive'
            ])
            ->with([
                'leave_type:id,leave_title',
                'user:id,name',
                'employees:id,fname,lname' // Load employee names for search
            ])
            ->where('firm_id', Session::get('firm_id'))
            ->when($this->filters['leave_type_id'], fn($q, $v) => $q->where('leave_type_id', $v))
            ->when($this->filters['approver_id'], fn($q, $v) => $q->where('approver_id', $v))
            ->when($this->filters['is_inactive'] !== '', fn($q) => $q->where('is_inactive', $this->filters['is_inactive']))
            ->orderBy('leave_type_id')
            ->orderBy('approval_level')
            ->get();

        // Update statuses
        $this->statuses = $rules->pluck('is_inactive', 'id')
            ->mapWithKeys(fn($v, $k) => [$k => !$v])->toArray();

        // Group by leave type
        return $rules->groupBy('leave_type_id')->map(function ($leaveTypeRules) {
            $leaveType = $leaveTypeRules->first()->leave_type;

            // Collect unique approvers for this leave type (for filter dropdown)
            $approvers = $leaveTypeRules->filter(fn($r) => $r->user)->pluck('user.name', 'user.id')->unique()->toArray();

            return [
                'leave_type' => $leaveType,
                'leave_title' => $leaveType->leave_title ?? 'Unknown',
                'approvers' => $approvers, // For filter dropdown
                'rules' => $leaveTypeRules->map(fn($r) => [
                    'id' => $r->id,
                    'approver_id' => $r->approver_id,
                    'approver_name' => $r->user->name ?? null,
                    'approval_level' => $r->approval_level,
                    'approval_mode' => $r->approval_mode,
                    'auto_approve' => $r->auto_approve,
                    'min_days' => $r->min_days,
                    'max_days' => $r->max_days,
                    'period_start' => Carbon::parse($r->period_start)->format('d M Y'),
                    'period_end' => Carbon::parse($r->period_end)->format('d M Y'),
                    'is_inactive' => $r->is_inactive,
                    'employees_count' => $r->employees->count(),
                    'employee_names' => $r->employees->map(fn($e) => $e->fname . ' ' . $e->lname)->implode(', '),
                ])->values()->toArray(),
                'total_rules' => $leaveTypeRules->count(),
                'active_count' => $leaveTypeRules->where('is_inactive', false)->count(),
                'inactive_count' => $leaveTypeRules->where('is_inactive', true)->count(),
            ];
        });
    }

    public function getLeaveTypeColor($leaveTitle)
    {
        return match ($leaveTitle) {
            'Sick Leave' => ['bg' => 'bg-amber-50', 'border' => 'border-amber-200', 'badge' => 'amber', 'icon' => 'heart'],
            'Casual Leave' => ['bg' => 'bg-emerald-50', 'border' => 'border-emerald-200', 'badge' => 'emerald', 'icon' => 'sun'],
            'Annual Leave' => ['bg' => 'bg-indigo-50', 'border' => 'border-indigo-200', 'badge' => 'indigo', 'icon' => 'calendar'],
            'Maternity Leave' => ['bg' => 'bg-rose-50', 'border' => 'border-rose-200', 'badge' => 'rose', 'icon' => 'user'],
            'Paternity Leave' => ['bg' => 'bg-blue-50', 'border' => 'border-blue-200', 'badge' => 'blue', 'icon' => 'user'],
            'Study Leave' => ['bg' => 'bg-violet-50', 'border' => 'border-violet-200', 'badge' => 'violet', 'icon' => 'academic-cap'],
            'Unpaid Leave' => ['bg' => 'bg-slate-50', 'border' => 'border-slate-200', 'badge' => 'zinc', 'icon' => 'banknotes'],
            default => ['bg' => 'bg-gray-50', 'border' => 'border-gray-200', 'badge' => 'gray', 'icon' => 'document-text']
        };
    }

    public function toggleStatus($id)
    {
        $rule = LeaveApprovalRule::find($id);
        if ($rule) {
            $rule->is_inactive = !$rule->is_inactive;
            $rule->save();
            $this->statuses[$id] = !$rule->is_inactive;
        }
    }

    public function applyFilters()
    {
        unset($this->groupedRules);
    }

    public function clearFilters()
    {
        $this->filters = array_fill_keys(array_keys($this->filterFields), '');
        unset($this->groupedRules);
    }

    public function confirmDelete($ruleId)
    {
        $this->deleteRuleId = $ruleId;
        $this->modal('mdl-confirm-delete')->show();
    }

    public function executeDelete()
    {
        if ($this->deleteRuleId) {
            $this->delete($this->deleteRuleId);
            $this->deleteRuleId = null;
            $this->modal('mdl-confirm-delete')->close();
            unset($this->groupedRules);
        }
    }

    public function delete($id)
    {
        try {
            DB::beginTransaction();
            $rule = LeaveApprovalRule::findOrFail($id);
            $rule->employees()->detach();
            $rule->forceDelete();
            DB::commit();

            Flux::toast(variant: 'success', heading: 'Deleted', text: 'Rule deleted successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Flux::toast(variant: 'error', heading: 'Error', text: 'Failed to delete: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        $this->isEditing = true;
        $rule = LeaveApprovalRule::with('employees')->findOrFail($id);
        $this->formData = $rule->toArray();
        $this->formData['period_start'] = Carbon::parse($rule->period_start)->format('Y-m-d');
        $this->formData['period_end'] = Carbon::parse($rule->period_end)->format('Y-m-d');
        $this->selectedEmployees = $rule->employees->pluck('id')->map(fn($id) => (string) $id)->toArray();
        $this->loadDepartmentsWithEmployees();
        $this->modal('mdl-approval-rule')->show();
    }

    public function create()
    {
        $this->resetForm();
        $this->loadDepartmentsWithEmployees();
        $this->modal('mdl-approval-rule')->show();
    }

    protected function loadDepartmentsWithEmployees()
    {
        $departments = Department::with(['employees' => fn($q) => $q->where('is_inactive', false)])
            ->where('firm_id', session('firm_id'))
            ->where('is_inactive', false)
            ->get();

        $this->departmentsWithEmployees = $departments->map(fn($d) => [
            'id' => $d->id,
            'title' => $d->title,
            'employees' => $d->employees->map(fn($e) => [
                'id' => (int) $e->id,
                'fname' => $e->fname,
                'lname' => $e->lname,
            ])->toArray()
        ])->toArray();

        $this->filteredDepartmentsWithEmployees = $this->departmentsWithEmployees;
    }

    public function store()
    {
        $this->validate();

        try {
            DB::beginTransaction();

            $data = collect($this->formData)->map(fn($v) => $v === '' ? null : $v)->toArray();
            $data['firm_id'] = session('firm_id');

            if ($data['auto_approve']) {
                $data['approver_id'] = null;
                $data['approval_level'] = null;
                $data['approval_mode'] = null;
            }

            if ($this->isEditing) {
                $rule = LeaveApprovalRule::findOrFail($this->formData['id']);
                $rule->update($data);
            } else {
                $rule = LeaveApprovalRule::create($data);
            }

            $rule->employees()->detach();
            if (!empty($this->selectedEmployees)) {
                $employeeData = collect($this->selectedEmployees)
                    ->mapWithKeys(fn($id) => [(int) $id => ['firm_id' => session('firm_id')]])
                    ->toArray();
                $rule->employees()->sync($employeeData);
            }

            DB::commit();
            $this->resetForm();
            $this->modal('mdl-approval-rule')->close();
            unset($this->groupedRules);

            Flux::toast(variant: 'success', heading: 'Saved', text: 'Rule saved successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Flux::toast(variant: 'error', heading: 'Error', text: 'Failed to save: ' . $e->getMessage());
        }
    }

    public function resetForm()
    {
        $this->reset(['formData', 'selectedEmployees', 'employeeSearch']);
        $this->formData = [
            'id' => null, 'firm_id' => null, 'leave_type_id' => '', 'approver_id' => '',
            'approval_level' => 1, 'approval_mode' => '', 'auto_approve' => false,
            'min_days' => null, 'max_days' => null,
            'period_start' => now()->format('Y-m-d'),
            'period_end' => now()->addYear()->format('Y-m-d'),
            'is_inactive' => false,
        ];
        $this->isEditing = false;
        $this->filteredDepartmentsWithEmployees = $this->departmentsWithEmployees;
        $this->resetErrorBag();
    }

    public function selectAllEmployeesGlobal()
    {
        $this->selectedEmployees = collect($this->departmentsWithEmployees)
            ->pluck('employees')->flatten(1)->pluck('id')
            ->map(fn($id) => (string) $id)->unique()->toArray();
    }

    public function deselectAllEmployeesGlobal()
    {
        $this->selectedEmployees = [];
    }

    public function selectAllEmployees($deptId)
    {
        $dept = collect($this->departmentsWithEmployees)->firstWhere('id', $deptId);
        if ($dept) {
            $ids = collect($dept['employees'])->pluck('id')->map(fn($id) => (string) $id)->toArray();
            $this->selectedEmployees = array_unique(array_merge($this->selectedEmployees, $ids));
        }
    }

    public function deselectAllEmployees($deptId)
    {
        $dept = collect($this->departmentsWithEmployees)->firstWhere('id', $deptId);
        if ($dept) {
            $ids = collect($dept['employees'])->pluck('id')->map(fn($id) => (string) $id)->toArray();
            $this->selectedEmployees = array_values(array_diff($this->selectedEmployees, $ids));
        }
    }

    public function updatedEmployeeSearch()
    {
        if (empty($this->employeeSearch)) {
            $this->filteredDepartmentsWithEmployees = $this->departmentsWithEmployees;
            return;
        }

        $search = strtolower($this->employeeSearch);
        $this->filteredDepartmentsWithEmployees = collect($this->departmentsWithEmployees)
            ->map(function ($dept) use ($search) {
                $filtered = collect($dept['employees'])
                    ->filter(fn($e) => str_contains(strtolower($e['fname'] . ' ' . $e['lname']), $search))
                    ->values()->all();
                return ['id' => $dept['id'], 'title' => $dept['title'], 'employees' => $filtered];
            })
            ->filter(fn($d) => !empty($d['employees']))
            ->values()->all();
    }

    public function updatedFormDataAutoApprove($value)
    {
        if ($value) {
            $this->formData['approver_id'] = null;
            $this->formData['approval_level'] = null;
            $this->formData['approval_mode'] = null;
        }
    }

    public function showEmployeeList($ruleId)
    {
        $rule = LeaveApprovalRule::findOrFail($ruleId);
        $this->loadedEmployees = $rule->employees()
            ->with(['emp_job_profile.department', 'emp_job_profile.designation'])
            ->get();
        $this->selectedRuleForEmployees = $rule;
        $this->resetPage('employees');
        $this->modal('mdl-employee-list')->show();
    }

    public function getEmployeeListProperty()
    {
        if (!$this->selectedRuleForEmployees) return collect();

        $filtered = collect($this->loadedEmployees);

        if ($this->employeeSearch) {
            $search = strtolower($this->employeeSearch);
            $filtered = $filtered->filter(fn($e) =>
                str_contains(strtolower($e->fname . ' ' . $e->lname), $search) ||
                str_contains(strtolower($e->email ?? ''), $search)
            );
        }

        if (!empty($this->employeeFilters['department_id'])) {
            $filtered = $filtered->filter(fn($e) =>
                $e->emp_job_profile && $e->emp_job_profile->department_id == $this->employeeFilters['department_id']
            );
        }

        $page = $this->getPage($this->paginationEmployeeList);
        $items = $filtered->forPage($page, $this->employeePerPage);

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $items, $filtered->count(), $this->employeePerPage, $page,
            ['path' => request()->url(), 'pageName' => $this->paginationEmployeeList]
        );
    }

    public function clearEmployeeFilters()
    {
        $this->employeeFilters = array_fill_keys(array_keys($this->employeeFilterFields), '');
        $this->employeeSearch = '';
        $this->resetPage($this->paginationEmployeeList);
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Leave/blades/leave-approval-rules.blade.php'));
    }
}
