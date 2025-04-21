<?php

namespace App\Livewire\Saas\UserMeta;

use Livewire\Component;
use App\Models\User;
use App\Models\Saas\Panel;
use Flux;

class PanelSync extends Component
{
    public User $user;
    public array $selectedPanels = [];
    public array $listsForFields = [];

    public function mount($userId)
    {
        $this->user = User::findOrFail($userId);
        $this->selectedPanels = $this->user->panels()->select('panels.id')->pluck('id')->toArray();
        $this->initListsForFields();
    }

    public function save()
    {
        $this->user->panels()->sync($this->selectedPanels);
        Flux::modal('panel-sync')->close();

        Flux::toast(
            variant: 'success',
            heading: 'Changes saved.',
            text: 'Panels updated successfully!',
        );
    }

    protected function initListsForFields(): void
    {
//        $this->listsForFields['panellist'] = Panel::pluck('name', 'id')->toArray();
        $this->listsForFields['panellist'] = Panel::get()
            ->mapWithKeys(function ($panel) {
                return [
                    $panel->id => $panel->panel_type_label . ' - ' . $panel->name
                ];
            })
            ->toArray();
    }


}
