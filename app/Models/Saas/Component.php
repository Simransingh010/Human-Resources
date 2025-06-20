<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Saas;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Saas\Action;
use App\Models\Saas\Componentcluster;
use App\Models\Saas\Module;
use App\Models\Saas\Panel;

/**
 * Class Component
 *
 * @property int $id
 * @property string $name
 * @property string $code
 * @property string|null $description
 * @property string|null $icon
 * @property string|null $route
 * @property string|null $color
 * @property string|null $tooltip
 * @property int|null $order
 * @property string|null $badge
 * @property string|null $custom_css
 * @property bool $is_inactive
 * @property string|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property Collection|Action[] $actions
 * @property Collection|Componentcluster[] $componentclusters
 * @property Collection|Module[] $modules
 * @property Collection|Panel[] $panels
 *
 * @package App\Models\Saas
 */
class Component extends Model
{
	use SoftDeletes;
	protected $table = 'components';

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

	public function actions()
	{
		return $this->hasMany(Action::class);
	}

	public function componentclusters()
	{
		return $this->belongsToMany(Componentcluster::class)
					->withPivot('id');
	}

	public function modules()
	{
		return $this->belongsToMany(Module::class)
					->withPivot('id');
	}

	public function panels()
	{
		return $this->belongsToMany(Panel::class,'component_panel')
					->withPivot('id');
	}
}
