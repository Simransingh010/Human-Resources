<?php

namespace App\Models\Hrms;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class EmployeeExit
 * 
 * @property int $id
 * @property int $firm_id
 * @property int $employee_id
 * @property string $exit_type
 * @property string $exit_reason
 * @property int $initiated_by_user_id
 * @property Carbon $exit_request_date
 * @property int $notice_period_days
 * @property Carbon|null $last_working_day
 * @property Carbon|null $actual_relieving_date
 * @property string $status
 * @property string|null $remarks
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property Firm $firm
 * @property Employee $employee
 * @property User $initiatedByUser
 *
 * @package App\Models\Hrms
 */
class EmployeeExit extends Model
{
    use SoftDeletes;

    protected $table = 'employee_exits';

    public const EXIT_TYPES = [
        'resignation' => 'Resignation',
        'termination' => 'Termination',
        'retirement' => 'Retirement',
        'absconding' => 'Absconding',
        'contract_end' => 'Contract End',   
        'rejoining' => 'Rejoining',
        'death' => 'Death',
        'other' => 'Other',
    ];

    protected $casts = [
        'firm_id' => 'int',
        'employee_id' => 'int',
        'initiated_by_user_id' => 'int',
        'exit_request_date' => 'date',
        'notice_period_days' => 'int',
        'last_working_day' => 'date',
        'actual_relieving_date' => 'date'
    ];

    protected $fillable = [
        'firm_id',
        'employee_id',
        'exit_type',
        'exit_reason',
        'initiated_by_user_id',
        'exit_request_date',
        'notice_period_days',
        'last_working_day',
        'actual_relieving_date',
        'status',
        'remarks'
    ];

    public function firm()
    {
        return $this->belongsTo(Firm::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function initiatedByUser()
    {
        return $this->belongsTo(User::class, 'initiated_by_user_id');
    }
} 