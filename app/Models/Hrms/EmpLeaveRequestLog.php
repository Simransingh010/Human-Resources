<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Hrms;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class EmpLeaveRequestLog
 * 
 * @property int $id
 * @property int $firm_id
 * @property int $emp_leave_request_id
 * @property Carbon|null $status_datetime
 * @property string|null $remarks
 * @property string|null $status
 * @property int|null $action_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property EmpLeaveRequest $emp_leave_request
 * @property Employee $action_by_user
 * @property Firm $firm
 *
 * @package App\Models\Hrms
 */
class EmpLeaveRequestLog extends Model
{
	use SoftDeletes;
	protected $table = 'emp_leave_request_logs';

	protected $casts = [
		'firm_id' => 'int',
		'emp_leave_request_id' => 'int',
		'status_datetime' => 'datetime',
		'action_by' => 'int'
	];

	protected $fillable = [
		'firm_id',
		'emp_leave_request_id',
		'status_datetime',
		'remarks',
		'status',
		'action_by'
	];

	public function emp_leave_request()
	{
		return $this->belongsTo(EmpLeaveRequest::class);
	}

	public function action_by_user()
	{
		return $this->belongsTo(Employee::class, 'action_by', 'id');
	}

	public function firm()
	{
		return $this->belongsTo(Firm::class);
	}
}
