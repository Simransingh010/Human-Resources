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
 * Class Module
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
 * @property Collection|App[] $apps
 * @property Collection|Component[] $components
 * @property Collection|ModuleclusterModule[] $modulecluster_modules
 *
 * @package App\Models\Saas
 */
class Module extends Model
{
	use SoftDeletes;
	protected $table = 'modules';

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

	public function apps()
	{
		return $this->belongsToMany(App::class)
					->withPivot('id');
	}

	public function components()
	{
		return $this->belongsToMany(Component::class)
					->withPivot('id');
	}

	public function modulecluster_modules()
	{
		return $this->hasMany(ModuleclusterModule::class);
	}
}
