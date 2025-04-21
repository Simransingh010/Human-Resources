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
 * Class Modulecluster
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
 * @property int|null $parent_modulecluster_id
 * @property bool $is_inactive
 * @property string|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Modulecluster|null $modulecluster
 * @property Collection|Module[] $modules
 * @property Collection|Modulecluster[] $moduleclusters
 *
 * @package App\Models\Saas
 */
class Modulecluster extends Model
{
	use SoftDeletes;
	protected $table = 'moduleclusters';

	protected $casts = [
		'order' => 'int',
		'parent_modulecluster_id' => 'int',
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
		'parent_modulecluster_id',
		'is_inactive'
	];

	public function modulecluster()
	{
		return $this->belongsTo(Modulecluster::class, 'parent_modulecluster_id');
	}

	public function modules()
	{
		return $this->belongsToMany(Module::class, 'modulecluster_module')
					->withPivot('id');
	}

	public function moduleclusters()
	{
		return $this->hasMany(Modulecluster::class, 'parent_modulecluster_id');
	}
}
