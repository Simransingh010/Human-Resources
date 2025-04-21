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
 * Class WorkShift
 * 
 * @property int $id
 * @property int $firm_id
 * @property string $shift_title
 * @property string|null $shift_desc
 * @property Carbon|null $start_date
 * @property Carbon|null $end_date
 * @property bool $is_inactive
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property Firm $firm
 * @property Collection|EmpWorkShift[] $emp_work_shifts
 * @property Collection|WorkShiftDay[] $work_shift_days
 * @property Collection|WorkShiftsAlgo[] $work_shifts_algos
 *
 * @package App\Models\Hrms
 */
class WorkShift extends Model
{
	use SoftDeletes;
	protected $table = 'work_shifts';

	protected $casts = [
		'firm_id' => 'int',
		'start_date' => 'datetime',
		'end_date' => 'datetime',
		'is_inactive' => 'bool'
	];

	protected $fillable = [
		'firm_id',
		'shift_title',
		'shift_desc',
		'is_inactive'
	];

	public function firm()
	{
		return $this->belongsTo(Firm::class);
	}

	public function emp_work_shifts()
	{
		return $this->hasMany(EmpWorkShift::class);
	}

	public function work_shift_days()
	{
		return $this->hasMany(WorkShiftDay::class);
	}

	public function work_shifts_algos()
	{
		return $this->hasMany(WorkShiftsAlgo::class);
	}
}
