<?php

namespace App\Livewire\Panel;

use Livewire\Component;
use App\Models\Saas\Permission;

class PermissionTopbar extends Component
{
    public $permissions = [];


    public function mount()
    {

        $this->permissions = session('current_module_permissions', []);

    }

    public function render()
    {
        return view('livewire.panel.permissiontopbar');
    }
}
