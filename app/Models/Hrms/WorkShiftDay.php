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
 * Class WorkShiftDay
 *
 * @property int $id
 * @property int $firm_id
 * @property int $work_shift_id
 * @property Carbon $work_date
 * @property string|null $day_status
 * @property Carbon|null $start_time
 * @property Carbon|null $end_time
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 *
 * @property Firm $firm
 * @property WorkShift $work_shift
 * @property Collection|EmpAttendance[] $emp_attendances
 * @property Collection|WorkShiftDaysBreak[] $work_shift_days_breaks
 *
 * @package App\Models\Hrms
 */
class WorkShiftDay extends Model
{
	use SoftDeletes;
	protected $table = 'work_shift_days';

	protected $casts = [
		'firm_id' => 'int',
		'work_shift_id' => 'int',
		'work_date' => 'datetime',
		'start_time' => 'datetime',
		'end_time' => 'datetime'
	];

	protected $fillable = [
		'firm_id',
		'work_shift_id',
		'work_date',
		'work_shift_day_status_id',
		'start_time',
		'end_time',
        'day_status_main',
        'paid_percent'
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

	public function emp_attendances()
	{
		return $this->hasMany(EmpAttendance::class);
	}

	public function work_shift_days_breaks()
	{
		return $this->hasMany(WorkShiftDaysBreak::class);
	}

	public function day_status()
	{
		return $this->belongsTo(WorkShiftDayStatus::class, 'work_shift_day_status_id');
	}
}
