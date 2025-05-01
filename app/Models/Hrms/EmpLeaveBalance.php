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
 * Class EmpLeaveBalance
 * 
 * @property int $id
 * @property int $firm_id
 * @property int $employee_id
 * @property int $leave_type_id
 * @property Carbon $period_start
 * @property Carbon $period_end
 * @property float $allocated_days
 * @property float $consumed_days
 * @property float $carry_forwarded_days
 * @property float $lapsed_days
 * @property float $balance
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property Employee $employee
 * @property Firm $firm
 * @property LeaveType $leave_type
 * @property Collection|EmpLeaveTransaction[] $emp_leave_transactions
 *
 * @package App\Models\Hrms
 */
class EmpLeaveBalance extends Model
{
	use SoftDeletes;
	protected $table = 'emp_leave_balance';

	protected $casts = [
		'firm_id' => 'int',
		'employee_id' => 'int',
		'leave_type_id' => 'int',
		'period_start' => 'datetime',
		'period_end' => 'datetime',
		'allocated_days' => 'float',
		'consumed_days' => 'float',
		'carry_forwarded_days' => 'float',
		'lapsed_days' => 'float',
		'balance' => 'float'
	];

	protected $fillable = [
		'firm_id',
		'employee_id',
		'leave_type_id',
		'period_start',
		'period_end',
		'allocated_days',
		'consumed_days',
		'carry_forwarded_days',
		'lapsed_days',
		'balance'
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

	public function emp_leave_transactions()
	{
		return $this->hasMany(EmpLeaveTransaction::class, 'leave_balance_id');
	}
}
