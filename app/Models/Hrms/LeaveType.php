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

	// Define constants for leave natures
	const NATURE_SICK = 'Sick';
	const NATURE_CASUAL = 'Casual';
	const NATURE_ANNUAL = 'Annual';
	const NATURE_MATERNITY = 'Maternity';
	const NATURE_PATERNITY = 'Paternity';
	const NATURE_STUDY = 'Study';
	const NATURE_UNPAID = 'Unpaid';
	const NATURE_COMPENSATORY = 'Compensatory';
	const NATURE_BEREAVEMENT = 'Bereavement';

	// Array of all valid leave natures
	public static $validNatures = [
		self::NATURE_SICK,
		self::NATURE_CASUAL,
		self::NATURE_ANNUAL,
		self::NATURE_MATERNITY,
		self::NATURE_PATERNITY,
		self::NATURE_STUDY,
		self::NATURE_UNPAID,
		self::NATURE_COMPENSATORY,
		self::NATURE_BEREAVEMENT,
	];

	protected $casts = [
		'firm_id' => 'int',
		'max_days' => 'int',
		'carry_forward' => 'bool',
		'encashable' => 'bool',
		'is_inactive' => 'bool'
	];

	protected $fillable = [
		'firm_id',
		'leave_title',
		'leave_desc',
		'leave_code',
		'leave_nature',
		'max_days',
		'carry_forward',
		'encashable',
		'is_inactive'
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
}
