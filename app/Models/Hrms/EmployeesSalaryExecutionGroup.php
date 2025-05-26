<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Hrms;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class EmployeesSalaryExecutionGroup
 * 
 * @property int $id
 * @property int $firm_id
 * @property int $salary_execution_group_id
 * @property int $employee_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Employee $employee
 * @property Firm $firm
 * @property SalaryExecutionGroup $salary_execution_group
 *
 * @package App\Models\Hrms
 */
class EmployeesSalaryExecutionGroup extends Model
{
	protected $table = 'employees_salary_execution_group';

	protected $casts = [
		'firm_id' => 'int',
		'salary_execution_group_id' => 'int',
		'employee_id' => 'int'
	];

	protected $fillable = [
		'firm_id',
		'salary_execution_group_id',
		'employee_id'
	];

	public function employee()
	{
		return $this->belongsTo(Employee::class);
	}

	public function firm()
	{
		return $this->belongsTo(Firm::class);
	}

	public function salary_execution_group()
	{
		return $this->belongsTo(SalaryExecutionGroup::class);
	}
}
