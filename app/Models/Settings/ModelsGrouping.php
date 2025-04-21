<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Settings;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class ModelsGrouping
 * 
 * @property int $id
 * @property int $firm_id
 * @property string $model_name
 * @property int $model_id
 * @property int $grouping_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property Firm $firm
 * @property Grouping $grouping
 *
 * @package App\Models\Settings
 */
class ModelsGrouping extends Model
{
	use SoftDeletes;
	protected $table = 'models_groupings';

	protected $casts = [
		'firm_id' => 'int',
		'model_id' => 'int',
		'grouping_id' => 'int'
	];

	protected $fillable = [
		'firm_id',
		'model_name',
		'model_id',
		'grouping_id'
	];

	public function firm()
	{
		return $this->belongsTo(Firm::class);
	}

	public function grouping()
	{
		return $this->belongsTo(Grouping::class);
	}
}
