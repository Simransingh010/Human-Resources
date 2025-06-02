<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Hrms;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class EmpLeaveAllocation
 * 
 * @property int $id
 * @property int $firm_id
 * @property int $employee_id
 * @property int|null $leaves_quota_template_id
 * @property int $leave_type_id
 * @property int $days_assigned
 * @property Carbon|null $start_date
 * @property Carbon|null $end_date
 * @property int $days_balance
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property Employee $employee
 * @property Firm $firm
 * @property LeaveType $leave_type
 * @property LeavesQuotaTemplate|null $leaves_quota_template
 *
 * @package App\Models\Hrms
 */
class EmpLeaveAllocation extends Model
{
	use SoftDeletes;
	protected $table = 'emp_leave_allocations';

	protected $casts = [
		'firm_id' => 'int',
		'employee_id' => 'int',
		'leaves_quota_template_id' => 'int',
		'leave_type_id' => 'int',
		'days_assigned' => 'int',
		'start_date' => 'datetime',
		'end_date' => 'datetime',
		'days_balance' => 'int'
	];

	protected $fillable = [
		'firm_id',
		'employee_id',
		'leaves_quota_template_id',
		'leave_type_id',
		'days_assigned',
		'start_date',
		'end_date',
		'days_balance'
	];

	public function employee()
	{
		return $this->belongsTo(Employee::class);
	}

	public function firm()
	{
		return $this->belongsTo(Firm::class);
	}

	public function leave_type()
	{
		return $this->belongsTo(LeaveType::class);
	}

	public function leaves_quota_template()
	{
		return $this->belongsTo(LeavesQuotaTemplate::class);
	}
}
