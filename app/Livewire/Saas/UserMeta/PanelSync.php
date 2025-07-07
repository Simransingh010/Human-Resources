<?php

namespace App\Livewire\Saas\UserMeta;

use Livewire\Component;
use App\Models\User;
use App\Models\Saas\Panel;
use App\Models\Saas\Firm;
use Illuminate\Support\Facades\View;
use Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Saas\PanelUser;

class PanelSync extends Component
{
    public User $user;
    public array $selectedPanels = [];
    public array $listsForFields = [];
    public ?int $firmId;

    public function mount($userId, $firmId = null)
    {

        $this->user = User::findOrFail($userId);
        $this->firmId = $firmId;  // Set the firmId
        
        // Get panels for specific firm if firmId is provided
        $this->selectedPanels = $this->user->panels()
            ->pluck('panels.id')
            ->toArray();
        
        $this->initListsForFields();
    }

    public function save()
    {
        if ($this->firmId) {
            // Create new panel_user records for each selected panel
            foreach ($this->selectedPanels as $panelId) {
                PanelUser::updateOrCreate(
                    [
                        'user_id' => $this->user->id,
                        'panel_id' => $panelId,
                        'firm_id' => $this->firmId
                    ]
                );
            }
        }

        Flux::modal('panel-sync')->close();

        Flux::toast(
            variant: 'success',
            heading: 'Changes saved.',
            text: 'Panels updated successfully!',
        );
    }

    protected function initListsForFields(): void
    {
        // Get all available panels without any filters
        $panels = Panel::pluck('id', 'name');
        
        $this->listsForFields['panellist'] = $panels->mapWithKeys(function ($id, $name) {
            $panel = Panel::find($id);
            $typeLabel = Panel::PANEL_TYPE_SELECT[$panel->panel_type] ?? '';
            return [
                $id => "{$typeLabel} - {$name}"
            ];
        })->toArray();
    }

    public function render()
    {
        return View::make('livewire.saas.user-meta.panel-sync');
    }
}
