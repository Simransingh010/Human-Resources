<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Hrms;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class OnboardMeta
 * 
 * @property int $id
 * @property int $firm_id
 * @property string $model_name
 * @property int $model_id
 * @property string $meta_key
 * @property string|null $meta_value
 * @property string $meta_type
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property Firm $firm
 *
 * @package App\Models\Hrms
 */
class OnboardMeta extends Model
{
	use SoftDeletes;
	protected $table = 'onboard_metas';

	protected $casts = [
		'firm_id' => 'int',
		'model_id' => 'int'
	];

	protected $fillable = [
		'firm_id',
		'model_name',
		'model_id',
		'meta_key',
		'meta_value',
		'meta_type'
	];

	public function firm()
	{
		return $this->belongsTo(Firm::class);
	}
}
