<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Hrms;

use App\Models\Saas\Firm;
use App\Models\Settings\Department;
use Carbon\Carbon;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class DepartmentLeaveApprovalRule
 * 
 * @property int $id
 * @property int $firm_id
 * @property int $rule_id
 * @property int $department_id
 * @property bool $is_inactive
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property Department $department
 * @property Firm $firm
 * @property LeaveApprovalRule $leave_approval_rule
 *
 * @package App\Models\Hrms
 */
class DepartmentLeaveApprovalRule extends Model
{
	use SoftDeletes;
	protected $table = 'department_leave_approval_rule';

	protected $casts = [
		'firm_id' => 'int',
		'rule_id' => 'int',
		'department_id' => 'int',
		'is_inactive' => 'bool'
	];

	protected $fillable = [
		'firm_id',
		'rule_id',
		'department_id',
		'is_inactive'
	];

	public function department()
	{
		return $this->belongsTo(Department::class);
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
