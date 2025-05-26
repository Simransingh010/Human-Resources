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
 * Class WorkShiftDayStatus
 *
 * @property int $id
 * @property int $firm_id
 * @property int $work_shift_id
 * @property string $day_status_code
 * @property string $day_status_label
 * @property string|null $day_status_desc
 * @property float $paid_percent
 * @property bool $count_as_working_day
 * @property bool $is_inactive
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 *
 * @property Firm $firm
 * @property WorkShift $work_shift
 * @property Collection|WorkShiftDay[] $work_shift_days
 *
 * @package App\Models\Hrms
 */
class WorkShiftDayStatus extends Model
{
	use SoftDeletes;
	protected $table = 'work_shift_day_statuses';

	protected $casts = [
		'firm_id' => 'int',

		'paid_percent' => 'float',
		'count_as_working_day' => 'bool',
		'is_inactive' => 'bool'
	];

	protected $fillable = [
		'firm_id',

		'day_status_code',
		'day_status_label',
		'day_status_desc',
		'paid_percent',
		'count_as_working_day',
		'is_inactive',
        'day_status_main'
	];


    public const WORK_STATUS_SELECT = [
        'F' => 'Full Working',
        'H' => 'Holiday',
        'W' => 'Week Off',
        'PW' => 'Partial Working',
        'S' => 'Suspended',
    ];
	public function firm()
	{
		return $this->belongsTo(Firm::class);
	}

	public function work_shift()
	{
		return $this->belongsTo(WorkShift::class);
	}

	public function work_shift_days()
	{
		return $this->hasMany(WorkShiftDay::class);
	}
}
