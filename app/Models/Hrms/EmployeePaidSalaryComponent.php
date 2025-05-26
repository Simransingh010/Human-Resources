<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Hrms;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class EmployeePaidSalaryComponent
 * 
 * @property int $id
 * @property int $firm_id
 * @property int $salary_disbursement_batch_id
 * @property int $payroll_components_employees_track_id
 * @property float $amount_paid
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Firm $firm
 * @property PayrollComponentsEmployeesTrack $payroll_components_employees_track
 * @property SalaryDisbursementBatch $salary_disbursement_batch
 *
 * @package App\Models\Hrms
 */
class EmployeePaidSalaryComponent extends Model
{
	protected $table = 'employee_paid_salary_components';

	protected $casts = [
		'firm_id' => 'int',
		'salary_disbursement_batch_id' => 'int',
		'payroll_components_employees_track_id' => 'int',
		'amount_paid' => 'float'
	];

	protected $fillable = [
		'firm_id',
		'salary_disbursement_batch_id',
		'payroll_components_employees_track_id',
		'amount_paid'
	];

	public function firm()
	{
		return $this->belongsTo(Firm::class);
	}

	public function payroll_components_employees_track()
	{
		return $this->belongsTo(PayrollComponentsEmployeesTrack::class);
	}

	public function salary_disbursement_batch()
	{
		return $this->belongsTo(SalaryDisbursementBatch::class);
	}
}
