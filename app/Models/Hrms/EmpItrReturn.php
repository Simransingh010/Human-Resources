<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Hrms;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class EmpItrReturn
 *
 * @property int $id
 * @property int $firm_id
 * @property int $emp_id
 * @property int $financial_year_id
 * @property string $itr_type
 * @property Carbon|null $date_filed
 * @property string|null $acknowledgement_no
 * @property array|null $filling_json
 * @property string $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 */
class EmpItrReturn extends Model
{
    use SoftDeletes;

    protected $table = 'emp_itr_returns';

    protected $casts = [
        'firm_id' => 'int',
        'emp_id' => 'int',
        'financial_year_id' => 'int',
        'date_filed' => 'datetime',
        'filling_json' => 'array',
    ];

    protected $fillable = [
        'firm_id',
        'emp_id',
        'financial_year_id',
        'itr_type',
        'date_filed',
        'acknowledgement_no',
        'filling_json',
        'status',
    ];

    public const ITR_TYPE_SELECT = [
		'ITR-1' => 'ITR-1',
		'ITR-2' => 'ITR-2',
		'ITR-3' => 'ITR-3',
		'ITR-4' => 'ITR-4',
		'ITR-5' => 'ITR-5',
	];

    public const STATUS_SELECT = [
		'filled' => 'Filled',
		'processed' => 'Processed',
		'adjusted' => 'Adjusted',
		'underscrutiny' => 'Underscrutiny',
	];
}


