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
 * Class Componentcluster
 * 
 * @property int $id
 * @property string $name
 * @property string $code
 * @property string|null $description
 * @property string|null $icon
 * @property string|null $color
 * @property string|null $tooltip
 * @property int|null $order
 * @property string|null $badge
 * @property string|null $custom_css
 * @property int|null $parent_componentcluster_id
 * @property bool $is_inactive
 * @property string|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Componentcluster|null $componentcluster
 * @property Collection|Component[] $components
 * @property Collection|Componentcluster[] $componentclusters
 *
 * @package App\Models\Saas
 */
class Componentcluster extends Model
{
	use SoftDeletes;
	protected $table = 'componentclusters';

	protected $casts = [
		'order' => 'int',
		'parent_componentcluster_id' => 'int',
		'is_inactive' => 'bool'
	];

	protected $fillable = [
		'name',
		'code',
		'description',
		'icon',
		'color',
		'tooltip',
		'order',
		'badge',
		'custom_css',
		'parent_componentcluster_id',
		'is_inactive'
	];

	public function componentcluster()
	{
		return $this->belongsTo(Componentcluster::class, 'parent_componentcluster_id');
	}

	public function components()
	{
		return $this->belongsToMany(Component::class)
					->withPivot('id');
	}

	public function componentclusters()
	{
		return $this->hasMany(Componentcluster::class, 'parent_componentcluster_id');
	}
}
