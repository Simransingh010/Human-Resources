<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Hrms;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class EmpHomeLoanRecord
 *
 * @property int $id
 * @property int $firm_id
 * @property int $emp_id
 * @property int $financial_year_id
 * @property string|null $lender_name
 * @property float $outstanding_principle
 * @property float $interest_paid
 * @property string|null $property_status
 * @property Carbon|null $from_date
 * @property Carbon|null $to_date
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 */
class EmpHomeLoanRecord extends Model
{
    use SoftDeletes;

    protected $table = 'emp_home_loan_records';

    protected $casts = [
        'firm_id' => 'int',
        'emp_id' => 'int',
        'financial_year_id' => 'int',
        'outstanding_principle' => 'float',
        'interest_paid' => 'float',
        'from_date' => 'datetime',
        'to_date' => 'datetime',
    ];

    protected $fillable = [
        'firm_id',
        'emp_id',
        'financial_year_id',
        'lender_name',
        'outstanding_principle',
        'interest_paid',
        'property_status',
        'from_date',
        'to_date',
    ];

    public const PROPERTY_STATUS_SELECT = [
		'self_occupied' => 'Self Occupied',
		'rented' => 'Rented',
		'other' => 'Other',
	];

}



