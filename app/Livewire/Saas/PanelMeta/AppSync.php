<?php

namespace App\Livewire\Saas\PanelMeta;

use Livewire\Component;
use App\Models\Saas\Panel;
use App\Models\Saas\App;
use Flux;

class AppSync extends Component
{
    public Panel $panel;
    public array $selectedApps = [];
    public array $listsForFields = [];

    public function mount($panelId)
    {
        $this->panel = Panel::findOrFail($panelId);
        $this->selectedApps = $this->panel->apps()->select('apps.id')->pluck('id')->toArray();
        $this->initListsForFields();
    }

    public function save()
    {
        $this->panel->apps()->sync($this->selectedApps);
        Flux::modal('app-sync')->close();

        Flux::toast(
            variant: 'success',
            heading: 'Changes saved.',
            text: 'Apps updated successfully!',
        );
    }

    protected function initListsForFields(): void
    {
        $this->listsForFields['applist'] = App::where('is_inactive', false)
            ->get()
            ->mapWithKeys(function ($app) {
                return [
                    $app->id => $app->name . ' (' . $app->code . ')'
                ];
            })
            ->toArray();
    }

    public function render()
    {
        return view('livewire.saas.panel-meta.app-sync');
    }
} 