<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Hrms;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class EmpTaxDeclaration
 *
 * @property int $id
 * @property int $firm_id
 * @property int $emp_id
 * @property int $financial_year_id
 * @property int $declaration_type_id
 * @property int|null $declaration_group_id
 * @property float $declared_amount
 * @property float $approved_amount
 * @property string|null $supporting_doc
 * @property string $status
 * @property string|null $remarks
 * @property int|null $home_loan_id
 * @property int|null $hra_record_id
 * @property string|null $source
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 */
class EmpTaxDeclaration extends Model
{
    use SoftDeletes;

    protected $table = 'emp_tax_declarations';

    protected $casts = [
        'firm_id' => 'int',
        'emp_id' => 'int',
        'financial_year_id' => 'int',
        'declaration_type_id' => 'int',
        'declaration_group_id' => 'int',
        'declared_amount' => 'float',
        'approved_amount' => 'float',
        'home_loan_id' => 'int',
        'hra_record_id' => 'int',
    ];

    protected $fillable = [
        'firm_id',
        'emp_id',
        'financial_year_id',
        'declaration_type_id',
        'declaration_group_id',
        'declared_amount',
        'approved_amount',
        'supporting_doc',
        'status',
        'remarks',
        'home_loan_id',
        'hra_record_id',
        'source',
    ];
}



