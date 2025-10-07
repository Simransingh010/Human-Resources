<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Hrms;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class TaxRebate
 *
 * @property int $id
 * @property int $financial_year_id
 * @property int $tax_regime_id
 * @property float|null $taxable_income_lim
 * @property float|null $max_rebate_amount
 * @property int|null $section_code
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 */
class TaxRebate extends Model
{
    use SoftDeletes;

    protected $table = 'tax_rebates';

    protected $casts = [
        'financial_year_id' => 'int',
        'tax_regime_id' => 'int',
        'taxable_income_lim' => 'float',
        'max_rebate_amount' => 'float',
        'section_code' => 'int',
    ];

    protected $fillable = [
        'financial_year_id',
        'tax_regime_id',
        'taxable_income_lim',
        'max_rebate_amount',
        'section_code',
    ];
}


