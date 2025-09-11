<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Hrms;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class WorkShiftsAlgo
 * 
 * @property int $id
 * @property int $firm_id
 * @property int $work_shift_id
 * @property string|null $week_off_pattern
 * @property int|null $holiday_calendar_id
 * @property bool $allow_wfh
 * @property string|null $half_day_rule
 * @property string|null $overtime_rule
 * @property string|null $rules_config
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property Firm $firm
 * @property WorkShift $work_shift
 *
 * @package App\Models\Hrms
 */
class WorkShiftsAlgo extends Model
{
	use SoftDeletes;
	protected $table = 'work_shifts_algos';

	protected $casts = [
		'firm_id' => 'int',
		'work_shift_id' => 'int',
		'holiday_calendar_id' => 'int',
		'allow_wfh' => 'bool',
		'is_active' => 'bool'
	];

    protected $fillable = [
        'firm_id',
        'work_shift_id',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'week_off_pattern',
        'work_breaks',
        'holiday_calendar_id',
        'allow_wfh',
        'half_day_rule',
        'overtime_rule',
        'rules_config',
        'late_panelty',
        'comp_off',
        'is_inactive',
        'is_overnight'
    ];

	public function firm()
	{
		return $this->belongsTo(Firm::class);
	}

	public function work_shift()
	{
		return $this->belongsTo(WorkShift::class);
	}
}
