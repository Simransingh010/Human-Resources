<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Saas;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class AppModule
 *
 * @property int $id
 * @property int $app_id
 * @property int|null $module_group_id
 * @property string $name
 * @property string|null $code
 * @property string|null $description
 * @property string|null $icon
 * @property string|null $route
 * @property string|null $color
 * @property string|null $tooltip
 * @property int $order
 * @property string|null $badge
 * @property string|null $custom_css
 * @property bool $is_inactive
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 *
 * @property App $app
 * @property ModuleGroup|null $module_group
 * @property Collection|FirmAppAccess[] $firm_app_accesses
 * @property Collection|Panel[] $panels
 * @property Collection|Permission[] $permissions
 *
 * @package App\Models\Saas
 */
class AppModule extends Model
{
    use SoftDeletes,HasFactory;
    protected $table = 'app_modules';

    protected $casts = [
        'app_id' => 'int',
        'module_group_id' => 'int',
        'order' => 'int',
        'is_inactive' => 'bool'
    ];

    protected $fillable = [
        'app_id',
        'module_group_id',
        'name',
        'code',
        'description',
        'icon',
        'route',
        'color',
        'tooltip',
        'order',
        'badge',
        'custom_css',
        'is_inactive'
    ];

    public function app()
    {
        return $this->belongsTo(App::class);
    }

    public function module_group()
    {
        return $this->belongsTo(ModuleGroup::class);
    }

    public function firm_app_accesses()
    {
        return $this->hasMany(FirmAppAccess::class);
    }

    public function panels()
    {
        return $this->belongsToMany(Panel::class, 'panel_app_module')
            ->withPivot('id', 'deleted_at')
            ->withTimestamps();
    }
    public function modules()
    {
        return $this->belongsToMany(Module::class, 'app_module', 'app_id', 'module_id');
    }
    public function permissions()
    {
        return $this->hasMany(Permission::class);
    }
}
