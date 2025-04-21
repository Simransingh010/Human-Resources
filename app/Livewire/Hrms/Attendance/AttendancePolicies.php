<?php

namespace App\Livewire\Hrms\Attendance;

use Livewire\Component;
use App\Models\Hrms\AttendancePolicy;
use Flux;

class AttendancePolicies extends Component
{
    use \Livewire\WithPagination;

    public $policyData = [
        'id' => null,
        'firm_id' => null,
        'employee_id' => null,
        'camshot' => '',
        'geo' => '',
        'manual_marking' => '',
        'geo_validation' => '',
        'ip_validation' => '',
        'back_date_max_minutes' => null,
        'max_punches_a_day' => null,
        'grace_period_minutes' => null,
        'mark_absent_rule' => '',
        'overtime_rule' => '',
        'valid_from' => null,
        'valid_to' => null,
        'policy_text' => '',
    ];

    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $isEditing = false;

    public function mount()
    {
        session()->put('firm_id', 3);
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
            ->when($this->sortBy, fn($query) => $query->orderBy($this->sortBy, $this->sortDirection))
            ->where('firm_id', session('firm_id'))
            ->paginate(5);
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
            'policyData.camshot' => 'required|in:1,2,3',
            'policyData.geo' => 'required|in:1,2,3',
            'policyData.manual_marking' => 'required|in:1,2,3',
            'policyData.geo_validation' => 'nullable|string',
            'policyData.ip_validation' => 'nullable|string',
            'policyData.back_date_max_minutes' => 'nullable|integer',
            'policyData.max_punches_a_day' => 'nullable|integer',
            'policyData.grace_period_minutes' => 'nullable|string',
            'policyData.valid_from' => 'nullable|date',
            'policyData.valid_to' => 'nullable|date',
        ]);

        if ($this->isEditing) {
            $policy = AttendancePolicy::findOrFail($this->policyData['id']);
            $policy->update($validatedData['policyData']);
            session()->flash('message', 'Policy updated successfully.');
        } else {
            $validatedData['policyData']['firm_id'] = session('firm_id');
            AttendancePolicy::create($validatedData['policyData']);
            session()->flash('message', 'Policy added successfully.');
        }

        $this->resetForm();
        $this->modal('mdl-policy')->close();
        Flux::toast(
            heading: 'Changes saved.',
            text: 'Attendance policy has been updated.',
        );
    }

    public function resetForm()
    {
        $this->policyData = [
            'id' => null,
            'firm_id' => null,
            'employee_id' => null,
            'camshot' => '',
            'geo' => '',
            'manual_marking' => '',
            'geo_validation' => '',
            'ip_validation' => '',
            'back_date_max_minutes' => null,
            'max_punches_a_day' => null,
            'grace_period_minutes' => null,
            'mark_absent_rule' => '',
            'overtime_rule' => '',
            'valid_from' => null,
            'valid_to' => null,
            'policy_text' => '',
        ];
        $this->isEditing = false;
    }

    public function deletePolicy($id)
    {
        try {
            $policy = AttendancePolicy::findOrFail($id);
            $policy->delete();

            Flux::toast(
                heading: 'Success',
                text: 'Policy deleted successfully.',
            );
        } catch (\Exception $e) {
            Flux::toast(
                heading: 'Error',
                text: 'Failed to delete policy.',
                variant: 'error'
            );
        }
    }

    public function render()
    {
//        dd('jbsjbck');
        return view('livewire.hrms.attendance.attendance-policies');
    }
} 