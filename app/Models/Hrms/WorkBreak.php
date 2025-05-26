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
 * Class WorkBreak
 * 
 * @property int $id
 * @property int $firm_id
 * @property string $break_title
 * @property string|null $break_desc
 * @property Carbon|null $start_time
 * @property Carbon|null $end_time
 * @property bool $is_inactive
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property Firm $firm
 * @property Collection|WorkShiftDaysBreak[] $work_shift_days_breaks
 *
 * @package App\Models\Hrms
 */
class WorkBreak extends Model
{
	use SoftDeletes;
	protected $table = 'work_breaks';

	protected $casts = [
		'firm_id' => 'int',
		'start_time' => 'datetime',
		'end_time' => 'datetime',
		'is_inactive' => 'bool'
	];

	protected $fillable = [
		'firm_id',
		'break_title',
		'break_desc',
		'start_time',
		'end_time',
		'is_inactive'
	];

	public function firm()
	{
		return $this->belongsTo(Firm::class);
	}

	public function work_shift_days_breaks()
	{
		return $this->hasMany(WorkShiftDaysBreak::class);
	}
}
