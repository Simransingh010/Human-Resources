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
 * Class EmpLeaveRequest
 * 
 * @property int $id
 * @property int $firm_id
 * @property int $employee_id
 * @property int $leave_type_id
 * @property Carbon $apply_from
 * @property Carbon $apply_to
 * @property int $apply_days
 * @property string|null $reason
 * @property string $status
 * @property string|null $time_from
 * @property string|null $time_to
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property Employee $employee
 * @property Firm $firm
 * @property LeaveType $leave_type
 * @property Collection|EmpLeaveRequestApproval[] $emp_leave_request_approvals
 * @property Collection|LeaveRequestEvent[] $leave_request_events
 *
 * @package App\Models\Hrms
 */
class EmpLeaveRequest extends Model
{
	use SoftDeletes;
	protected $table = 'emp_leave_requests';

	protected $casts = [
		'firm_id' => 'int',
		'employee_id' => 'int',
		'leave_type_id' => 'int',
		'apply_from' => 'datetime',
		'apply_to' => 'datetime',
		'apply_days' => 'decimal:2',
		'time_from' => 'datetime',
		'time_to' => 'datetime'
	];

    public const STATUS_SELECT = [
        'applied' => 'Applied',
        'reviewed' => 'Reviewed',
        'approved' => 'Approved',
        'approved_further' => 'Approved & Sent for Further Approval',
        'partially_approved' => 'Partially Approved',
        'rejected' => 'Rejected',
        'cancelled_employee' => 'Cancelled by Employee',
        'cancelled_hr' => 'Cancelled by HR/Admin',
        'modified' => 'Modified',
        'escalated' => 'Escalated',
        'delegated' => 'Delegated',
        'hold' => 'Hold',
        'expired' => 'Expired',
        'withdrawn' => 'Withdrawn',
        'auto_approved' => 'Auto-Approved'
    ];

	protected $fillable = [
		'firm_id',
		'employee_id',
		'leave_type_id',
		'apply_from',
		'apply_to',
		'apply_days',
		'reason',
		'status',
        'time_from',
        'time_to'
        //TIME FROM - TIME TO
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

	public function emp_leave_request_approvals()
	{
		return $this->hasMany(EmpLeaveRequestApproval::class);
	}

	public function leave_request_events()
	{
		return $this->hasMany(LeaveRequestEvent::class);
	}
}
