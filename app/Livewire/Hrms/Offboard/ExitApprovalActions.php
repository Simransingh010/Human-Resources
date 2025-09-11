<?php

namespace App\Livewire\Hrms\Offboard;

use App\Models\Hrms\ExitApprovalAction;
use App\Models\Hrms\ExitApprovalStep;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Cache;
use Flux;

class ExitApprovalActions extends Component
{
    use WithPagination;

    public $perPage = 10;
    public $sortBy = 'clearance_item';
    public $sortDirection = 'asc';
    public $isEditing = false;

    // Field configuration for form and table
    public array $fieldConfig = [
        'exit_approval_step_id' => ['label' => 'Exit Approval Step', 'type' => 'select', 'listKey' => 'exit_approval_steps'],
        'clearance_item' => ['label' => 'Clearance Item', 'type' => 'text'],
        'clearance_desc' => ['label' => 'Clearance Description', 'type' => 'textarea'],
        'is_inactive' => ['label' => 'Inactive', 'type' => 'switch'],
    ];

    // Filter fields configuration
    public array $filterFields = [
        'exit_approval_step_id' => ['label' => 'Exit Approval Step', 'type' => 'select', 'listKey' => 'exit_approval_steps'],
        'clearance_item' => ['label' => 'Clearance Item', 'type' => 'text'],
        'is_inactive' => ['label' => 'Inactive', 'type' => 'select', 'listKey' => 'inactive_options'],
    ];

    public array $listsForFields = [];
    public array $filters = [];
    public array $visibleFields = [];
    public array $visibleFilterFields = [];

    public $formData = [
        'id' => null,
        'firm_id' => null,
        'exit_approval_step_id' => '',
        'clearance_item' => '',
        'clearance_desc' => '',
        'is_inactive' => false,
    ];

    public function mount()
    {
        $this->initListsForFields();

        // Set default visible fields
        $this->visibleFields = ['exit_approval_step_id', 'clearance_item', 'clearance_desc'];
        $this->visibleFilterFields = ['exit_approval_step_id', 'clearance_item'];

        // Initialize filters
        $this->filters = array_fill_keys(array_keys($this->filterFields), '');
    }

    protected function initListsForFields(): void
    {
        $firmId = Session::get('firm_id');
        $cacheKey = "exit_approval_actions_lists_{$firmId}";

        // Use caching for better performance
        $this->listsForFields = Cache::remember($cacheKey, 300, function () use ($firmId) {
            return [
                'exit_approval_steps' => ExitApprovalStep::where('firm_id', $firmId)
                    ->where('is_inactive', false)
                    ->orderBy('flow_order')
                    ->get()
                    ->mapWithKeys(function ($step) {
                        $label = "Step {$step->flow_order} - {$step->approval_type}";
                        if ($step->exitEmployeeDepartment) {
                            $label .= " ({$step->exitEmployeeDepartment->title})";
                        }
                        return [$step->id => $label];
                    })
                    ->toArray(),
                'inactive_options' => [
                    '1' => 'Yes',
                    '0' => 'No'
                ]
            ];
        });
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
        $firmId = Session::get('firm_id');
        
        return ExitApprovalAction::query()
            ->with(['exitApprovalStep.exitEmployeeDepartment', 'exitApprovalStep.exitEmployeeDesignation'])
            ->where('firm_id', $firmId)
            ->when($this->filters['exit_approval_step_id'], fn($query, $value) =>
                $query->where('exit_approval_step_id', $value))
            ->when($this->filters['clearance_item'], fn($query, $value) =>
                $query->where('clearance_item', 'like', "%{$value}%"))
            ->when($this->filters['is_inactive'] !== '', fn($query, $value) =>
                $query->where('is_inactive', $value))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);
    }

    protected function rules()
    {
        return [
            'formData.exit_approval_step_id' => 'required|exists:exit_approval_steps,id',
            'formData.clearance_item' => 'required|string|max:255',
            'formData.clearance_desc' => 'nullable|string|max:1000',
            'formData.is_inactive' => 'boolean'
        ];
    }

    public function store()
    {
        $validatedData = $this->validate();

        $validatedData['formData'] = collect($validatedData['formData'])
            ->map(fn($val) => $val === '' ? null : $val)
            ->toArray();

        $validatedData['formData']['firm_id'] = session('firm_id');

        if ($this->isEditing) {
            $exitApprovalAction = ExitApprovalAction::findOrFail($this->formData['id']);
            $exitApprovalAction->update($validatedData['formData']);
            $toastMsg = 'Exit approval action updated successfully';
        } else {
            ExitApprovalAction::create($validatedData['formData']);
            $toastMsg = 'Exit approval action added successfully';
        }

        // Clear cache to refresh lists
        $this->clearCache();
        
        $this->resetForm();
        $this->modal('mdl-exit-approval-action')->close();
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
        $exitApprovalAction = ExitApprovalAction::findOrFail($id);
        $this->formData = $exitApprovalAction->toArray();
        $this->modal('mdl-exit-approval-action')->show();
    }

    public function delete($id)
    {
        $exitApprovalAction = ExitApprovalAction::findOrFail($id);
        $exitApprovalAction->delete();
        
        // Clear cache to refresh lists
        $this->clearCache();
        
        Flux::toast(
            variant: 'success',
            heading: 'Record Deleted.',
            text: 'Exit approval action has been deleted successfully',
        );
    }

    protected function clearCache()
    {
        $firmId = Session::get('firm_id');
        Cache::forget("exit_approval_actions_lists_{$firmId}");
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Offboard/blades/exit-approval-actions.blade.php'));
    }
}
