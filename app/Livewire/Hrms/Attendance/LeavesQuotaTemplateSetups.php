<?php

namespace App\Livewire\Hrms\Attendance;

use Livewire\Component;
use App\Models\Hrms\LeavesQuotaTemplateSetup;
use App\Models\Hrms\LeavesQuotaTemplate;
use App\Models\Hrms\LeaveType;
use Flux;

class LeavesQuotaTemplateSetups extends Component
{
    use \Livewire\WithPagination;

    public $setupData = [
        'id' => null,
        'firm_id' => null,
        'leaves_quota_template_id' => '',
        'leave_type_id' => '',
        'days_assigned' => 0,
        'alloc_period_unit' => '',
        'alloc_period_value' => null,
    ];

    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $isEditing = false;
    public $templateId;

    public function mount($templateId = null)
    {
        $this->templateId = $templateId;
        if ($templateId) {
            $this->setupData['leaves_quota_template_id'] = $templateId;
        }
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
    public function setupsList()
    {
        return LeavesQuotaTemplateSetup::query()
            ->with(['leaves_quota_template', 'leave_type'])
            ->when($this->sortBy, fn($query) => $query->orderBy($this->sortBy, $this->sortDirection))
            ->when($this->templateId, fn($query) => $query->where('leaves_quota_template_id', $this->templateId))
            ->where('firm_id', session('firm_id'))
            ->paginate(10);
    }

    #[\Livewire\Attributes\Computed]
    public function templatesList()
    {
        return LeavesQuotaTemplate::where('firm_id', session('firm_id'))
            ->pluck('name', 'id');
    }

    #[\Livewire\Attributes\Computed]
    public function leaveTypesList()
    {
        return LeaveType::where('firm_id', session('firm_id'))
            ->pluck('leave_title', 'id');
    }

    public function fetchSetup($id)
    {
        $setup = LeavesQuotaTemplateSetup::findOrFail($id);
        $this->setupData = $setup->toArray();
        $this->isEditing = true;
        $this->modal('mdl-quota-setup')->show();
    }

    public function saveSetup()
    {
        $validatedData = $this->validate([
            'setupData.leaves_quota_template_id' => 'required|exists:leaves_quota_templates,id',
            'setupData.leave_type_id' => 'required|exists:leave_types,id',
            'setupData.days_assigned' => 'required|integer|min:0',
            'setupData.alloc_period_unit' => 'required|in:days,months,years',
            'setupData.alloc_period_value' => 'required|integer|min:1',
        ]);

        try {
            if ($this->isEditing) {
                $setup = LeavesQuotaTemplateSetup::findOrFail($this->setupData['id']);
                $setup->update($validatedData['setupData']);
            } else {
                $validatedData['setupData']['firm_id'] = session('firm_id');
                if ($this->templateId) {
                    $validatedData['setupData']['leaves_quota_template_id'] = $this->templateId;
                }
                LeavesQuotaTemplateSetup::create($validatedData['setupData']);
            }

            $this->resetForm();
            $this->modal('mdl-quota-setup')->close();
            
            Flux::toast(
                variant: 'success',
                position: 'top-right',
                heading: 'Success',
                text: 'Quota template setup ' . ($this->isEditing ? 'updated' : 'added') . ' successfully.'
            );
        } catch (\Exception $e) {
            Flux::toast(
                variant: 'error',
                position: 'top-right',
                heading: 'Error',
                text: 'Failed to save setup: ' . $e->getMessage()
            );
        }
    }

    public function deleteSetup($id)
    {
        try {
            $setup = LeavesQuotaTemplateSetup::findOrFail($id);
            $setup->delete();

            Flux::toast(
                position: 'top-right',
                variant: 'success',
                heading: 'Success',
                text: 'Setup deleted successfully.'
            );
        } catch (\Exception $e) {
            Flux::toast(
                position: 'top-right',
                variant: 'error',
                heading: 'Error',
                text: 'Failed to delete setup.'
            );
        }
    }

    public function resetForm()
    {
        $this->setupData = [
            'id' => null,
            'firm_id' => null,
            'leaves_quota_template_id' => $this->templateId ?? '',
            'leave_type_id' => '',
            'days_assigned' => 0,
            'alloc_period_unit' => '',
            'alloc_period_value' => null,
        ];
        $this->isEditing = false;
    }

    public function render()
    {
        return view('livewire.hrms.attendance.leaves-quota-template-setups');
    }
} 