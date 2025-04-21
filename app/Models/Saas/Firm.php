<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Saas;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class Firm
 *
 * @property int $id
 * @property string $name
 * @property string|null $short_name
 * @property string|null $firm_type
 * @property int|null $agency_id
 * @property int|null $parent_firm_id
 * @property bool $is_master_firm
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 *
 * @property Agency|null $agency
 * @property Firm|null $firm
 * @property Collection|EmpAttendance[] $emp_attendances
 * @property Collection|EmpLeaveAllocation[] $emp_leave_allocations
 * @property Collection|EmpLeaveRequestLog[] $emp_leave_request_logs
 * @property Collection|EmpLeaveRequest[] $emp_leave_requests
 * @property Collection|EmpPunch[] $emp_punches
 * @property Collection|EmpWorkShift[] $emp_work_shifts
 * @property Collection|Employee[] $employees
 * @property Collection|App[] $apps
 * @property Collection|User[] $users
 * @property Collection|Firm[] $firms
 * @property Collection|LeaveType[] $leave_types
 * @property Collection|LeavesQuotaTemplateSetup[] $leaves_quota_template_setups
 * @property Collection|LeavesQuotaTemplate[] $leaves_quota_templates
 * @property Collection|PermissionGroup[] $permission_groups
 * @property Collection|UserPermission[] $user_permissions
 * @property Collection|WorkBreak[] $work_breaks
 * @property Collection|WorkShiftDay[] $work_shift_days
 * @property Collection|WorkShiftDaysBreak[] $work_shift_days_breaks
 * @property Collection|WorkShift[] $work_shifts
 * @property Collection|WorkShiftsAlgo[] $work_shifts_algos
 *
 * @package App\Models\Saas
 */
class Firm extends Model
{
	use SoftDeletes,HasFactory;
	protected $table = 'firms';

	protected $casts = [
		'agency_id' => 'int',
		'parent_firm_id' => 'int',
		'is_master_firm' => 'bool',
        'is_inactive' => 'bool'
	];

	protected $fillable = [
		'name',
		'short_name',
		'firm_type',
		'agency_id',
		'parent_firm_id',
		'is_master_firm',
        'is_inactive'
	];

    public const FIRM_TYPE_SELECT = [
        '1' => 'Proprietorship',
        '2' => 'Partnership Firm',
        '3' => 'LLP (Limited Liability Partnership)',
        '4' => 'Private Limited Company',
        '5' => 'Public Limited Company',
        '6' => 'One Person Company (OPC)',
        '7' => 'Cooperative Society',
        '8' => 'Trust',
        '9' => 'Society',
        '10' => 'Section 8 Company (Non-Profit)',
    ];

    public function getFirmTypeLabelAttribute($value)
    {
        return static::FIRM_TYPE_SELECT[$this->firm_type] ?? null;
    }

	public function agency()
	{
		return $this->belongsTo(Agency::class);
	}

	public function firm()
	{
		return $this->belongsTo(Firm::class, 'parent_firm_id');
	}

	public function emp_attendances()
	{
		return $this->hasMany(EmpAttendance::class);
	}

	public function emp_leave_allocations()
	{
		return $this->hasMany(EmpLeaveAllocation::class);
	}

	public function emp_leave_request_logs()
	{
		return $this->hasMany(EmpLeaveRequestLog::class);
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

	public function employees()
	{
		return $this->hasMany(Employee::class);
	}

	public function apps()
	{
		return $this->belongsToMany(App::class, 'firm_app_access')
					->withPivot('id', 'app_module_id', 'is_inactive', 'deleted_at')
					->withTimestamps();
	}

	public function users()
	{
		return $this->belongsToMany(User::class)
					->withPivot('id', 'is_default', 'deleted_at')
					->withTimestamps();
	}

	public function firms()
	{
		return $this->hasMany(Firm::class, 'parent_firm_id');
	}

	public function leave_types()
	{
		return $this->hasMany(LeaveType::class);
	}

	public function leaves_quota_template_setups()
	{
		return $this->hasMany(LeavesQuotaTemplateSetup::class);
	}

	public function leaves_quota_templates()
	{
		return $this->hasMany(LeavesQuotaTemplate::class);
	}

	public function permission_groups()
	{
		return $this->hasMany(PermissionGroup::class);
	}

	public function user_permissions()
	{
		return $this->hasMany(UserPermission::class);
	}

	public function work_breaks()
	{
		return $this->hasMany(WorkBreak::class);
	}

	public function work_shift_days()
	{
		return $this->hasMany(WorkShiftDay::class);
	}

	public function work_shift_days_breaks()
	{
		return $this->hasMany(WorkShiftDaysBreak::class);
	}

	public function work_shifts()
	{
		return $this->hasMany(WorkShift::class);
	}

	public function work_shifts_algos()
	{
		return $this->hasMany(WorkShiftsAlgo::class);
	}
}
