<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Hrms;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class EmployeeLeaveApprovalRule
 * 
 * @property int $id
 * @property int $firm_id
 * @property int $rule_id
 * @property int $employee_id
 * @property bool $is_inactive
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property Employee $employee
 * @property Firm $firm
 * @property LeaveApprovalRule $leave_approval_rule
 *
 * @package App\Models\Hrms
 */
class EmployeeLeaveApprovalRule extends Model
{
	use SoftDeletes;
	protected $table = 'employee_leave_approval_rule';

	protected $casts = [
		'firm_id' => 'int',
		'rule_id' => 'int',
		'employee_id' => 'int',
		'is_inactive' => 'bool'
	];

	protected $fillable = [
		'firm_id',
		'rule_id',
		'employee_id',
		'is_inactive'
	];

	public function employee()
	{
		return $this->belongsTo(Employee::class);
	}

	public function firm()
	{
		return $this->belongsTo(Firm::class);
	}

	public function leave_approval_rule()
	{
		return $this->belongsTo(LeaveApprovalRule::class, 'rule_id');
	}
}
