<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Hrms;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class SalaryChangeEmployee
 * 
 * @property int $id
 * @property int $firm_id
 * @property int $employee_id
 * @property int $old_salary_components_employee_id
 * @property int $new_salary_components_employee_id
 * @property Carbon $old_effective_to
 * @property string|null $remarks
 * @property array $changes_details_json
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Employee $employee
 * @property Firm $firm
 * @property SalaryComponentsEmployee $old_salary_components
 * @property SalaryComponentsEmployee $new_salary_components
 *
 * @package App\Models\Hrms
 */
class SalaryChangeEmployee extends Model
{
    protected $table = 'salary_changes_employees';

    protected $casts = [
        'firm_id' => 'int',
        'employee_id' => 'int',
        'old_salary_components_employee_id' => 'int',
        'new_salary_components_employee_id' => 'int',
        'old_effective_to' => 'date',
        'changes_details_json' => 'array'
    ];

    protected $fillable = [
        'firm_id',
        'employee_id',
        'old_salary_components_employee_id',
        'new_salary_components_employee_id',
        'old_effective_to',
        'remarks',
        'changes_details_json'
    ];

    public function getCreatedAtAttribute($value)
    {
        return $this->asDateTime($value)->format('Y-m-d H:i:s');
    }

    public function getUpdatedAtAttribute($value)
    {
        return $this->asDateTime($value)->format('Y-m-d H:i:s');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function firm()
    {
        return $this->belongsTo(Firm::class);
    }

    public function old_salary_components()
    {
        return $this->belongsTo(SalaryComponentsEmployee::class, 'old_salary_components_employee_id');
    }

    public function new_salary_components()
    {
        return $this->belongsTo(SalaryComponentsEmployee::class, 'new_salary_components_employee_id');
    }
} 