<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Hrms;

use App\Models\User;
use App\Models\Saas\Firm;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


/**
 * Class Employee
 *
 * @property int $id
 * @property int $firm_id
 * @property string|null $fname
 * @property string|null $mname
 * @property string|null $lname
 * @property string|null $gender
 * @property string|null $email
 * @property string|null $phone
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 *
 * @property Firm $firm
 * @property Collection|EmpAttendance[] $emp_attendances
 * @property Collection|EmpLeaveAllocation[] $emp_leave_allocations
 * @property Collection|EmpLeaveRequest[] $emp_leave_requests
 * @property Collection|EmpPunch[] $emp_punches
 * @property Collection|EmpWorkShift[] $emp_work_shifts
 *
 * @package App\Models\Hrms
 */
class Employee extends Model
{
	use SoftDeletes;
	protected $table = 'employees';

	protected $casts = [
		'firm_id' => 'int'
	];

	protected $fillable = [
		'firm_id',
		'fname',
		'mname',
		'lname',
		'gender',
		'email',
		'phone',
        'user_id',
        'is_inactive'
	];

	public function firm()
	{
		return $this->belongsTo(Firm::class);
	}

	public function emp_attendances()
	{
		return $this->hasMany(EmpAttendance::class);
	}

	public function emp_leave_allocations()
	{
		return $this->hasMany(EmpLeaveAllocation::class);
	}

	public function emp_leave_requests()
	{
		return $this->hasMany(EmpLeaveRequest::class);
	}

	public function emp_punches()
	{
		return $this->hasMany(EmpPunch::class);
	}

	public function emp_work_shifts()
	{
		return $this->hasMany(EmpWorkShift::class);
	}
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function payroll_tracks()
    {
        return $this->hasMany(\App\Models\Hrms\PayrollComponentsEmployeesTrack::class, 'employee_id', 'id');
    }


    public function emp_job_profile()
    {
        return $this->hasOne(EmployeeJobProfile::class, 'employee_id');
    }

    public function bank_account()
    {
        return $this->hasOne(EmployeeBankAccount::class, 'employee_id');
    }

    public function emp_personal_detail()
    {
        return $this->hasOne(\App\Models\Hrms\EmployeePersonalDetail::class, 'employee_id');
    }

    public function documents()
    {
        return $this->hasMany(EmployeeDoc::class, 'employee_id');
    }

    public function relations()
    {
        return $this->hasMany(EmployeeRelation::class, 'employee_id');
    }
    public function emp_address()
    {
        return $this->hasMany(EmployeeAddress::class, 'employee_id');
    }
    public function emp_emergency_contact()
    {
        return $this->hasMany(EmployeeContact::class, 'employee_id');
    }
    public function salary_execution_groups()
    {
        return $this->belongsToMany(SalaryExecutionGroup::class, 'employees_salary_execution_group', 'employee_id', 'salary_execution_group_id')
            ->withPivot('id', 'firm_id')
            ->withTimestamps();
    }

    public function attendance_policy()
    {
        return $this->hasOne(AttendancePolicy::class);
    }
    public function leave_approval_rules()
    {
        return $this->belongsToMany(LeaveApprovalRule::class, 'employee_leave_approval_rule', 'employee_id', 'rule_id')
            ->withPivot('id', 'firm_id', 'is_inactive')
            ->withTimestamps();
    }

    public function salary_components_employees()
    {
        return $this->hasMany(SalaryComponentsEmployee::class, 'employee_id');
    }
}

