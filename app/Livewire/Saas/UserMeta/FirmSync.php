<?php

namespace App\Livewire\Saas\UserMeta;

use Livewire\Component;
use App\Models\User;
use App\Models\Saas\Firm;
use Flux;

class FirmSync extends Component
{
    public User $user;
    public array $selectedFirms = [];
    public array $listsForFields = [];

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
        $this->listsForFields['firmlist'] = Firm::pluck('name', 'id')->toArray();

    }


}
