<?php

namespace App\Models\Hrms;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class FinalSettlementItem
 * 
 * @property int $id
 * @property int $firm_id
 * @property int $exit_id
 * @property int $final_settlement_id
 * @property int $employee_id
 * @property int $salary_component_id
 * @property string $nature
 * @property float $amount
 * @property string|null $remarks
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property Firm $firm
 * @property EmployeeExit $exit
 * @property FinalSettlement $finalSettlement
 * @property Employee $employee
 * @property SalaryComponent $salaryComponent
 *
 * @package App\Models\Hrms
 */
class FinalSettlementItem extends Model
{
    use SoftDeletes;

    protected $table = 'final_settlements_items';

    protected $casts = [
        'firm_id' => 'int',
        'exit_id' => 'int',
        'final_settlement_id' => 'int',
        'employee_id' => 'int',
        'salary_component_id' => 'int',
        'amount' => 'decimal:2'
    ];

    protected $fillable = [
        'firm_id',
        'exit_id',
        'final_settlement_id',
        'employee_id',
        'salary_component_id',
        'nature',
        'amount',
        'remarks'
    ];

    public function firm()
    {
        return $this->belongsTo(Firm::class);
    }

    public function exit()
    {
        return $this->belongsTo(EmployeeExit::class, 'exit_id');
    }

    public function finalSettlement()
    {
        return $this->belongsTo(FinalSettlement::class, 'final_settlement_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function salaryComponent()
    {
        return $this->belongsTo(SalaryComponent::class);
    }
} 