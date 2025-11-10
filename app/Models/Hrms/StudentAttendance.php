<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Hrms;

use App\Models\Settings\Joblocation;
use App\Models\Saas\Firm;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class StudentAttendance
 *
 * @property int $id
 * @property int $firm_id
 * @property int $student_id
 * @property int|null $study_centre_id
 * @property Carbon $attendance_date
 * @property string|null $attendance_status_main
 * @property float|null $duration_hours
 * @property int|null $location_id
 * @property string|null $remarks
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 *
 * @property Firm $firm
 * @property Student $student
 * @property StudyCentre|null $study_centre
 * @property Joblocation|null $location
 * @property Collection|StudentPunch[] $student_punches
 *
 * @package App\Models\Hrms
 */
class StudentAttendance extends Model
{
	use SoftDeletes;
	protected $table = 'student_attendances';

	protected $casts = [
		'firm_id' => 'int',
		'student_id' => 'int',
		'study_centre_id' => 'int',
		'attendance_date' => 'date',
		'duration_hours' => 'float',
		'location_id' => 'int'
	];

	protected $fillable = [
		'firm_id',
		'student_id',
		'study_centre_id',
		'attendance_date',
		'attendance_status_main',
		'duration_hours',
		'location_id',
		'remarks'
	];

	public const ATTENDANCE_STATUS_MAIN_SELECT = [
		'P'   => 'Present',
		'A'   => 'Absent',
		'HD'  => 'Half Day',
		'PW'  => 'Partial Working',
		'L'   => 'Leave',
		'WFR' => 'Work from Remote',
		'CW'  => 'Compensatory Work',
		'OD'  => 'On Duty',
		'H'   => 'Holiday',
		'W'   => 'Week Off',
		'S'   => 'Suspended',
		'POW' => 'Present on Week Off',
		'LM'  => 'Late Marked',
		'NM'  => 'Not Marked',
		'LWP' => 'Leave without Pay',
	];

	public function getAttendanceStatusMainLabelAttribute($value)
	{
		return static::ATTENDANCE_STATUS_MAIN_SELECT[$this->attendance_status_main] ?? null;
	}

	protected function serializeDate(\DateTimeInterface $date)
	{
		return $date->format('Y-m-d H:i:s');
	}

	public function firm()
	{
		return $this->belongsTo(Firm::class);
	}

	public function student()
	{
		return $this->belongsTo(Student::class);
	}

	public function study_centre()
	{
		return $this->belongsTo(StudyCentre::class);
	}

	public function location()
	{
		return $this->belongsTo(Joblocation::class);
	}

	public function student_punches()
	{
		return $this->hasMany(StudentPunch::class);
	}
}

