<?php

namespace App\Models\Hrms;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class ExitApprovalActionsTrack
 * 
 * @property int $id
 * @property int $firm_id
 * @property int $exit_approvals_steps_track_id
 * @property int $employee_id
 * @property int $exit_approval_step_id
 * @property string|null $clearance_type
 * @property string $clearance_item
 * @property string|null $clearance_desc
 * @property string|null $remarks
 * @property string $status
 * @property int|null $clearance_by_user_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property Firm $firm
 * @property ExitApprovalsStepsTrack $exitApprovalsStepsTrack
 * @property Employee $employee
 * @property ExitApprovalStep $exitApprovalStep
 * @property User|null $clearanceByUser
 *
 * @package App\Models\Hrms
 */
class ExitApprovalActionsTrack extends Model
{
    use SoftDeletes;

    protected $table = 'exit_approval_actions_track';

    protected $casts = [
        'firm_id' => 'int',
        'exit_approvals_steps_track_id' => 'int',
        'employee_id' => 'int',
        'exit_approval_step_id' => 'int',
        'clearance_by_user_id' => 'int'
    ];

    protected $fillable = [
        'firm_id',
        'exit_approvals_steps_track_id',
        'employee_id',
        'exit_approval_step_id',
        'clearance_type',
        'clearance_item',
        'clearance_desc',
        'remarks',
        'status',
        'clearance_by_user_id'
    ];

    public function firm()
    {
        return $this->belongsTo(Firm::class);
    }

    public function exitApprovalsStepsTrack()
    {
        return $this->belongsTo(ExitApprovalsStepsTrack::class, 'exit_approvals_steps_track_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function exitApprovalStep()
    {
        return $this->belongsTo(ExitApprovalStep::class);
    }

    public function clearanceByUser()
    {
        return $this->belongsTo(User::class, 'clearance_by_user_id');
    }
} 