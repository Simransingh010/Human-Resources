<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Hrms;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class LeaveRequestEvent
 * 
 * @property int $id
 * @property int $emp_leave_request_id
 * @property int|null $user_id
 * @property string $event_type
 * @property string|null $from_status
 * @property string|null $to_status
 * @property string|null $remarks
 * @property Carbon $created_at
 * @property string|null $deleted_at
 * @property int $firm_id
 * 
 * @property EmpLeaveRequest $emp_leave_request
 * @property Firm $firm
 * @property User|null $user
 *
 * @package App\Models\Hrms
 */
class LeaveRequestEvent extends Model
{
	use SoftDeletes;
	protected $table = 'leave_request_events';
	public $timestamps = false;

	protected $casts = [
		'emp_leave_request_id' => 'int',
		'user_id' => 'int',
		'firm_id' => 'int'
	];

	protected $fillable = [
		'emp_leave_request_id',
		'user_id',
		'event_type',
		'from_status',
		'to_status',
		'remarks',
		'firm_id'
	];

	public function emp_leave_request()
	{
		return $this->belongsTo(EmpLeaveRequest::class);
	}

	public function firm()
	{
		return $this->belongsTo(Firm::class);
	}

	public function user()
	{
		return $this->belongsTo(User::class);
	}
}
