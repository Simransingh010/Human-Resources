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
 * Class EmpLeaveRequest
 * 
 * @property int $id
 * @property int $firm_id
 * @property int $employee_id
 * @property int $leave_type_id
 * @property Carbon $apply_from
 * @property Carbon $apply_to
 * @property int $apply_days
 * @property string|null $reason
 * @property string $status
 * @property string|null $time_from
 * @property string|null $time_to
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property Employee $employee
 * @property Firm $firm
 * @property LeaveType $leave_type
 * @property Collection|EmpLeaveRequestApproval[] $emp_leave_request_approvals
 * @property Collection|LeaveRequestEvent[] $leave_request_events
 *
 * @package App\Models\Hrms
 */
class EmpLeaveRequest extends Model
{
	use SoftDeletes;
	protected $table = 'emp_leave_requests';

	protected $casts = [
		'firm_id' => 'int',
		'employee_id' => 'int',
		'leave_type_id' => 'int',
		'apply_from' => 'datetime',
		'apply_to' => 'datetime',
		'apply_days' => 'decimal:2',
		'time_from' => 'datetime',
		'time_to' => 'datetime'
	];

    protected $appends = ['is_half_day', 'time_info', 'half_day_type', 'leave_age', 'status_label'];

    public function getApplyFromAttribute($value)
    {
        // Parse the datetime and return only the date part in Y-m-d format
        // This ensures we get the correct date regardless of timezone
        return Carbon::parse($value)->toDateString();
    }

    public function getApplyToAttribute($value)
    {
        // Parse the datetime and return only the date part in Y-m-d format
        // This ensures we get the correct date regardless of timezone
        return Carbon::parse($value)->toDateString();
    }

    public function getCreatedAtAttribute($value)
    {
        return $this->asDateTime($value)->format('Y-m-d H:i:s');
    }

    public function getUpdatedAtAttribute($value)
    {
        return $this->asDateTime($value)->format('Y-m-d H:i:s');
    }

    public function getIsHalfDayAttribute()
    {
        return floatval($this->apply_days) === 0.5;
    }

    public function getTimeInfoAttribute()
    {
        if ($this->time_from && $this->time_to) {
            return "(" . $this->asDateTime($this->time_from)->format('H:i') . " - " . $this->asDateTime($this->time_to)->format('H:i') . ")";
        }
        return '';
    }

    public function getHalfDayTypeAttribute()
    {
        if ($this->is_half_day && $this->time_from && $this->time_to) {
            return $this->asDateTime($this->time_from)->format('H:i') <= '12:00' ? 'First Half' : 'Second Half';
        }
        return '';
    }

    public function getLeaveAgeAttribute()
    {
        if ($this->is_half_day) {
            return 'half';
        } elseif ($this->time_from && $this->time_to) {
            return 'hourly';
        } elseif (floatval($this->apply_days) == 1.0) {
            return 'single';
        } elseif (floatval($this->apply_days) > 1.0) {
            return 'multi';
        }
        return '';
    }

    public function getStatusLabelAttribute()
    {
        return static::STATUS_SELECT[$this->status] ?? 'Unknown';
    }

    public function setApplyFromAttribute($value)
    {
        // Ensure the date is stored as a proper datetime with start of day
        $this->attributes['apply_from'] = Carbon::parse($value)->startOfDay();
    }

    public function setApplyToAttribute($value)
    {
        // Ensure the date is stored as a proper datetime with start of day
        $this->attributes['apply_to'] = Carbon::parse($value)->startOfDay();
    }

    public const STATUS_SELECT = [
        'applied' => 'Applied',
        'reviewed' => 'Reviewed',
        'approved' => 'Approved',
        'approved_further' => 'Approved & Sent for Further Approval',
        'partially_approved' => 'Partially Approved',
        'rejected' => 'Rejected',
        'cancelled_employee' => 'Cancelled by Employee',
        'cancelled_hr' => 'Cancelled by HR/Admin',
        'modified' => 'Modified',
        'escalated' => 'Escalated',
        'delegated' => 'Delegated',
        'hold' => 'Hold',
        'expired' => 'Expired',
        'withdrawn' => 'Withdrawn',
        'auto_approved' => 'Auto-Approved'
    ];

	protected $fillable = [
		'firm_id',
		'employee_id',
		'leave_type_id',
		'apply_from',
		'apply_to',
		'apply_days',
		'reason',
		'status',
        'time_from',
        'time_to'
	];

	public function employee()
	{
		return $this->belongsTo(Employee::class);
	}

	public function firm()
	{
		return $this->belongsTo(Firm::class);
	}

	public function leave_type()
	{
		return $this->belongsTo(LeaveType::class);
	}

	public function emp_leave_request_approvals()
	{
		return $this->hasMany(EmpLeaveRequestApproval::class);
	}

	public function leave_request_events()
	{
		return $this->hasMany(LeaveRequestEvent::class);
	}
}
