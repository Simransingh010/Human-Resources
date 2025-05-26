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
 * Class PayrollStepPayrollSlot
 * 
 * @property int $id
 * @property int $firm_id
 * @property int $payroll_slot_id
 * @property string $step_code_main
 * @property int $payroll_step_id
 * @property string $payroll_step_status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property Firm $firm
 * @property PayrollSlot $payroll_slot
 * @property PayrollStep $payroll_step
 * @property Collection|EmployeesLopDaysLog[] $employees_lop_days_logs
 * @property Collection|PayrollStepPayrollSlotCmd[] $payroll_step_payroll_slot_cmds
 *
 * @package App\Models\Hrms
 */
class PayrollStepPayrollSlot extends Model
{
	use SoftDeletes;
	protected $table = 'payroll_step_payroll_slot';

	protected $casts = [
		'firm_id' => 'int',
		'payroll_slot_id' => 'int',
		'payroll_step_id' => 'int'
	];

	protected $fillable = [
		'firm_id',
		'payroll_slot_id',
		'step_code_main',
		'payroll_step_id',
		'payroll_step_status'
	];

	public function firm()
	{
		return $this->belongsTo(Firm::class);
	}

	public function payroll_slot()
	{
		return $this->belongsTo(PayrollSlot::class);
	}

	public function payroll_step()
	{
		return $this->belongsTo(PayrollStep::class);
	}

	public function employees_lop_days_logs()
	{
		return $this->hasMany(EmployeesLopDaysLog::class);
	}

	public function payroll_step_payroll_slot_cmds()
	{
		return $this->hasMany(PayrollStepPayrollSlotCmd::class);
	}
}
