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
 * Class SalaryArrear
 * 
 * @property int $id
 * @property int $firm_id
 * @property int $employee_id
 * @property int $salary_component_id
 * @property Carbon $effective_from
 * @property Carbon $effective_to
 * @property float $total_amount
 * @property float $paid_amount
 * @property int $installments
 * @property float $installment_amount
 * @property string $arrear_status
 * @property bool $is_inactive
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property SalaryComponent $salary_component
 * @property Employee $employee
 * @property Firm $firm
 * @property Collection|PayrollComponentsEmployeesTrack[] $payroll_components_employees_tracks
 *
 * @package App\Models\Hrms
 */
class SalaryArrear extends Model
{
	use SoftDeletes;
	protected $table = 'salary_arrears';

	/**
	 * Available arrear statuses
	 */
	public static $arrearStatuses = [
		'pending' => 'Pending',
		'partially_paid' => 'Partially Paid',
		'paid' => 'Paid',
	];

	protected $casts = [
		'firm_id' => 'int',
		'employee_id' => 'int',
		'salary_component_id' => 'int',
		'effective_from' => 'datetime',
		'effective_to' => 'datetime',
		'total_amount' => 'float',
		'paid_amount' => 'float',
		'installments' => 'int',
		'installment_amount' => 'float',
		'is_inactive' => 'bool'
	];

	protected $fillable = [
		'firm_id',
		'employee_id',
		'salary_component_id',
		'effective_from',
		'effective_to',
		'total_amount',
		'paid_amount',
		'installments',
		'installment_amount',
		'arrear_status',
		'is_inactive'
	];

	public function salary_component()
	{
		return $this->belongsTo(SalaryComponent::class);
	}

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
}
