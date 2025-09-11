<?php

namespace App\Livewire\Saas\UserMeta;

use Livewire\Component;
use App\Models\User;
use App\Models\Saas\Firm;
use App\Models\Saas\Panel;
use App\Models\Saas\Role;
use App\Models\Saas\Action;
use Flux;

class FirmSync extends Component
{
    public User $user;
    public array $selectedFirms = [];
    public array $listsForFields = [];
    public ?int $selectedFirmId = null;

    public function mount($userId)
    {
        $this->user = User::findOrFail($userId);
        $this->selectedFirms = $this->user->firms()->select('firms.id')->pluck('id')->toArray();
        $this->initListsForFields();
    }

    public function save()
    {
        $this->user->firms()->sync($this->selectedFirms);
        Flux::modal('firm-sync')->close();

        Flux::toast(
            variant: 'success',
            heading: 'Changes saved.',
            text: 'Firms updated successfully!',
        );
    }

    protected function initListsForFields(): void
    {
        // Exclude the firm in which the user is an employee
        $employeeFirmId = $this->user->employee ? $this->user->employee->firm_id : null;
        if ($this->user->role_main === 'L1_firm') {
            $firmsQuery = $this->user->firms();
            if ($employeeFirmId) {
                $firmsQuery->where('firms.id', '!=', $employeeFirmId);
            }
            $this->listsForFields['firmlist'] = $firmsQuery
                ->orderBy('firms.name')
                ->pluck('firms.name', 'firms.id')
                ->toArray();
        } else {
            $firmsQuery = Firm::query();
            if ($employeeFirmId) {
                $firmsQuery->where('id', '!=', $employeeFirmId);
            }
            $this->listsForFields['firmlist'] = $firmsQuery
                ->orderBy('name')
                ->pluck('name', 'id')
                ->toArray();
        }
    }

    public function showPanelsModal($firmId)
    {
        $this->selectedFirmId = $firmId;
        
        Flux::modal('firm-panel-sync')->show();
    }

    public function showRolesModal($firmId)
    {
        $this->selectedFirmId = $firmId;
        Flux::modal('firm-permission-group-sync')->show();
    }

    public function showActionsModal($firmId)
    {
        $this->selectedFirmId = $firmId;
        Flux::modal('firm-permission-sync')->show();
    }

    public function render()
    {
        return view('livewire.saas.user-meta.firm-sync');
    }
}
