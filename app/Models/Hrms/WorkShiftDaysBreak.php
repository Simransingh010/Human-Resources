<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Hrms;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class WorkShiftDaysBreak
 * 
 * @property int $id
 * @property int $firm_id
 * @property int $work_shift_day_id
 * @property int $work_break_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property Firm $firm
 * @property WorkBreak $work_break
 * @property WorkShiftDay $work_shift_day
 *
 * @package App\Models\Hrms
 */
class WorkShiftDaysBreak extends Model
{
	use SoftDeletes;
	protected $table = 'work_shift_days_breaks';

	protected $casts = [
		'firm_id' => 'int',
		'work_shift_day_id' => 'int',
		'work_break_id' => 'int'
	];

	protected $fillable = [
		'firm_id',
		'work_shift_day_id',
		'work_break_id'
	];

	public function firm()
	{
		return $this->belongsTo(Firm::class);
	}

	public function work_break()
	{
		return $this->belongsTo(WorkBreak::class);
	}

	public function work_shift_day()
	{
		return $this->belongsTo(WorkShiftDay::class);
	}
}
