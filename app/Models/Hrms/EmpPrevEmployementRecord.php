<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Hrms;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class EmpPrevEmployementRecord
 *
 * @property int $id
 * @property int $firm_id
 * @property int $emp_id
 * @property float $gross_salary
 * @property float $tds_deducted
 * @property Carbon|null $salary_start_date
 * @property Carbon|null $salary_end_date
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 */
class EmpPrevEmployementRecord extends Model
{
    use SoftDeletes;

    protected $table = 'emp_prev_employement_record';

    protected $casts = [
        'firm_id' => 'int',
        'emp_id' => 'int',
        'gross_salary' => 'float',
        'tds_deducted' => 'float',
        'salary_start_date' => 'datetime',
        'salary_end_date' => 'datetime',
    ];

    protected $fillable = [
        'firm_id',
        'emp_id',
        'gross_salary',
        'tds_deducted',
        'salary_start_date',
        'salary_end_date',
    ];
}


