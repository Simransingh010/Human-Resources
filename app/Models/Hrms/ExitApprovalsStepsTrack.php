<?php

namespace App\Models\Hrms;

use App\Models\Saas\Firm;
use App\Models\Settings\Department;
use App\Models\Settings\Designation;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class ExitApprovalsStepsTrack
 * 
 * @property int $id
 * @property int $firm_id
 * @property int $exit_id
 * @property int $employee_id
 * @property int $exit_employee_department_id
 * @property int $exit_employee_designation_id
 * @property int $flow_order
 * @property string $approval_type
 * @property int $department_id
 * @property string|null $remarks
 * @property string $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property Firm $firm
 * @property EmployeeExit $exit
 * @property Employee $employee
 * @property Department $exitEmployeeDepartment
 * @property Designation $exitEmployeeDesignation
 * @property Department $department
 * @property ExitApprovalActionsTrack[] $exitApprovalActionsTracks
 *
 * @package App\Models\Hrms
 */
class ExitApprovalsStepsTrack extends Model
{
    use SoftDeletes;

    protected $table = 'exit_approvals_steps_track';

    protected $casts = [
        'firm_id' => 'int',
        'exit_id' => 'int',
        'employee_id' => 'int',
        'exit_employee_department_id' => 'int',
        'exit_employee_designation_id' => 'int',
        'flow_order' => 'int',
        'department_id' => 'int'
    ];

    protected $fillable = [
        'firm_id',
        'exit_id',
        'employee_id',
        'exit_employee_department_id',
        'exit_employee_designation_id',
        'flow_order',
        'approval_type',
        'department_id',
        'remarks',
        'status'
    ];

    public function firm()
    {
        return $this->belongsTo(Firm::class);
    }

    public function exit()
    {
        return $this->belongsTo(EmployeeExit::class, 'exit_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function exitEmployeeDepartment()
    {
        return $this->belongsTo(Department::class, 'exit_employee_department_id');
    }

    public function exitEmployeeDesignation()
    {
        return $this->belongsTo(Designation::class, 'exit_employee_designation_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function exitApprovalActionsTracks()
    {
        return $this->hasMany(ExitApprovalActionsTrack::class, 'exit_approvals_steps_track_id');
    }
} 