<?php

namespace App\Models\Hrms;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class ExitInterview
 * 
 * @property int $id
 * @property int $firm_id
 * @property int $exit_id
 * @property int $interviewer_id
 * @property Carbon $interview_date
 * @property string|null $interview_notes
 * @property int|null $feedback_rating
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property Firm $firm
 * @property EmployeeExit $exit
 * @property Employee $interviewer
 *
 * @package App\Models\Hrms
 */
class ExitInterview extends Model
{
    use SoftDeletes;

    protected $table = 'exit_interviews';

    protected $casts = [
        'firm_id' => 'int',
        'exit_id' => 'int',
        'interviewer_id' => 'int',
        'interview_date' => 'date',
        'feedback_rating' => 'int'
    ];

    protected $fillable = [
        'firm_id',
        'exit_id',
        'interviewer_id',
        'interview_date',
        'interview_notes',
        'feedback_rating'
    ];

    public function firm()
    {
        return $this->belongsTo(Firm::class);
    }

    public function exit()
    {
        return $this->belongsTo(EmployeeExit::class, 'exit_id');
    }

    public function interviewer()
    {
        return $this->belongsTo(Employee::class, 'interviewer_id');
    }
} 