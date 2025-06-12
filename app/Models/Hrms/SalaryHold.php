<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Hrms;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class SalaryHold
 * 
 * @property int $id
 * @property int $firm_id
 * @property int $employee_id
 * @property int $payroll_slot_id
 * @property string|null $remarks
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Employee $employee
 * @property Firm $firm
 * @property PayrollSlot $payrollSlot
 *
 * @package App\Models\Hrms
 */
class SalaryHold extends Model
{
    protected $table = 'salary_holds';

    protected $casts = [
        'firm_id' => 'int',
        'employee_id' => 'int',
        'payroll_slot_id' => 'int'
    ];

    protected $fillable = [
        'firm_id',
        'employee_id',
        'payroll_slot_id',
        'remarks'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function firm()
    {
        return $this->belongsTo(Firm::class);
    }

    public function payrollSlot()
    {
        return $this->belongsTo(PayrollSlot::class);
    }
} 