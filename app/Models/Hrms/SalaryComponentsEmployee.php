<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Hrms;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class SalaryComponentsEmployee
 * 
 * @property int $id
 * @property int $firm_id
 * @property int $employee_id
 * @property int $salary_template_id
 * @property int|null $salary_component_group_id
 * @property int $salary_component_id
 * @property int $sequence
 * @property string $nature
 * @property string $component_type
 * @property string|null $amount_type
 * @property float $amount
 * @property bool $taxable
 * @property array|null $calculation_json
 * @property Carbon $effective_from
 * @property Carbon|null $effective_to
 * @property int|null $user_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property Employee $employee
 * @property Firm $firm
 * @property SalaryComponentGroup|null $salary_component_group
 * @property SalaryComponent $salary_component
 * @property SalaryTemplate $salary_template
 * @property User|null $user
 *
 * @package App\Models\Hrms
 */
class SalaryComponentsEmployee extends Model
{
	use SoftDeletes;
	protected $table = 'salary_components_employees';

	protected $casts = [
		'firm_id' => 'int',
		'employee_id' => 'int',
		'salary_template_id' => 'int',
		'salary_component_group_id' => 'int',
		'salary_component_id' => 'int',
		'sequence' => 'int',
		'amount' => 'float',
		'taxable' => 'bool',
		'calculation_json' => 'json',
		'effective_from' => 'datetime',
		'effective_to' => 'datetime',
		'user_id' => 'int'
	];

	protected $fillable = [
		'firm_id',
		'employee_id',
		'salary_template_id',
		'salary_component_group_id',
		'salary_component_id',
		'sequence',
		'nature',
		'component_type',
		'amount_type',
		'amount',
		'taxable',
		'calculation_json',
		'effective_from',
		'effective_to',
		'user_id'
	];

	public const NATURE_SELECT = [
		'earning' => 'Earning',
		'deduction' => 'Deduction',
		'no_impact' => 'No Impact'
	];

	public const COMPONENT_TYPE_SELECT = [
		'regular' => 'Regular',
		'one_time' => 'One Time',
		'reimbursement' => 'Reimbursement',
		'advance' => 'Advance',
		'arrear' => 'Arrear',
		'tax' => 'Tax',
		'employee_contribution' => 'Employee Contribution',
		'employer_contribution' => 'Employer Contribution'
	];

	public const AMOUNT_TYPE_SELECT = [
		'static_known' => 'Static Known',
		'static_unknown' => 'Static Unknown',
		'calculated_known' => 'Calculated Known',
		'calculated_unknown' => 'Calculated Unknown'
	];

	public function employee()
	{
		return $this->belongsTo(Employee::class);
	}

	public function firm()
	{
		return $this->belongsTo(Firm::class);
	}

	public function salary_component_group()
	{
		return $this->belongsTo(SalaryComponentGroup::class);
	}

	public function salary_component()
	{
		return $this->belongsTo(SalaryComponent::class);
	}

	public function salary_template()
	{
		return $this->belongsTo(SalaryTemplate::class);
	}

	public function user()
	{
		return $this->belongsTo(User::class);
	}
}
