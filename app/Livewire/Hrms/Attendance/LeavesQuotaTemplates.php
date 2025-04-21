<?php

namespace App\Livewire\Hrms\Attendance;

use Livewire\Component;
use App\Models\Hrms\LeavesQuotaTemplate;
use Flux;

class LeavesQuotaTemplates extends Component
{
    use \Livewire\WithPagination;

    public $templateData = [
        'id' => null,
        'firm_id' => null,
        'name' => '',
        'desc' => '',
        'is_inactive' => false,
    ];

    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $isEditing = false;
    public $selectedTemplateId = null;

    public function mount()
    {
//    dd(session('firm_id'));
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
    public function templatesList()
    {
        return LeavesQuotaTemplate::query()
            ->with(['emp_leave_allocations', 'leaves_quota_template_setups'])
            ->when($this->sortBy, fn($query) => $query->orderBy($this->sortBy, $this->sortDirection))
            ->where('firm_id', session('firm_id'))
            ->paginate(10);
    }

    public function fetchTemplate($id)
    {
        $template = LeavesQuotaTemplate::findOrFail($id);
        $this->templateData = $template->toArray();
        $this->isEditing = true;
        $this->modal('mdl-quota-template')->show();
    }

    public function saveTemplate()
    {
        $validatedData = $this->validate([
            'templateData.name' => 'required|string|max:255',
            'templateData.desc' => 'nullable|string|max:500',
            'templateData.is_inactive' => 'boolean',
        ]);

        try {
            if ($this->isEditing) {
                $template = LeavesQuotaTemplate::findOrFail($this->templateData['id']);
                $template->update($validatedData['templateData']);
            } else {
                $validatedData['templateData']['firm_id'] = session('firm_id');
                LeavesQuotaTemplate::create($validatedData['templateData']);
            }

            $this->resetForm();
            $this->modal('mdl-quota-template')->close();
            
            Flux::toast(
                variant: 'success',
                position: 'top-right',
                heading: 'Success',
                text: 'Leave quota template ' . ($this->isEditing ? 'updated' : 'added') . ' successfully.'
            );
        } catch (\Exception $e) {
            Flux::toast(
                variant: 'error',
                position: 'top-right',
                heading: 'Error',
                text: 'Failed to save template: ' . $e->getMessage()
            );
        }
    }

    public function deleteTemplate($id)
    {
        try {
            $template = LeavesQuotaTemplate::findOrFail($id);
            $template->delete();

            Flux::toast(
                position: 'top-right',
                variant: 'success',
                heading: 'Success',
                text: 'Template deleted successfully.'
            );
        } catch (\Exception $e) {
            Flux::toast(
                position: 'top-right',
                variant: 'error',
                heading: 'Error',
                text: 'Failed to delete template.'
            );
        }
    }

    public function toggleStatus($id)
    {
        try {
            $template = LeavesQuotaTemplate::findOrFail($id);
            $template->update(['is_inactive' => !$template->is_inactive]);

            Flux::toast(
                position: 'top-right',
                variant: 'success',
                heading: 'Success',
                text: 'Template status updated successfully.'
            );
        } catch (\Exception $e) {
            Flux::toast(
                position: 'top-right',
                variant: 'error',
                heading: 'Error',
                text: 'Failed to update status.'
            );
        }
    }

    public function resetForm()
    {
        $this->templateData = [
            'id' => null,
            'firm_id' => null,
            'name' => '',
            'desc' => '',
            'is_inactive' => false,
        ];
        $this->isEditing = false;
    }

    public function showmodal_template_setup($templateId)
    {
        $this->selectedTemplateId = $templateId;
        $this->modal('add-template-setup')->show();
    }

    public function render()
    {
        return view('livewire.hrms.attendance.leaves-quota-templates');
    }
} 