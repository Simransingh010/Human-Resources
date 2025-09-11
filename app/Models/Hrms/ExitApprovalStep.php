<?php

namespace App\Models\Hrms;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Saas\Firm;
use App\Models\Settings\Department;
use App\Models\Settings\Designation;

/**
 * Class ExitApprovalStep
 * 
 * @property int $id
 * @property int $firm_id
 * @property int $exit_employee_department_id
 * @property int $exit_employee_designation_id
 * @property int $flow_order
 * @property string $approval_type
 * @property int $department_id
 * @property bool $is_inactive
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property Firm $firm
 * @property Department $exitEmployeeDepartment
 * @property Designation $exitEmployeeDesignation
 * @property Department $department
 * @property ExitApprovalAction[] $exitApprovalActions
 *
 * @package App\Models\Hrms
 */
class ExitApprovalStep extends Model
{
    use SoftDeletes;

    protected $table = 'exit_approval_steps';

    protected $casts = [
        'firm_id' => 'int',
        'exit_employee_department_id' => 'int',
        'exit_employee_designation_id' => 'int',
        'flow_order' => 'int',
        'department_id' => 'int',
        'is_inactive' => 'bool'
    ];

    protected $fillable = [
        'firm_id',
        'exit_employee_department_id',
        'exit_employee_designation_id',
        'flow_order',
        'approval_type',
        'department_id',
        'is_inactive'
    ];


    public const APPROVAL_TYPE_SELECT = [
        'department_head' => 'Department Head',
        'hr_manager' => 'HR Manager',
        'finance_manager' => 'Finance Manager',
        'it_manager' => 'IT Manager',
        'admin_manager' => 'Admin Manager',
        'general_manager' => 'General Manager',
        'director' => 'Director',
        'ceo' => 'CEO',
    ];

    public function getApprovalTypeLabelAttribute($value)
    {
        return static::APPROVAL_TYPE_SELECT[$this->approval_type] ?? null;
    }

    public function firm()
    {
        return $this->belongsTo(Firm::class);
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

    public function exitApprovalActions()
    {
        return $this->hasMany(ExitApprovalAction::class);
    }
} 