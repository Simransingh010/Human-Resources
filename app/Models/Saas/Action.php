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
 * Class Action
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
 * @property int $component_id
 * @property int|null $actioncluster_id
 * @property bool $is_inactive
 * @property string|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property Actioncluster|null $actioncluster
 * @property Component $component
 * @property Collection|Role[] $roles
 * @property Collection|User[] $users
 *
 * @package App\Models\Saas
 */
class Action extends Model
{
	use SoftDeletes;
	protected $table = 'actions';

	protected $casts = [
		'order' => 'int',
		'component_id' => 'int',
		'actioncluster_id' => 'int',
		'is_inactive' => 'bool'
	];

	protected $fillable = [
		'name',
		'code',
        'wire',
		'description',
		'icon',
		'color',
		'tooltip',
		'order',
		'badge',
		'custom_css',
		'component_id',
		'actioncluster_id',
		'is_inactive'
	];

	public function actioncluster()
	{
		return $this->belongsTo(Actioncluster::class);
	}

	public function component()
	{
		return $this->belongsTo(Component::class);
	}

	public function roles()
	{
		return $this->belongsToMany(Role::class)
					->withPivot('id', 'firm_id', 'records_scope');
	}

	public function users()
	{
		return $this->belongsToMany(User::class)
					->withPivot('id', 'firm_id', 'records_scope');
	}
}
