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
        if ($this->firmId) {
            // Get user's panels for this specific firm
            $this->selectedPanels = $this->user->panels()
                ->wherePivot('firm_id', $this->firmId)
                ->pluck('panels.id')
                ->toArray();
        } else {
            // Get all user panels if no firm specified
            $this->selectedPanels = $this->user->panels()
                ->pluck('panels.id')
                ->toArray();
        }
        
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
        // Fetch all panels from the database, no filters
        $panels = Panel::all()->pluck('name', 'id');
        $this->listsForFields['panellist'] = $panels->mapWithKeys(function ($name, $id) {
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
