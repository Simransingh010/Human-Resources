<?php

namespace App\Livewire\Saas;

use App\Models\Saas\Version;
use Livewire\Component;
use Livewire\WithPagination;
use Flux;

class Versions extends Component
{
    use WithPagination;

    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $statuses;
    public array $listsForFields = [];
    public $formData = [
        'id' => null,
        'name' => '',
        'code' => '',
        'description' => '',
        'device_type' => '',
        'major_version' => '',
        'minor_version' => '',
        'is_inactive' => 0,
    ];

    public $isEditing = false;

    public function mount()
    {
        $this->refreshStatuses();
        $this->initListsForFields();
    }

    public function refreshStatuses()
    {
        $this->statuses = Version::pluck('is_inactive', 'id')
            ->mapWithKeys(fn($val, $key) => [$key => (bool)$val])
            ->toArray();
    }

    #[\Livewire\Attributes\Computed]
    public function list()
    {
        return Version::query()
            ->when($this->sortBy, fn($query) => $query->orderBy($this->sortBy, $this->sortDirection))
            ->paginate(5);
    }

    public function store()
    {
        $validatedData = $this->validate([
            'formData.name' => 'required|string|max:255',
            'formData.code' => 'nullable|string|max:255',
            'formData.description' => 'nullable|string',
            'formData.device_type' => 'required|string|max:255',
            'formData.major_version' => 'nullable|string|max:255',
            'formData.minor_version' => 'nullable|string|max:255',
            'formData.is_inactive' => 'boolean',
        ]);

        // Convert empty strings to null
        $validatedData['formData'] = collect($validatedData['formData'])
            ->map(fn($val) => $val === '' ? null : $val)
            ->toArray();

        if ($this->isEditing) {
            $version = Version::findOrFail($this->formData['id']);
            $version->update($validatedData['formData']);
            $toastMsg = 'Version updated successfully';
        } else {
            Version::create($validatedData['formData']);
            $toastMsg = 'Version added successfully';
        }

        $this->resetForm();
        $this->refreshStatuses();
        $this->modal('mdl-version')->close();
        Flux::toast(
            variant: 'success',
            heading: 'Changes saved.',
            text: $toastMsg,
        );
    }

    public function resetForm()
    {
        $this->reset(['formData']);
        $this->formData['is_inactive'] = 1;
        $this->isEditing = false;
    }

    public function edit($id)
    {
        $version = Version::findOrFail($id);
        $this->formData = $version->toArray();
        $this->isEditing = true;
        $this->modal('mdl-version')->show();
    }

    public function delete($id)
    {
        Version::findOrFail($id)->delete();
        Flux::toast(
            variant: 'success',
            heading: 'Version Deleted.',
            text: 'Version has been deleted successfully',
        );
    }

    public function toggleStatus($versionId)
    {
        $version = Version::find($versionId);
        $version->is_inactive = !$version->is_inactive;
        $version->save();

        $this->statuses[$versionId] = $version->is_inactive;
        $this->refreshStatuses();
    }

    protected function initListsForFields(): void
    {
        $this->listsForFields['device_type'] = Version::DEVICE_TYPE_SELECT;
    }

} 