<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Hrms;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class LossCarryForward
 *
 * @property int $id
 * @property int $firm_id
 * @property int $emp_id
 * @property int $financial_year_id
 * @property string $loss_type
 * @property float|null $original_loss_amount
 * @property float|null $setoff_in_current_year
 * @property float|null $carry_forward_amount
 * @property string|null $carry_forward_upto_year
 * @property int|null $emp_tax_declaration_id
 * @property int|null $itr_id
 * @property string|null $remarks
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 */
class LossCarryForward extends Model
{
    use SoftDeletes;

    protected $table = 'loss_carry_forwards';

    protected $casts = [
        'firm_id' => 'int',
        'emp_id' => 'int',
        'financial_year_id' => 'int',
        'original_loss_amount' => 'float',
        'setoff_in_current_year' => 'float',
        'carry_forward_amount' => 'float',
        'emp_tax_declaration_id' => 'int',
        'itr_id' => 'int',
    ];

    protected $fillable = [
        'firm_id',
        'emp_id',
        'financial_year_id',
        'loss_type',
        'original_loss_amount',
        'setoff_in_current_year',
        'carry_forward_amount',
        'carry_forward_upto_year',
        'emp_tax_declaration_id',
        'itr_id',
        'remarks',
    ];
}


