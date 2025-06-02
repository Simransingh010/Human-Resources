<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Hrms;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class LeavesQuotaTemplateSetup
 * 
 * @property int $id
 * @property int $firm_id
 * @property int $leaves_quota_template_id
 * @property int $leave_type_id
 * @property int $days_assigned
 * @property string|null $alloc_period_unit
 * @property int|null $alloc_period_value
 * @property bool $is_inactive
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property LeavesQuotaTemplate $leaves_quota_template
 * @property LeaveType $leave_type
 * @property Firm $firm
 *
 * @package App\Models\Hrms
 */
class LeavesQuotaTemplateSetup extends Model
{
	use SoftDeletes;
	protected $table = 'leaves_quota_template_setups';

	public const ALLOC_PERIOD_UNITS = [
		'day' => 'Day',
		'week' => 'Week',
		'month' => 'Month',
		'year' => 'Year'
	];

	protected $casts = [
		'firm_id' => 'int',
		'leaves_quota_template_id' => 'int',
		'leave_type_id' => 'int',
		'days_assigned' => 'int',
		'alloc_period_value' => 'int',
		'is_inactive' => 'bool'
	];

	protected $fillable = [
		'firm_id',
		'leaves_quota_template_id',
		'leave_type_id',
		'days_assigned',
		'alloc_period_unit',
		'alloc_period_value',
		'is_inactive'
	];

	public function leaves_quota_template()
	{
		return $this->belongsTo(LeavesQuotaTemplate::class);
	}

	public function leave_type()
	{
		return $this->belongsTo(LeaveType::class);
	}

	public function firm()
	{
		return $this->belongsTo(Firm::class);
	}
}
