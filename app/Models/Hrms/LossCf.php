<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Hrms;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class LossCf
 *
 * @property int $id
 * @property int $firm_id
 * @property int|null $emp_id
 * @property int $financial_year_id
 * @property float $original_loss_amount
 * @property float $setoff_in_current_year
 * @property float $carry_forward_amount
 * @property int $forward_upto_year
 * @property int|null $declaration_id
 * @property int|null $itr_id
 * @property string|null $remarks
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 */
class LossCf extends Model
{
    use SoftDeletes;

    protected $table = 'loss_cf';

    protected $casts = [
        'firm_id' => 'int',
        'emp_id' => 'int',
        'financial_year_id' => 'int',
        'original_loss_amount' => 'float',
        'setoff_in_current_year' => 'float',
        'carry_forward_amount' => 'float',
        'forward_upto_year' => 'int',
        'declaration_id' => 'int',
        'itr_id' => 'int',
        'created_by' => 'int',
        'updated_by' => 'int',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $fillable = [
        'firm_id',
        'emp_id',
        'financial_year_id',
        'original_loss_amount',
        'setoff_in_current_year',
        'carry_forward_amount',
        'forward_upto_year',
        'declaration_id',
        'itr_id',
        'remarks',
        'created_by',
        'updated_by',
    ];
}


