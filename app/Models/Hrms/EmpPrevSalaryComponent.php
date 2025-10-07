<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Hrms;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class EmpPrevSalaryComponent
 *
 * @property int $id
 * @property int $firm_id
 * @property int $emp_id
 * @property int $salary_component_id
 * @property string|null $salary_period_from
 * @property string|null $salary_period_to
 * @property int|null $emp_prev_employement_records_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 */
class EmpPrevSalaryComponent extends Model
{
    use SoftDeletes;

    protected $table = 'emp_prev_salary_components';

    protected $casts = [
        'firm_id' => 'int',
        'emp_id' => 'int',
        'salary_component_id' => 'int',
        'emp_prev_employement_records_id' => 'int',
    ];

    protected $fillable = [
        'firm_id',
        'emp_id',
        'salary_component_id',
        'salary_period_from',
        'salary_period_to',
        'emp_prev_employement_records_id',
    ];
}


