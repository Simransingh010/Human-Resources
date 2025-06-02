<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Hrms;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class PayrollComponentsEmployeesTrack
 *
 * @property int $id
 * @property int $firm_id
 * @property int $payroll_slot_id
 * @property int $payroll_slots_cmd_id
 * @property int $employee_id
 * @property int $salary_template_id
 * @property int $salary_component_group_id
 * @property int $salary_component_id
 * @property int $sequence
 * @property string $nature
 * @property string $component_type
 * @property string $amount_type
 * @property bool $taxable
 * @property array|null $calculation_json
 * @property Carbon $salary_period_from
 * @property Carbon $salary_period_to
 * @property int|null $user_id
 * @property float $amount_full
 * @property float $amount_payable
 * @property float $amount_paid
 * @property int|null $salary_advance_id
 * @property int|null $salary_arrear_id
 * @property int|null $salary_cycle_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property SalaryAdvance|null $salary_advance
 * @property SalaryArrear|null $salary_arrear
 * @property SalaryComponentGroup $salary_component_group
 * @property SalaryComponent $salary_component
 * @property SalaryCycle|null $salary_cycle
 * @property Employee $employee
 * @property Firm $firm
 * @property PayrollSlotsCmd $payroll_slots_cmd
 * @property PayrollSlot $payroll_slot
 * @property SalaryTemplate $salary_template
 * @property User|null $user
 * @property Collection|EmployeePaidSalaryComponent[] $employee_paid_salary_components
 *
 * @package App\Models\Hrms
 */
class PayrollComponentsEmployeesTrack extends Model
{
	protected $table = 'payroll_components_employees_tracks';

	protected $casts = [
		'firm_id' => 'int',
		'payroll_slot_id' => 'int',
		'payroll_slots_cmd_id' => 'int',
		'employee_id' => 'int',
		'salary_template_id' => 'int',
		'salary_component_group_id' => 'int',
		'salary_component_id' => 'int',
		'sequence' => 'int',
		'taxable' => 'bool',
		'calculation_json' => 'json',
		'salary_period_from' => 'datetime',
		'salary_period_to' => 'datetime',
		'user_id' => 'int',
		'amount_full' => 'float',
		'amount_payable' => 'float',
		'amount_paid' => 'float',
		'salary_advance_id' => 'int',
		'salary_arrear_id' => 'int',
		'salary_cycle_id' => 'int'
	];

	protected $fillable = [
		'firm_id',
		'payroll_slot_id',
		'payroll_slots_cmd_id',
		'employee_id',
		'salary_template_id',
		'salary_component_group_id',
		'salary_component_id',
		'sequence',
		'nature',
		'component_type',
		'amount_type',
		'taxable',
		'calculation_json',
		'salary_period_from',
		'salary_period_to',
		'user_id',
		'amount_full',
		'amount_payable',
		'amount_paid',
		'salary_advance_id',
		'salary_arrear_id',
		'salary_cycle_id',
        'remarks',
        'entry_type'
	];

	public function salary_advance()
	{
		return $this->belongsTo(SalaryAdvance::class);
	}

	public function salary_arrear()
	{
		return $this->belongsTo(SalaryArrear::class);
	}

	public function salary_component_group()
	{
		return $this->belongsTo(SalaryComponentGroup::class);
	}

	public function salary_component()
	{
		return $this->belongsTo(SalaryComponent::class);
	}

	public function salary_cycle()
	{
		return $this->belongsTo(SalaryCycle::class);
	}

	public function employee()
	{
		return $this->belongsTo(Employee::class);
	}

	public function firm()
	{
		return $this->belongsTo(Firm::class);
	}

	public function payroll_slots_cmd()
	{
		return $this->belongsTo(PayrollSlotsCmd::class);
	}

	public function payroll_slot()
	{
		return $this->belongsTo(PayrollSlot::class);
	}

	public function salary_template()
	{
		return $this->belongsTo(SalaryTemplate::class);
	}

	public function user()
	{
		return $this->belongsTo(User::class);
	}

	public function employee_paid_salary_components()
	{
		return $this->hasMany(EmployeePaidSalaryComponent::class);
	}
}
