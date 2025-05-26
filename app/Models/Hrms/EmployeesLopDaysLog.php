<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Hrms;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class EmployeesLopDaysLog
 * 
 * @property int $id
 * @property int $firm_id
 * @property int $payroll_slot_id
 * @property int $payroll_step_payroll_slot_id
 * @property int $lop_days_count
 * @property string $creation_mode
 * @property string|null $creation_remarks
 * @property int|null $user_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Firm $firm
 * @property PayrollStepPayrollSlot $payroll_step_payroll_slot
 * @property PayrollSlot $payroll_slot
 * @property User|null $user
 *
 * @package App\Models\Hrms
 */
class EmployeesLopDaysLog extends Model
{
	protected $table = 'employees_lop_days_logs';

	protected $casts = [
		'firm_id' => 'int',
		'payroll_slot_id' => 'int',
		'payroll_step_payroll_slot_id' => 'int',
		'lop_days_count' => 'int',
		'user_id' => 'int'
	];

	protected $fillable = [
		'firm_id',
		'payroll_slot_id',
		'payroll_step_payroll_slot_id',
		'lop_days_count',
		'creation_mode',
		'creation_remarks',
		'user_id'
	];

	public function firm()
	{
		return $this->belongsTo(Firm::class);
	}

	public function payroll_step_payroll_slot()
	{
		return $this->belongsTo(PayrollStepPayrollSlot::class);
	}

	public function payroll_slot()
	{
		return $this->belongsTo(PayrollSlot::class);
	}

	public function user()
	{
		return $this->belongsTo(User::class);
	}
}
