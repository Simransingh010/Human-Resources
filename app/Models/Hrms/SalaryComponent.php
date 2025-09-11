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
 * Class SalaryComponent
 * 
 * @property int $id
 * @property int $firm_id
 * @property string $title
 * @property string|null $description
 * @property int|null $salary_component_group_id
 * @property string $nature
 * @property string $component_type
 * @property string|null $amount_type
 * @property bool $taxable
 * @property array|null $calculation_json
 * @property bool $document_required
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property Firm $firm
 * @property SalaryComponentGroup|null $salary_component_group
 * @property Collection|Employee[] $employees
 * @property Collection|SalaryTemplatesComponent[] $salary_templates_components
 *
 * @package App\Models\Hrms
 */
class SalaryComponent extends Model
{
	use SoftDeletes;
	protected $table = 'salary_components';

	protected $casts = [
		'firm_id' => 'int',
		'salary_component_group_id' => 'int',
		'taxable' => 'bool',
		'calculation_json' => 'json',
		'document_required' => 'bool'
	];

	protected $fillable = [
		'firm_id',
		'title',
		'description',
		'salary_component_group_id',
		'nature',
		'component_type',
		'amount_type',
		'taxable',
		'calculation_json',
		'document_required'
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
        'tds' => 'TDS',
		'epf' => 'EPF',	
		
		'employee_contribution' => 'Employee Contribution',
		'employer_contribution' => 'Employer Contribution',
		'lop_deduction' => "Lop Deduction",
	];

	public const AMOUNT_TYPE_SELECT = [
		'static_known' => 'Static Known',
		'static_unknown' => 'Static Unknown',
		'calculated_known' => 'Calculated Known',
        'calculated_unknown' => 'Calculated Unknown',
	];

	public function firm()
	{
		return $this->belongsTo(Firm::class);
	}

	public function salary_component_group()
	{
		return $this->belongsTo(SalaryComponentGroup::class);
	}

	public function employees()
	{
		return $this->belongsToMany(Employee::class, 'salary_components_employees')
			->withPivot('id', 'firm_id', 'salary_template_id', 'salary_component_group_id', 'sequence', 'nature', 'component_type', 'amount_type', 'amount', 'taxable', 'calculation_json', 'effective_from', 'effective_to', 'user_id', 'deleted_at')
			->withTimestamps();
	}

	public function salary_templates_components()
	{
		return $this->hasMany(SalaryTemplatesComponent::class);
	}

	public function salary_components_employees()
	{
		return $this->hasMany(SalaryComponentsEmployee::class);
	}
}
