<?php

namespace App\Livewire\Panelr;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Topbarr extends Component
{
    public $panels = [];
    public $firms = [];
    public $currentPanel;
    public $currentFirm;

    public function mount()
    {
        $this->firms = auth()->user()->firms()->where('is_inactive', false)->get();
        if ($this->firms->isNotEmpty()) {
            $this->currentFirm = session('firm_id', $this->firms->first()->id);
            session(['firm_id' => $this->currentFirm]);
        }
        
        $this->panels = auth()->user()->panels()
            ->where('is_inactive', false)
            ->where('panel_type', '2')
            ->get()
            ->unique('id');
            
        if ($this->panels->isNotEmpty()) {
            $this->currentPanel = session('panel_id', $this->panels->first()->id);
            session(['panel_id' => $this->currentPanel]);
        }
        
        $this->setLopDeductionType();
    }
    
    private function setLopDeductionType()
    {
        $currentFirmId = (int) session('firm_id');
        if ($currentFirmId === 2) {
            session(['LOP_deduction_type' => 'calculation_wise']);
        } else {
            session(['LOP_deduction_type' => '']);
        }
    }

    public function changePanel($panelId)
    {
        $this->currentPanel = $panelId;
        session(['panel_id' => $this->currentPanel]);
        session()->forget('selected_app_id');
        return redirect(request()->header('Referer'));
    }

    public function changefirm($firmId)
    {
        $this->currentFirm = $firmId;
        session(['firm_id' => $this->currentFirm]);
        
        $this->panels = auth()->user()->panels()
            ->where('is_inactive', false)
            ->where('panel_type', '2')
            ->get()
            ->unique('id');
            
        if ($this->panels->isNotEmpty()) {
            $this->currentPanel = $this->panels->first()->id;
            session(['panel_id' => $this->currentPanel]);
        }
        
        $this->setLopDeductionType();
        session()->forget('selected_app_id');
        return redirect(request()->header('Referer'));
    }

    public function render()
    {
        return view('livewire.panelr.topbarr');
    }
}
