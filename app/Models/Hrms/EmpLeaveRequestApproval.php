<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Hrms;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class EmpLeaveRequestApproval
 * 
 * @property int $id
 * @property int $emp_leave_request_id
 * @property int $approval_level
 * @property int $approver_id
 * @property string $status
 * @property string|null $remarks
 * @property Carbon|null $acted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property int $firm_id
 * 
 * @property User $user
 * @property EmpLeaveRequest $emp_leave_request
 * @property Firm $firm
 *
 * @package App\Models\Hrms
 */
class EmpLeaveRequestApproval extends Model
{
	use SoftDeletes;
	protected $table = 'emp_leave_request_approvals';

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
        'auto_approved' => 'Auto-Approved',
        'clarification_required' => 'Clarification Required'
    ];

	protected $casts = [
		'emp_leave_request_id' => 'int',
		'approval_level' => 'int',
		'approver_id' => 'int',
		'acted_at' => 'datetime',
		'firm_id' => 'int'
	];

	protected $fillable = [
		'emp_leave_request_id',
		'approval_level',
		'approver_id',
		'status',
		'remarks',
		'acted_at',
		'firm_id'
	];

	public function user()
	{
		return $this->belongsTo(User::class, 'approver_id');
	}

	public function approver()
	{
		return $this->belongsTo(User::class, 'approver_id');
	}

	public function emp_leave_request()
	{
		return $this->belongsTo(EmpLeaveRequest::class);
	}

	public function firm()
	{
		return $this->belongsTo(Firm::class);
	}
}
