<?php

namespace App\Models\Hrms;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class FinalSettlement
 * 
 * @property int $id
 * @property int $firm_id
 * @property int $exit_id
 * @property int $employee_id
 * @property Carbon $settlement_date
 * @property int|null $disburse_payroll_slot_id
 * @property float $fnf_earning_amount
 * @property float $fnf_deduction_amount
 * @property string $full_final_status
 * @property string|null $remarksp
 * @property string|null $additional_rule
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property Firm $firm
 * @property EmployeeExit $exit
 * @property Employee $employee
 * @property PayrollSlot|null $disbursePayrollSlot
 * @property FinalSettlementItem[] $finalSettlementItems
 *
 * @package App\Models\Hrms
 */
class FinalSettlement extends Model
{
    use SoftDeletes;

    protected $table = 'final_settlements';

    protected $casts = [
        'firm_id' => 'int',
        'exit_id' => 'int',
        'employee_id' => 'int',
        'settlement_date' => 'date',
        'disburse_payroll_slot_id' => 'int',
        'fnf_earning_amount' => 'decimal:2',
        'fnf_deduction_amount' => 'decimal:2'
    ];

    protected $fillable = [
        'firm_id',
        'exit_id',
        'employee_id',
        'settlement_date',
        'disburse_payroll_slot_id',
        'fnf_earning_amount',
        'fnf_deduction_amount',
        'full_final_status',
        'remarks',
        'additional_rule'
    ];

    public const full_final_status_select = [
        'pending' => 'Pending',
        'settled' => 'Settled',
        'disbursed' => 'Disbursed',
        'locked' => 'Locked',
        'published' => 'Published',
       'rejected' => 'Rejected',
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

    public function disbursePayrollSlot()
    {
        return $this->belongsTo(PayrollSlot::class, 'disburse_payroll_slot_id');
    }

    public function finalSettlementItems()
    {
        return $this->hasMany(FinalSettlementItem::class);
    }

    /**
     * Recompute and persist FNF totals from linked items.
     */
    public function recomputeTotals(): void
    {
        $earning = $this->finalSettlementItems()
            ->where('nature', 'earning')
            ->sum('amount');

        $deduction = $this->finalSettlementItems()
            ->where('nature', 'deduction')
            ->sum('amount');

        $this->forceFill([
            'fnf_earning_amount' => (float) $earning,
            'fnf_deduction_amount' => (float) $deduction,
        ])->save();
    }
} 