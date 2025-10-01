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
        
        // Ensure LOP deduction type is set based on current firm
        $this->setLopDeductionType();
    }
    
    /**
     * Set LOP deduction type based on current firm ID
     */
    private function setLopDeductionType()
    {
        $currentFirmId = (int) session('firm_id');
        if ($currentFirmId === 2) {
            session(['LOP_deduction_type' => 'calculation_wise']);
            \Log::info('LOP DEDUCTION TYPE SET - Mount', [
                'firm_id' => $currentFirmId,
                'lop_deduction_type' => session('LOP_deduction_type')
            ]);
        } else {
            session(['LOP_deduction_type' => '']); // Keep blank for other firms
            \Log::info('LOP DEDUCTION TYPE NOT SET - Mount', [
                'firm_id' => $currentFirmId,
                'lop_deduction_type' => session('LOP_deduction_type')
            ]);
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
        
        // Set LOP deduction type based on new firm ID
        $this->setLopDeductionType();
        
        // Clear the selected_app_id so the new panel can load its default app
        session()->forget('selected_app_id');
        return redirect(request()->header('Referer'));
    }

    public function render()
    {
        return view('livewire.panel.topbar');
    }
}
