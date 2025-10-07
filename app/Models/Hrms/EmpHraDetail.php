<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Hrms;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class EmpHraDetail
 *
 * @property int $id
 * @property int $firm_id
 * @property int $emp_id
 * @property string|null $land_lord_name
 * @property string|null $landlord_pan
 * @property float $monthly_rent
 * @property Carbon|null $from_date
 * @property Carbon|null $to_date
 * @property string $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 */
class EmpHraDetail extends Model
{
    use SoftDeletes;

    protected $table = 'emp_hra_details';

    protected $casts = [
        'firm_id' => 'int',
        'emp_id' => 'int',
        'monthly_rent' => 'float',
        'from_date' => 'datetime',
        'to_date' => 'datetime',
    ];

    protected $fillable = [
        'firm_id',
        'emp_id',
        'land_lord_name',
        'landlord_pan',
        'monthly_rent',
        'from_date',
        'to_date',
        'status',
    ];
}


