<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Saas;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class App
 *
 * @property int $id
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
 * @property Collection|Module[] $modules
 * @property Collection|AppModule[] $app_modules
 * @property Collection|Firm[] $firms
 * @property Collection|ModuleGroup[] $module_groups
 * @property Collection|Panel[] $panels
 *
 * @package App\Models\Saas
 */
class App extends Model
{
	use SoftDeletes;
	protected $table = 'apps';

	protected $casts = [
		'order' => 'int',
		'is_inactive' => 'bool'
	];

	protected $fillable = [
		'name',
		'code',
        'wire',
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

	public function modules()
	{
		return $this->belongsToMany(Module::class)
					->withPivot('id');
	}

	public function app_modules()
	{
		return $this->hasMany(AppModule::class);
	}

	public function firms()
	{
		return $this->belongsToMany(Firm::class, 'firm_app_access')
					->withPivot('id', 'app_module_id', 'is_inactive', 'deleted_at')
					->withTimestamps();
	}

	public function module_groups()
	{
		return $this->hasMany(ModuleGroup::class);
	}

	public function panels()
	{
		return $this->belongsToMany(Panel::class, 'panel_app')
					->withPivot('id', 'deleted_at')
					->withTimestamps();
	}
    public function getPanelsAttribute()
    {
        return $this->modules
            ->flatMap(fn ($module) => $module->components)
            ->flatMap(fn ($component) => $component->panels)
            ->unique('id');
    }
}
    