<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Hrms;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class AttendanceViolationLog
 * 
 * @property int $id
 * @property int $firm_id
 * @property int $employee_id
 * @property Carbon $work_date
 * @property Carbon $punch_datetime
 * @property string $violation_type
 * @property string|null $allowed_value
 * @property string|null $actual_value
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property Employee $employee
 * @property Firm $firm
 *
 * @package App\Models\Hrms
 */
class AttendanceViolationLog extends Model
{
	use SoftDeletes;
	protected $table = 'attendance_violation_logs';

	protected $casts = [
		'firm_id' => 'int',
		'employee_id' => 'int',
		'work_date' => 'datetime',
		'punch_datetime' => 'datetime'
	];

	protected $fillable = [
		'firm_id',
		'employee_id',
		'work_date',
		'punch_datetime',
		'violation_type',
		'allowed_value',
		'actual_value'
	];

	public function employee()
	{
		return $this->belongsTo(Employee::class);
	}

	public function firm()
	{
		return $this->belongsTo(Firm::class);
	}
}
