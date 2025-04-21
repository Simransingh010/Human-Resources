<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Hrms;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class EmployeeJobProfile
 * 
 * @property int $id
 * @property int $firm_id
 * @property int $employee_id
 * @property string $employee_code
 * @property Carbon|null $doh
 * @property int $department_id
 * @property int $designation_id
 * @property int|null $reporting_manager
 * @property int|null $employment_type
 * @property Carbon|null $doe
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property Department $department
 * @property Designation $designation
 * @property Employee|null $employee
 * @property Firm $firm
 *
 * @package App\Models\Hrms
 */
class EmployeeJobProfile extends Model
{
	use SoftDeletes;
	protected $table = 'employee_job_profiles';

	protected $casts = [
		'firm_id' => 'int',
		'employee_id' => 'int',
		'doh' => 'datetime',
		'department_id' => 'int',
		'designation_id' => 'int',
		'reporting_manager' => 'int',
		'employment_type' => 'int',
		'doe' => 'datetime'
	];

	protected $fillable = [
		'firm_id',
		'employee_id',
		'employee_code',
		'doh',
		'department_id',
		'designation_id',
		'reporting_manager',
		'employment_type',
		'doe'
	];

	public function department()
	{
		return $this->belongsTo(Department::class);
	}

	public function designation()
	{
		return $this->belongsTo(Designation::class);
	}

	public function employee()
	{
		return $this->belongsTo(Employee::class, 'reporting_manager');
	}

	public function employment_type()
	{
		return $this->belongsTo(EmploymentType::class, 'employment_type');
	}

	public function firm()
	{
		return $this->belongsTo(Firm::class);
	}
}
