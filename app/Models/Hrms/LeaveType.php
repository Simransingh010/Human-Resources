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
 * Class LeaveType
 * 
 * @property int $id
 * @property int $firm_id
 * @property string $leave_title
 * @property string|null $leave_desc
 * @property string|null $leave_code
 * @property string|null $leave_nature
 * @property int|null $max_days
 * @property bool $carry_forward
 * @property bool $encashable
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property bool $is_inactive
 * @property string|null $leave_type_main
 * 
 * @property Firm $firm
 * @property Collection|EmpLeaveBalance[] $emp_leave_balances
 * @property Collection|EmpLeaveRequest[] $emp_leave_requests
 * @property Collection|LeaveApprovalRule[] $leave_approval_rules
 * @property Collection|LeavesQuotaTemplateSetup[] $leaves_quota_template_setups
 *
 * @package App\Models\Hrms
 */
class LeaveType extends Model
{
	use SoftDeletes;
	protected $table = 'leave_types';

	protected $casts = [
		'firm_id' => 'int',
		'max_days' => 'int',
		'carry_forward' => 'bool',
		'encashable' => 'bool',
		'is_inactive' => 'bool',
		'requires_document' => 'bool'
	];

	protected $fillable = [
		'firm_id',
		'leave_title',
		'leave_desc',
		'leave_code',
		'leave_nature',
		'leave_type_main',
		'max_days',
		'carry_forward',
		'encashable',
		'is_inactive',
		'requires_document'
	];

	public function firm()
	{
		return $this->belongsTo(Firm::class);
	}

	public function emp_leave_balances()
	{
		return $this->hasMany(EmpLeaveBalance::class);
	}

	public function emp_leave_requests()
	{
		return $this->hasMany(EmpLeaveRequest::class);
	}

	public function leave_approval_rules()
	{
		return $this->hasMany(LeaveApprovalRule::class);
	}

	public function leaves_quota_template_setups()
	{
		return $this->hasMany(LeavesQuotaTemplateSetup::class);
	}

	public const LEAVE_NATURE_OPTIONS = [
		'paid' => 'Paid',
		'unpaid' => 'Unpaid',
	];

	public const LEAVE_TYPE_MAIN_OPTIONS = [
		'regular' => 'Regular',
		'weekoff' => 'Weekoff',
		'earn' => 'Earned',
		
	];
}
