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
 * Class SalaryAdvance
 * 
 * @property int $id
 * @property int $firm_id
 * @property int $employee_id
 * @property Carbon $advance_date
 * @property float $amount
 * @property int $installments
 * @property float $installment_amount
 * @property float $recovered_amount
 * @property string $advance_status
 * @property bool $is_inactive
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property string|null $disburse_salary_component
 * @property string|null $recovery_salary_component
 * @property int|null $disburse_payroll_slot_id
 * @property int|null $recovery_wef_payroll_slot_id
 * @property string|null $additional_rule_remarks
 * 
 * @property Employee $employee
 * @property Firm $firm
 * @property PayrollSlot|null $disbursePayrollSlot
 * @property PayrollSlot|null $recoveryWefPayrollSlot
 * @property Collection|PayrollComponentsEmployeesTrack[] $payroll_components_employees_tracks
 *
 * @package App\Models\Hrms
 */
class SalaryAdvance extends Model
{
	use SoftDeletes;
	protected $table = 'salary_advances';

	/**
	 * Available advance statuses
	 */
	public static $advanceStatuses = [
		'pending' => 'Pending',
		'active' => 'Active',
        'closed' => 'Closed',
	];

	protected $casts = [
		'firm_id' => 'int',
		'employee_id' => 'int',
		'advance_date' => 'datetime',
		'amount' => 'float',
		'installments' => 'int',
		'installment_amount' => 'float',
		'recovered_amount' => 'float',
		'is_inactive' => 'bool',
		'disburse_payroll_slot_id' => 'int',
		'recovery_wef_payroll_slot_id' => 'int'
	];

	protected $fillable = [
		'firm_id',
		'employee_id',
		'advance_date',
		'amount',
		'installments',
		'installment_amount',
		'recovered_amount',
		'advance_status',
		'is_inactive',
		'disburse_salary_component',
		'recovery_salary_component',
		'disburse_payroll_slot_id',
		'recovery_wef_payroll_slot_id',
		'additional_rule_remarks'
	];

	public function employee()
	{
		return $this->belongsTo(Employee::class);
	}

	public function firm()
	{
		return $this->belongsTo(Firm::class);
	}

	public function payroll_components_employees_tracks()
	{
		return $this->hasMany(PayrollComponentsEmployeesTrack::class);
	}

	public function disbursePayrollSlot()
	{
		return $this->belongsTo(PayrollSlot::class, 'disburse_payroll_slot_id');
	}

	public function recoveryWefPayrollSlot()
	{
		return $this->belongsTo(PayrollSlot::class, 'recovery_wef_payroll_slot_id');
	}
}
