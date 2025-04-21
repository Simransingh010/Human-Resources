<?php

namespace App\Livewire\Saas;

use App\Models\Saas\FirmBranding;
use App\Models\Saas\Firm;
use Livewire\Component;
use Livewire\WithPagination;
use Flux;

class FirmBrandings extends Component
{
    use WithPagination;

    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $statuses;
    public array $listsForFields = [];
    public $formData = [
        'id' => null,
        'firm_id' => null,
        'brand_name' => '',
        'brand_slogan' => '',
        'website' => '',
        'email' => '',
        'phone' => '',
        'facebook' => '',
        'linkedin' => '',
        'instagram' => '',
        'youtube' => '',
        'color_scheme' => '',
        'logo' => '',
        'logo_dark' => '',
        'favicon' => '',
        'legal_entity_type' => '',
        'legal_reg_certificate' => '',
        'legal_certificate_number' => '',
        'tax_reg_certificate' => '',
        'tax_certificate_no' => '',
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
        $this->statuses = FirmBranding::pluck('is_inactive', 'id')
            ->mapWithKeys(fn($val, $key) => [$key => (bool)$val])
            ->toArray();
    }

    protected function initListsForFields(): void
    {
        $this->listsForFields['firms'] = Firm::where('is_inactive', false)
            ->pluck('name', 'id')
            ->toArray();
    }

    #[\Livewire\Attributes\Computed]
    public function list()
    {
        return FirmBranding::with('firm')
            ->when($this->sortBy, fn($query) => $query->orderBy($this->sortBy, $this->sortDirection))
            ->paginate(5);
    }

    public function store()
    {
        $validatedData = $this->validate([
            'formData.firm_id' => 'required|exists:firms,id',
            'formData.brand_name' => 'required|string|max:255',
            'formData.brand_slogan' => 'nullable|string|max:255',
            'formData.website' => 'nullable|string|max:255|url',
            'formData.email' => 'nullable|email|max:255',
            'formData.phone' => 'nullable|string|max:255',
            'formData.facebook' => 'nullable|string|max:255|url',
            'formData.linkedin' => 'nullable|string|max:255|url',
            'formData.instagram' => 'nullable|string|max:255|url',
            'formData.youtube' => 'nullable|string|max:255|url',
            'formData.color_scheme' => 'nullable|string|max:255',
            'formData.logo' => 'nullable|string|max:255',
            'formData.logo_dark' => 'nullable|string|max:255',
            'formData.favicon' => 'nullable|string|max:255',
            'formData.legal_entity_type' => 'nullable|string|max:255',
            'formData.legal_reg_certificate' => 'nullable|string|max:255',
            'formData.legal_certificate_number' => 'nullable|string|max:255',
            'formData.tax_reg_certificate' => 'nullable|string|max:255',
            'formData.tax_certificate_no' => 'nullable|string|max:255',
            'formData.is_inactive' => 'boolean',
        ]);

        // Convert empty strings to null
        $validatedData['formData'] = collect($validatedData['formData'])
            ->map(fn($val) => $val === '' ? null : $val)
            ->toArray();

        if ($this->isEditing) {
            $firmBranding = FirmBranding::findOrFail($this->formData['id']);
            $firmBranding->update($validatedData['formData']);
            $toastMsg = 'Firm Branding updated successfully';
        } else {
            FirmBranding::create($validatedData['formData']);
            $toastMsg = 'Firm Branding added successfully';
        }

        $this->resetForm();
        $this->refreshStatuses();
        $this->modal('mdl-firm-branding')->close();
        Flux::toast(
            variant: 'success',
            heading: 'Changes saved.',
            text: $toastMsg,
        );
    }

    public function resetForm()
    {
        $this->reset(['formData']);
        $this->formData['is_inactive'] = 0;
        $this->isEditing = false;
    }

    public function edit($id)
    {
        $firmBranding = FirmBranding::findOrFail($id);
        $this->formData = $firmBranding->toArray();
        $this->isEditing = true;
        $this->modal('mdl-firm-branding')->show();
    }

    public function delete($id)
    {
        FirmBranding::findOrFail($id)->delete();
        Flux::toast(
            variant: 'success',
            heading: 'Firm Branding Deleted.',
            text: 'Firm Branding has been deleted successfully',
        );
    }

    public function toggleStatus($firmBrandingId)
    {
        $firmBranding = FirmBranding::find($firmBrandingId);
        $firmBranding->is_inactive = !$firmBranding->is_inactive;
        $firmBranding->save();

        $this->statuses[$firmBrandingId] = $firmBranding->is_inactive;
        $this->refreshStatuses();
    }

    public function render()
    {
        return view('livewire.saas.firm-brandings');
    }
} 