<?php

namespace App\Livewire\Saas\FirmMeta;

use Livewire\Component;
use App\Models\Saas\Firm;
use App\Models\Saas\App;
use App\Models\Saas\AppModule;
use App\Models\Saas\FirmAppAccess;
use Flux;

class AppAccess extends Component
{
    public Firm $firm;
    public array $selectedApps = [];
    public array $selectedModules = [];
    public array $listsForFields = [];

    public function mount($firmId)
    {
        $this->firm = Firm::findOrFail($firmId);
        // Current access
        $this->selectedModules = FirmAppAccess::where('firm_id', $firmId)
            ->pluck('app_module_id')
            ->filter()
            ->map(fn($id) => (string) $id)
            ->toArray();

//        dd($this->selectedModules);

        $this->initListsForFields();
    }

    public function save()
    {
        // Remove existing access
        FirmAppAccess::where('firm_id', $this->firm->id)->delete();

        // Re-add selected
        foreach ($this->selectedModules as $moduleId) {
            $module = \App\Models\Saas\AppModule::find($moduleId);

            if ($module) {
                FirmAppAccess::updateOrInsert(
                    [
                        'firm_id' => $this->firm->id,
                        'app_id' => $module->app_id,
                        'app_module_id' => $module->id,
                    ],
                    [
                        'is_inactive' => false,
                        'updated_at' => now(),
                        'created_at' => now(),
                        'deleted_at' => NULL
                    ]
                );

            }
        }

        Flux::modal('app-access')->close();

        Flux::toast(
            variant: 'success',
            heading: 'Changes saved.',
            text: 'Modules updated successfully!',
        );
    }

    protected function initListsForFields()
    {
        $apps = App::with('app_modules')->get();
        $grouped = [];

        foreach ($apps as $app) {
            foreach ($app->app_modules as $module) {
                $grouped[$app->name][] = [
                    'id' => (string) $module->id,
                    'name' => $module->name,
                ];
            }
        }

        $this->listsForFields['apps'] = $grouped;
    }

    public function render()
    {
        return view('livewire.saas.firm-meta.app-access');
    }
}
