<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Hrms;

use App\Models\Saas\Firm;
use App\Models\Settings\Department;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class LeaveApprovalRule
 * 
 * @property int $id
 * @property int $firm_id
 * @property Carbon $period_start
 * @property Carbon $period_end
 * @property int|null $leave_type_id
 * @property string|null $department_scope
 * @property string|null $employee_scope
 * @property int|null $approval_level
 * @property string|null $approval_mode
 * @property bool $auto_approve
 * @property int|null $approver_id
 * @property float|null $min_days
 * @property float|null $max_days
 * @property bool $is_inactive
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property User $user
 * @property Firm $firm
 * @property LeaveType|null $leave_type
 * @property Collection|Department[] $departments
 * @property Collection|Employee[] $employees
 *
 * @package App\Models\Hrms
 */
class LeaveApprovalRule extends Model
{
	use SoftDeletes;
	protected $table = 'leave_approval_rules';

	protected $casts = [
		'firm_id' => 'int',
		'period_start' => 'datetime',
		'period_end' => 'datetime',
		'leave_type_id' => 'int',
		'approval_level' => 'int',
		'auto_approve' => 'bool',
		'approver_id' => 'int',
		'min_days' => 'float',
		'max_days' => 'float',
		'is_inactive' => 'bool'
	];

	protected $fillable = [
		'firm_id',
		'period_start',
		'period_end',
		'leave_type_id',
		'department_scope',
		'employee_scope',
		'approval_level',
		'approval_mode',
		'auto_approve',
		'approver_id',
		'min_days',
		'max_days',
		'is_inactive'
	];

	public function user()
	{
		return $this->belongsTo(User::class, 'approver_id');
	}

	public function firm()
	{
		return $this->belongsTo(Firm::class);
	}

	public function leave_type()
	{
		return $this->belongsTo(LeaveType::class);
	}

	public function departments()
	{
		return $this->belongsToMany(Department::class, 'department_leave_approval_rule', 'rule_id')
			->withPivot('id', 'firm_id', 'is_inactive', 'deleted_at')
			->withTimestamps();
	}

	public function employees()
	{
		return $this->belongsToMany(Employee::class, 'employee_leave_approval_rule', 'rule_id')
			->withPivot('id', 'firm_id', 'is_inactive', 'deleted_at')
			->withTimestamps();
	}
}
