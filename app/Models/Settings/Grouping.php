<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Settings;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Grouping
 * 
 * @property int $id
 * @property int $firm_id
 * @property string $model_name
 * @property string $group_name
 * @property int|null $parent_group_id
 * @property bool $is_inactive
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property Firm $firm
 * @property Grouping|null $grouping
 * @property Collection|Grouping[] $groupings
 * @property Collection|ModelsGrouping[] $models_groupings
 *
 * @package App\Models\Settings
 */
class Grouping extends Model
{
	use SoftDeletes;
	protected $table = 'groupings';

	protected $casts = [
		'firm_id' => 'int',
		'parent_group_id' => 'int',
		'is_inactive' => 'bool'
	];

	protected $fillable = [
		'firm_id',
		'model_name',
		'group_name',
		'parent_group_id',
		'is_inactive'
	];

	public function firm()
	{
		return $this->belongsTo(Firm::class);
	}

	public function grouping()
	{
		return $this->belongsTo(Grouping::class, 'parent_group_id');
	}

	public function groupings()
	{
		return $this->hasMany(Grouping::class, 'parent_group_id');
	}

	public function models_groupings()
	{
		return $this->hasMany(ModelsGrouping::class);
	}
}
