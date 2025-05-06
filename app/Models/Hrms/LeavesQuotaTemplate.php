<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Hrms;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class LeavesQuotaTemplate
 *
 * @property int $id
 * @property int $firm_id
 * @property string $name
 * @property string|null $desc
 * @property bool $is_inactive
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 *
 * @property Firm $firm
 * @property Collection|LeavesQuotaTemplateSetup[] $leaves_quota_template_setups
 *
 * @package App\Models\Hrms
 */
class LeavesQuotaTemplate extends Model
{
	use SoftDeletes;
	protected $table = 'leaves_quota_templates';

	public const PERIOD_UNITS = [
		'day' => 'Day',
		'week' => 'Week',
		'month' => 'Month',
		'year' => 'Year'
	];

	protected $casts = [
		'firm_id' => 'int',
		'is_inactive' => 'bool'
	];

	protected $fillable = [
		'firm_id',
		'name',
		'desc',
		'alloc_period_unit',
		'alloc_period_value',
		'is_inactive'
	];

	public function firm()
	{
		return $this->belongsTo(Firm::class);
	}

	public function leaves_quota_template_setups()
	{
		return $this->hasMany(LeavesQuotaTemplateSetup::class);
	}
}
