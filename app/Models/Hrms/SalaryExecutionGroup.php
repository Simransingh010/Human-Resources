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
 * Class SalaryExecutionGroup
 * 
 * @property int $id
 * @property int $firm_id
 * @property string $title
 * @property string|null $description
 * @property int|null $salary_cycle_id
 * @property bool $is_inactive
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property SalaryCycle|null $salary_cycle
 * @property Firm $firm
 * @property Collection|Employee[] $employees
 * @property Collection|PayrollSlot[] $payroll_slots
 *
 * @package App\Models\Hrms
 */
class SalaryExecutionGroup extends Model
{
	use SoftDeletes;
	protected $table = 'salary_execution_groups';

	protected $casts = [
		'firm_id' => 'int',
		'salary_cycle_id' => 'int',
		'is_inactive' => 'bool'
	];

	protected $fillable = [
		'firm_id',
		'title',
		'description',
		'salary_cycle_id',
		'is_inactive'
	];

	public function salary_cycle()
	{
		return $this->belongsTo(SalaryCycle::class);
	}

	public function firm()
	{
		return $this->belongsTo(Firm::class);
	}

	public function employees()
	{
		return $this->belongsToMany(Employee::class, 'employees_salary_execution_group')
					->withPivot('id', 'firm_id')
					->withTimestamps();
	}

	public function payroll_slots()
	{
		return $this->hasMany(PayrollSlot::class);
	}
}
