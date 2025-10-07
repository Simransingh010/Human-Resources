<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Hrms;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class EmpTdsRecord
 *
 * @property int $id
 * @property int $firm_id
 * @property int $emp_id
 * @property int $payroll_slot_id
 * @property float $payable_tds
 * @property float $paid_tds
 * @property float $balance
 * @property string|null $remarks
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 */
class EmpTdsRecord extends Model
{
    use SoftDeletes;

    protected $table = 'emp_tds_records';

    protected $casts = [
        'firm_id' => 'int',
        'emp_id' => 'int',
        'payroll_slot_id' => 'int',
        'payable_tds' => 'float',
        'paid_tds' => 'float',
        'balance' => 'float',
    ];

    protected $fillable = [
        'firm_id',
        'emp_id',
        'payroll_slot_id',
        'payable_tds',
        'paid_tds',
        'balance',
        'remarks',
    ];
}


