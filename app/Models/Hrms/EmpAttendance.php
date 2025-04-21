<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Hrms;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class EmpAttendance
 *
 * @property int $id
 * @property int $firm_id
 * @property int $employee_id
 * @property Carbon $work_date
 * @property int|null $work_shift_day_id
 * @property string|null $attend_status
 * @property string|null $attend_location
 * @property float $ideal_working_hours
 * @property float $actual_worked_hours
 * @property float $final_day_weightage
 * @property string|null $attend_remarks
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 *
 * @property Employee $employee
 * @property Firm $firm
 * @property WorkShiftDay|null $work_shift_day
 *
 * @package App\Models\Hrms
 */
class EmpAttendance extends Model
{
	use SoftDeletes;
	protected $table = 'emp_attendances';

	protected $casts = [
		'firm_id' => 'int',
		'employee_id' => 'int',
		'work_date' => 'datetime',
		'work_shift_day_id' => 'int',
		'ideal_working_hours' => 'float',
		'actual_worked_hours' => 'float',
		'final_day_weightage' => 'float'
	];

	protected $fillable = [
		'firm_id',
		'employee_id',
		'work_date',
		'work_shift_day_id',
		'attendance_status_main',
		'attend_location_id',
		'ideal_working_hours',
		'actual_worked_hours',
		'final_day_weightage',
		'attend_remarks'

	];

    public const ATTENDANCE_STATUS_MAIN_SELECT = [
        'P'   => 'Present',
        'A'   => 'Absent',
        'HD'  => 'Half Day',
        'L'   => 'Leave',
        'WFR' => 'Work from Remote',
        'OD'  => 'On Duty',
        'H'   => 'Holiday',
        'W'   => 'Weekend',
    ];
    public function getAttendanceStatusMainLabelAttribute($value)
    {
        return static::ATTENDANCE_STATUS_MAIN_SELECT[$this->attendance_status_main] ?? null;
    }
     protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

	public function employee()
	{
		return $this->belongsTo(Employee::class);
	}

	public function firm()
	{
		return $this->belongsTo(Firm::class);
	}

	public function work_shift_day()
	{
		return $this->belongsTo(WorkShiftDay::class);
	}
    public function punches()
    {
        return $this->hasMany(EmpPunch::class, 'emp_attendance_id', 'id');
    }
}
