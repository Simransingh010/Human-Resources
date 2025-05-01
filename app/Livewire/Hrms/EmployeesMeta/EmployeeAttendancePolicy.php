<?php

namespace App\Livewire\Hrms\EmployeesMeta;

use Livewire\Component;
use App\Models\Hrms\AttendancePolicy;
use App\Models\Hrms\Employee;
use Flux;

class EmployeeAttendancePolicy extends Component
{
    use \Livewire\WithPagination;

    public array $listsForFields = [];
    public Employee $employee;
    public array $policyStatuses = [];

    public $policyData = [
        'id' => null,
        'employee_id' => '',
        'camshot' => '',
        'geo' => '',
        'manual_marking' => '',
        'geo_validation' => '',
        'ip_validation' => '',
        'back_date_max_minutes' => '',
        'max_punches_a_day' => '',
        'grace_period_minutes' => '',
        'mark_absent_rule' => '',
        'overtime_rule' => '',
        'custom_rules' => '',
        'valid_from' => '',
        'valid_to' => '',
        'policy_text' => '',
    ];

    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $isEditing = false;

    public function mount($employeeId)
    {
        session()->put('firm_id', session('firm_id'));
        $this->employee = Employee::findOrFail($employeeId);
        $this->policiesList();
        $this->initListsForFields();
    }


    public function sort($column)
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
    }

    #[\Livewire\Attributes\Computed]
    public function policiesList()
    {
        return AttendancePolicy::query()
            ->where('employee_id', $this->employee->id)
            ->where('firm_id', session('firm_id'))
            ->when($this->sortBy, fn($query) => $query->orderBy($this->sortBy, $this->sortDirection))
            ->get();
    }

    public function fetchPolicy($id)
    {
        $policy = AttendancePolicy::findOrFail($id);
        $this->policyData = $policy->toArray();
        $this->isEditing = true;
        $this->modal('mdl-policy')->show();
    }

    public function savePolicy()
    {
        $validatedData = $this->validate([
            'policyData.camshot' => 'required|string',
            'policyData.geo' => 'required|string',
            'policyData.manual_marking' => 'required|string',
            'policyData.geo_validation' => 'nullable|string',
            'policyData.ip_validation' => 'nullable|string',
            'policyData.back_date_max_minutes' => 'nullable|integer',
            'policyData.max_punches_a_day' => 'nullable|integer',
            'policyData.grace_period_minutes' => 'nullable|string',
            'policyData.mark_absent_rule' => 'nullable|string',
            'policyData.overtime_rule' => 'nullable|string',
            'policyData.custom_rules' => 'nullable|string',
            'policyData.valid_from' => 'nullable|date',
            'policyData.valid_to' => 'nullable|date',
            'policyData.policy_text' => 'nullable|string',
        ]);

        $validatedData['policyData']['employee_id'] = $this->employee->id;

        if ($this->isEditing) {
            $policy = AttendancePolicy::findOrFail($this->policyData['id']);
            $policy->update($validatedData['policyData']);
            $toast = 'Policy updated successfully.';
        } else {
            $validatedData['policyData']['firm_id'] = session('firm_id');
            AttendancePolicy::create($validatedData['policyData']);
            $toast = 'Policy added successfully.';
        }

        $this->resetForm();
        $this->modal('mdl-policy')->close();
        Flux::toast(
            heading: 'Changes saved.',
            text: $toast,
        );
    }

    protected function initListsForFields(): void
    {
        $this->listsForFields['camshot'] = AttendancePolicy::CAMSHOT_SELECT;
        $this->listsForFields['geo'] = AttendancePolicy::GEO_SELECT;
        $this->listsForFields['manual_marking'] = AttendancePolicy::MANUAL_MARKING_SELECT;
    }

    public function resetForm()
    {
        $this->policyData = [
            'id' => null,
            'employee_id' => '',
            'camshot' => '',
            'geo' => '',
            'manual_marking' => '',
            'geo_validation' => '',
            'ip_validation' => '',
            'back_date_max_minutes' => '',
            'max_punches_a_day' => '',
            'grace_period_minutes' => '',
            'mark_absent_rule' => '',
            'overtime_rule' => '',
            'custom_rules' => '',
            'valid_from' => '',
            'valid_to' => '',
            'policy_text' => '',
        ];
        $this->isEditing = false;
    }

    public function deletePolicy($policyId)
    {
        $policy = AttendancePolicy::findOrFail($policyId);

        $policy->delete();

        Flux::toast(
            heading: 'Policy Deleted',
            text: "Attendance policy has been deleted successfully."
        );
    }

    public function render()
    {
        return view('livewire.hrms.employees-meta.employee-attendance-policy');
    }
}
