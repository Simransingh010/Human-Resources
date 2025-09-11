<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Hrms;
use App\Models\Settings\Department;
use App\Models\Settings\Designation;
use App\Models\Settings\Joblocation;
use App\Models\Settings\EmploymentType;
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
 * @property string|null $uanno
 * @property string|null $esicno
 * @property int|null $joblocation_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 *
 * @property Department $department
 * @property Designation $designation
 * @property Employee|null $employee
 * @property Firm $firm
 * @property Joblocation|null $joblocation
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
		'employment_type_id' => 'int',
		'doe' => 'datetime',
		'joblocation_id' => 'int'
	];
//doe = date of exit, doh = date of hire 
	protected $fillable = [
		'firm_id',
		'employee_id',
		'employee_code',
		'doh',
		'department_id',
		'designation_id',
		'reporting_manager',
		'employment_type_id',
		'doe',
		'uanno',
		'esicno',
		'joblocation_id',
		'pran_number',
		'paylevel',
		'rf_id',
		'biometric_emp_code',
		'status'
	];

	public const STATUS_SELECT = [
		'active' => 'Active',
		'inactive' => 'Inactive',
		'terminated' => 'Terminated',
		'resigned' => 'Resigned',
		'transferred' => 'Transferred',
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
		return $this->belongsTo(Employee::class, 'employee_id');
	}

    public function manager()
    {
        return $this->belongsTo(Employee::class, 'reporting_manager');
    }

	public function employment_type()
	{
		return $this->belongsTo(EmploymentType::class, 'employment_type_id');
	}

	public function firm()
	{
		return $this->belongsTo(Firm::class);
	}

	public function joblocation()
	{
		return $this->belongsTo(Joblocation::class, 'joblocation_id');
	}
}
