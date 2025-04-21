<?php

namespace App\Livewire\Saas;

use App\Models\Saas\Agency;
use App\Models\Saas\Firm;
use Livewire\Component;
use Livewire\WithPagination;
use Flux;

class Firms extends Component
{
    use WithPagination;
    public $selectedId = null;
    public array $listsForFields = [];
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $statuses;
    public $setMasterStatuses;
    public $formData = [
        'id' => null,
        'name' => '',
        'short_name' => '',
        'firm_type' => '',
        'agency_id' => '',
        'parent_firm_id' => '',
        'is_master_firm' => 0,
        'is_inactive'=> 0,
    ];

    public $isEditing = false;
    public $modal = false;

    public function mount()
    {
        $this->refreshStatuses();
        $this->refreshSetMasterStatus();
        $this->initListsForFields();
    }

    #[\Livewire\Attributes\Computed]
    public function list()
    {
        return Firm::query()
            ->when($this->sortBy, fn($query) => $query->orderBy($this->sortBy, $this->sortDirection))
            ->paginate(5);
    }

    public function store()
    {


        $validatedData = $this->validate([
            'formData.name' => 'required|string|max:255',
            'formData.short_name' => 'nullable|string|max:255',
            'formData.firm_type' => 'nullable|string|max:255',
            'formData.agency_id' => 'nullable|integer|max:255',
            'formData.parent_firm_id' => 'nullable|integer|exists:firms,id',
            'formData.is_master_firm' => 'boolean',
            'formData.is_inactive' => 'boolean',

        ]);

        // Convert empty strings to null
        $validatedData['formData'] = collect($validatedData['formData'])
            ->map(fn($val) => $val === '' ? null : $val)
            ->toArray();

        if ($this->isEditing) {
            // Editing: Update the record
            Firm::findOrFail($this->formData['id'])->update($validatedData['formData']);
            $toastMsg = 'Record updated successfully';
        } else {
            firm::create($validatedData['formData']);
            $toastMsg = 'Record added successfully';
        }

        // Reset the form and editing state after saving
        $this->resetForm();
        $this->refreshStatuses();
        $this->refreshSetMasterStatus();
        $this->modal('mdl-firm')->close();
        Flux::toast(
            variant: 'success',
            heading: 'Changes saved.',
            text: $toastMsg,
        );

    }

    public function edit($id)
    {
        $this->formData = Firm::findOrFail($id)->toArray();
        $this->isEditing = true;
        $this->modal('mdl-firm')->show();

    }

    public function delete($id)
    {
        Firm::findOrFail($id)->delete();
        Flux::toast(
            variant: 'success',
            heading: 'Record Deleted.',
            text: 'Record has been deleted successfully',
        );
    }

    public function resetForm()
    {
        $this->reset(['formData']);
        $this->formData['is_master_firm'] = 0; // or false
        $this->formData['is_inactive'] = 0; // or false
        $this->isEditing = false;
    }

    public function refreshStatuses()
    {
        $this->statuses = Firm::pluck('is_inactive', 'id')
            ->mapWithKeys(fn($val, $key) => [$key => (bool)$val])
            ->toArray();
    }
    public function toggleStatus($firmId)
    {
        $firm = Firm::find($firmId);
        $firm->is_inactive = !$firm->is_inactive;
        $firm->save();

        $this->statuses[$firmId] = $firm->is_inactive;
        $this->refreshStatuses();
    }

    public function refreshSetMasterStatus()
    {
        $this->setMasterStatuses = Firm::pluck('is_master_firm', 'id')
            ->mapWithKeys(fn($val, $key) => [$key => (bool)$val])
            ->toArray();
    }
    public function toggleSetMasterStatus($firmId)
    {
        $firm = Firm::find($firmId);
        $firm->is_master_firm = !$firm->is_master_firm;
        $firm->save();

        $this->setMasterStatuses[$firmId] = $firm->is_master_firm;
        $this->refreshSetMasterStatus();
    }

    protected function initListsForFields(): void
    {
        $this->listsForFields['agencylist'] = Agency::pluck('name', 'id')->toArray();
        $this->listsForFields['firmlist'] = Firm::pluck('name', 'id')->toArray();
        $this->listsForFields['firm_type'] = Firm::FIRM_TYPE_SELECT;
    }

    public function showAppAccess($firmId)
    {
        $this->selectedId = $firmId;
        $this->modal('app-access')->show();
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Saas/blades/firms.blade.php'));
    }

}
