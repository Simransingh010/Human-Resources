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
 * Class PayrollSlot
 *
 * @property int $id
 * @property int $firm_id
 * @property int|null $salary_cycle_id
 * @property int|null $salary_execution_group_id
 * @property Carbon $from_date
 * @property Carbon $to_date
 * @property string $payroll_slot_status
 * @property string $title
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 *
 * @property SalaryCycle|null $salary_cycle
 * @property Firm $firm
 * @property SalaryExecutionGroup|null $salary_execution_group
 * @property Collection|EmployeesLopDaysLog[] $employees_lop_days_logs
 * @property Collection|EmployeesSalaryDay[] $employees_salary_days
 * @property Collection|PayrollComponentsEmployeesTrack[] $payroll_components_employees_tracks
 * @property Collection|PayrollSlotsCmd[] $payroll_slots_cmds
 * @property Collection|PayrollStep[] $payroll_steps
 *
 * @package App\Models\Hrms
 */
class PayrollSlot extends Model
{
	use SoftDeletes;
	protected $table = 'payroll_slots';

	protected $casts = [
		'firm_id' => 'int',
		'salary_cycle_id' => 'int',
		'salary_execution_group_id' => 'int',
		'from_date' => 'datetime',
		'to_date' => 'datetime'
	];

	protected $fillable = [
		'firm_id',
		'salary_cycle_id',
		'salary_execution_group_id',
		'from_date',
		'to_date',
		'payroll_slot_status',
		'title'
	];
	public const PAYROLL_SLOT_STATUS = [
        'PN'=> 'Pending',
		'ND' => 'Not Due',
		'NX' => 'Next Due',
		'ST' => 'Started',
		'HT' => 'Halted',
		'SP' => 'Suspended',
		'CM' => 'Completed',
        'RS' => 'Re-Started',
        'L' => 'Locked',
	];

	public function salary_cycle()
	{
		return $this->belongsTo(SalaryCycle::class);
	}

	public function firm()
	{
		return $this->belongsTo(Firm::class);
	}

	public function salary_execution_group()
	{
		return $this->belongsTo(SalaryExecutionGroup::class);
	}


   //Undefined constant App\Models\Hrms\PayrollSlot::STATUS_SELECT
    //GET start.iqdigit.com
    
	public function employees_lop_days_logs()
	{
		return $this->hasMany(EmployeesLopDaysLog::class);
	}

	public function employees_salary_days()
	{
		return $this->hasMany(EmployeesSalaryDay::class);
	}

	public function payroll_components_employees_tracks()
	{
		return $this->hasMany(PayrollComponentsEmployeesTrack::class);
	}

	public function payroll_slots_cmds()
	{
		return $this->hasMany(PayrollSlotsCmd::class);
	}

	public function payroll_steps()
	{
		return $this->belongsToMany(PayrollStep::class, 'payroll_step_payroll_slot')
			->withPivot('id', 'firm_id', 'step_code_main', 'payroll_step_status', 'deleted_at')
			->withTimestamps();
	}
}
