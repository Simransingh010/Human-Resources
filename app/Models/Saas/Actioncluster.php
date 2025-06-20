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
 * Class Actioncluster
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
 * @property int|null $parent_actioncluster_id
 * @property bool $is_inactive
 * @property string|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Actioncluster|null $actioncluster
 * @property Collection|Actioncluster[] $actionclusters
 * @property Collection|Action[] $actions
 *
 * @package App\Models\Saas
 */
class Actioncluster extends Model
{
	use SoftDeletes;
	protected $table = 'actionclusters';

	protected $casts = [
		'order' => 'int',
		'parent_actioncluster_id' => 'int',
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
		'parent_actioncluster_id',
		'is_inactive'
	];

	public function actioncluster()
	{
		return $this->belongsTo(Actioncluster::class, 'parent_actioncluster_id');
	}

	public function actionclusters()
	{
		return $this->hasMany(Actioncluster::class, 'parent_actioncluster_id');
	}

	public function actions()
	{
		return $this->hasMany(Action::class);
	}
}
