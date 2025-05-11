<?php

namespace App\Livewire\Hrms\Leave\LeaveApprovalRules;

use App\Models\Hrms\DepartmentLeaveApprovalRule;
use App\Models\Hrms\LeaveApprovalRule;
use App\Models\Settings\Department;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Session;
use Flux;

class DepartmentLeaveApprovalRules extends Component
{
    use WithPagination;

    public $perPage = 10;
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $isEditing = false;
    public $statuses = [];

    // Field configuration for form and table
    public array $fieldConfig = [
        'rule_id' => ['label' => 'Approval Rule', 'type' => 'select', 'listKey' => 'rules_list'],
        'department_id' => ['label' => 'Department', 'type' => 'select', 'listKey' => 'departments_list'],
        'is_inactive' => ['label' => 'Status', 'type' => 'switch'],
    ];

    // Filter fields configuration
    public array $filterFields = [
        'rule_id' => ['label' => 'Approval Rule', 'type' => 'select', 'listKey' => 'rules_list'],
        'department_id' => ['label' => 'Department', 'type' => 'select', 'listKey' => 'departments_list'],
        'is_inactive' => ['label' => 'Status', 'type' => 'select', 'listKey' => 'status_list'],
    ];

    public array $listsForFields = [];
    public array $filters = [];
    public array $visibleFields = [];
    public array $visibleFilterFields = [];

    public $formData = [
        'id' => null,
        'firm_id' => null,
        'rule_id' => '',
        'department_id' => '',
        'is_inactive' => false,
    ];

    protected $rules = [
        'formData.rule_id' => 'required|exists:leave_approval_rules,id',
        'formData.department_id' => 'required|exists:departments,id',
        'formData.is_inactive' => 'boolean',
    ];

    public function mount()
    {
        $this->initListsForFields();

        // Set default visible fields
        $this->visibleFields = ['rule_id', 'department_id', 'is_inactive'];
        $this->visibleFilterFields = ['rule_id', 'department_id', 'is_inactive'];

        // Initialize filters
        $this->filters = array_fill_keys(array_keys($this->filterFields), '');

        // Initialize statuses
        $this->refreshStatuses();
    }

    protected function initListsForFields(): void
    {
        $this->listsForFields['rules_list'] = LeaveApprovalRule::where('firm_id', session('firm_id'))
            ->pluck('id', 'id')
            ->toArray();

        // Enhance department list to show more meaningful information
        $this->listsForFields['departments_list'] = Department::where('firm_id', session('firm_id'))
            ->orderBy('title')
            ->pluck('title', 'id')
            ->toArray();

        $this->listsForFields['status_list'] = [
            '0' => 'Active',
            '1' => 'Inactive'
        ];
    }

    public function refreshStatuses()
    {
        $this->statuses = DepartmentLeaveApprovalRule::where('firm_id', session('firm_id'))
            ->pluck('is_inactive', 'id')
            ->mapWithKeys(fn($val, $key) => [$key => !(bool) $val])
            ->toArray();
    }

    public function toggleStatus($id)
    {
        $rule = DepartmentLeaveApprovalRule::find($id);
        $rule->is_inactive = !$rule->is_inactive;
        $rule->save();

        $this->statuses[$id] = !$rule->is_inactive;
        $this->refreshStatuses();
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
        return DepartmentLeaveApprovalRule::query()
            ->with([
                'leave_approval_rule',
                'department' => function ($query) {
                    $query->select('id', 'title');
                }
            ])
            ->where('firm_id', Session::get('firm_id'))
            ->when($this->filters['rule_id'], fn($query, $value) =>
                $query->where('rule_id', $value))
            ->when($this->filters['department_id'], fn($query, $value) =>
                $query->where('department_id', $value))
            ->when($this->filters['is_inactive'] !== '', fn($query, $value) =>
                $query->where('is_inactive', $value))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);
    }

    public function store()
    {
        $validatedData = $this->validate();

        $validatedData['formData'] = collect($validatedData['formData'])
            ->map(fn($val) => $val === '' ? null : $val)
            ->toArray();

        $validatedData['formData']['firm_id'] = session('firm_id');

        if ($this->isEditing) {
            $rule = DepartmentLeaveApprovalRule::findOrFail($this->formData['id']);
            $rule->update($validatedData['formData']);
            $toastMsg = 'Department rule updated successfully';
        } else {
            DepartmentLeaveApprovalRule::create($validatedData['formData']);
            $toastMsg = 'Department rule added successfully';
        }

        $this->resetForm();
        $this->refreshStatuses();
        $this->modal('mdl-department-rule')->close();
        Flux::toast(
            variant: 'success',
            heading: 'Changes saved.',
            text: $toastMsg,
        );
    }

    public function resetForm()
    {
        $this->reset(['formData']);
        $this->formData['is_inactive'] = false;
        $this->isEditing = false;
    }

    public function edit($id)
    {
        $this->isEditing = true;
        $rule = DepartmentLeaveApprovalRule::findOrFail($id);
        $this->formData = $rule->toArray();
        $this->modal('mdl-department-rule')->show();
    }

    public function delete($id)
    {
        try {
            $rule = DepartmentLeaveApprovalRule::findOrFail($id);
            $rule->delete();

            Flux::toast(
                variant: 'success',
                heading: 'Record Deleted.',
                text: 'Department rule has been deleted successfully',
            );
        } catch (\Exception $e) {
            Flux::toast(
                variant: 'error',
                heading: 'Error',
                text: 'Failed to delete department rule: ' . $e->getMessage(),
            );
        }
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Leave/LeaveApprovalRules/blade/department-leave-approval-rules.blade.php'));
    }
}
