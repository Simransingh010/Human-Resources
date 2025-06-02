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
 * Class PayrollStep
 *
 * @property int $id
 * @property int $firm_id
 * @property string $step_code_main
 * @property string $step_title
 * @property string|null $step_desc
 * @property bool $required
 * @property int $step_order
 * @property bool $is_inactive
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 *
 * @property Firm $firm
 * @property Collection|PayrollSlot[] $payroll_slots
 *
 * @package App\Models\Hrms
 */
class PayrollStep extends Model
{
    use SoftDeletes;
    protected $table = 'payroll_steps';

    protected $casts = [
        'firm_id' => 'int',
        'required' => 'bool',
        'step_order' => 'int',
        'is_inactive' => 'bool'
    ];

    public const STEP_CODE_MAIN_STATUS = [
        'fetch_attendance'=> 'Fetch Attendance',
        'lop_attendance' => 'LOP Adjustment',
        'static_unknown' => 'Set Head Amounts Manually',
        'tds_calculation' => 'TDS',
    ];

    protected $fillable = [
        'firm_id',
        'step_code_main',
        'step_title',
        'step_desc',
        'required',
        'step_order',
        'is_inactive'
    ];

    public function firm()
    {
        return $this->belongsTo(Firm::class);
    }

    public function payroll_slots()
    {
        return $this->belongsToMany(PayrollSlot::class, 'payroll_step_payroll_slot')
            ->withPivot('id', 'firm_id', 'step_code_main', 'payroll_step_status', 'deleted_at')
            ->withTimestamps();
    }
}
