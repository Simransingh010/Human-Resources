<?php

namespace App\Livewire\Panel;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;


class Topbar extends Component
{
    public $panels = [];
    public $firms = [];
    public $currentPanel;
    public $currentFirm;

    public function mount()
    {
        $this->panels = auth()->user()->panels()->where('is_inactive', false)->where('panel_type', '2')->get();
        if ($this->panels->isNotEmpty()) {
            $this->currentPanel = session('panel_id', $this->panels->first()->id);
            session(['panel_id' => $this->currentPanel]);
        }

        $this->firms = auth()->user()->firms()->where('is_inactive', false)->get();
        if ($this->firms->isNotEmpty()) {
            $this->currentFirm = session('firm_id', $this->firms->first()->id);
            session(['firm_id' => $this->currentFirm]);
        }
    }

    public function changePanel($panelId)
    {
        $this->currentPanel = $panelId;
        session(['panel_id' => $this->currentPanel]);
        // Clear the selected_app_id so the new panel can load its default app
        session()->forget('selected_app_id');
        return redirect(request()->header('Referer'));
    }

    public function changefirm($firmId)
    {
        $this->currentFirm = $firmId;
        session(['firm_id' => $this->currentFirm]);
        // Clear the selected_app_id so the new panel can load its default app
        session()->forget('selected_app_id');
        return redirect(request()->header('Referer'));
    }

    public function render()
    {
        return view('livewire.panel.topbar');
    }
}
