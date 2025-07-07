<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Hrms;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class EmployeesSalaryDay
 * 
 * @property int $id
 * @property int $firm_id
 * @property int $payroll_slot_id
 * @property int $employee_id
* // * php artisan make:migration add_firm_id_to_panel_user_table --table=panel_user
 * @property int $cycle_days
 * @property int $void_days_count
 * @property int $lop_days_count
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Employee $employee
 * @property Firm $firm
 * @property PayrollSlot $payroll_slot
 *
 * @package App\Models\Hrms
 */
class EmployeesSalaryDay extends Model
{
	protected $table = 'employees_salary_days';

	protected $casts = [
		'firm_id' => 'int',
		'payroll_slot_id' => 'int',
		'employee_id' => 'int',
		'cycle_days' => 'int',
		'void_days_count' => 'int',
		'lop_days_count' => 'int',
		'lop_details' => 'array'
	];

	protected $fillable = [
		'firm_id',
		'payroll_slot_id',
		'employee_id',
		'cycle_days',
		'void_days_count',
		'lop_days_count',
		'lop_details',
	];

	public function employee()
	{
		return $this->belongsTo(Employee::class);
	}

	public function firm()
	{
		return $this->belongsTo(Firm::class);
	}

	public function payroll_slot()
	{
		return $this->belongsTo(PayrollSlot::class);
	}
}
