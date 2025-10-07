<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Hrms;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class TaxPayment
 *
 * @property int $id
 * @property int $firm_id
 * @property int $emp_id
 * @property int $financial_year_id
 * @property float $amount
 * @property Carbon|null $payment_date
 * @property string|null $challan_no
 * @property Carbon|null $from_date
 * @property Carbon|null $to_date
 * @property string|null $payment_type
 * @property string|null $paid_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 */
class TaxPayment extends Model
{
    use SoftDeletes;

    protected $table = 'tax_payments';

    protected $casts = [
        'firm_id' => 'int',
        'emp_id' => 'int',
        'financial_year_id' => 'int',
        'amount' => 'float',
        'payment_date' => 'datetime',
        'from_date' => 'datetime',
        'to_date' => 'datetime',
    ];

    protected $fillable = [
        'firm_id',
        'emp_id',
        'financial_year_id',
        'amount',
        'payment_date',
        'challan_no',
        'from_date',
        'to_date',
        'payment_type',
        'paid_by',
    ];
}


