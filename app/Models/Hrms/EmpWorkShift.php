<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Hrms;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class EmpWorkShift
 * 
 * @property int $id
 * @property int $firm_id
 * @property int $work_shift_id
 * @property int $employee_id
 * @property Carbon|null $start_date
 * @property Carbon|null $end_date
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property Employee $employee
 * @property Firm $firm
 * @property WorkShift $work_shift
 *
 * @package App\Models\Hrms
 */
class EmpWorkShift extends Model
{
	use SoftDeletes;
	protected $table = 'emp_work_shifts';

	protected $casts = [
		'firm_id' => 'int',
		'work_shift_id' => 'in  t',
		'employee_id' => 'int',
		'start_date' => 'datetime',
		'end_date' => 'datetime'
	];

	protected $fillable = [
		'firm_id',
		'work_shift_id',
		'employee_id',
		'start_date',
		'end_date'
	];

	public function employee()
	{
		return $this->belongsTo(Employee::class);
	}

	public function firm()
	{
		return $this->belongsTo(Firm::class);
	}

	public function work_shift()
	{
		return $this->belongsTo(WorkShift::class);
	}
}
