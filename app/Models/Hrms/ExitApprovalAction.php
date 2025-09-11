<?php

namespace App\Models\Hrms;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class ExitApprovalAction
 * 
 * @property int $id
 * @property int $firm_id
 * @property int $exit_approval_step_id
 * @property string $clearance_item
 * @property string|null $clearance_desc
 * @property bool $is_inactive
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property Firm $firm
 * @property ExitApprovalStep $exitApprovalStep
 *
 * @package App\Models\Hrms
 */
class ExitApprovalAction extends Model
{
    use SoftDeletes;

    protected $table = 'exit_approval_actions';

    protected $casts = [
        'firm_id' => 'int',
        'exit_approval_step_id' => 'int',
        'is_inactive' => 'bool'
    ];

    protected $fillable = [
        'firm_id',
        'exit_approval_step_id',
        'clearance_item',
        'clearance_desc',
        'is_inactive'
    ];

    public function firm()
    {
        return $this->belongsTo(Firm::class);
    }

    public function exitApprovalStep()
    {
        return $this->belongsTo(ExitApprovalStep::class);
    }
} 