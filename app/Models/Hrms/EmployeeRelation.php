<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Hrms;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class EmployeeRelation
 * 
 * @property int $id
 * @property int $firm_id
 * @property int $employee_id
 * @property string $relation
 * @property string $person_name
 * @property string|null $occupation
 * @property Carbon|null $dob
 * @property string|null $qualification
 * @property bool $is_inactive
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property Employee $employee
 * @property Firm $firm
 *
 * @package App\Models\Hrms
 */
class EmployeeRelation extends Model
{
	use SoftDeletes;
	protected $table = 'employee_relations';

	protected $casts = [
		'firm_id' => 'int',
		'employee_id' => 'int',
		'dob' => 'datetime',
		'is_inactive' => 'bool'
	];

	protected $fillable = [
		'firm_id',
		'employee_id',
		'relation',
		'person_name',
		'occupation',
		'dob',
		'qualification',
		'is_inactive'
	];

	public const RELATION_SELECT = [
        'Spouse' => 'Spouse',
        'Child' => 'Child',
        'Parent' => 'Parent',
        'Sibling' => 'Sibling',
        'Other' => 'Other',
        'Mother' => 'Mother',
        'Father' => 'Father',
        'Son' => 'Son',
        'Daughter' => 'Daughter',
        'Brother' => 'Brother',
        'Sister' => 'Sister',
        'Brother In Law' => 'Brother In Law',
        'Sister In Law' => 'Sister In Law',
        'Mother In Law' => 'Mother In Law',
        'Father In Law' => 'Father In Law',
        'Grand Father' => 'Grand Father',
        'Grand Mother' => 'Grand Mother',
        'Relative' => 'Relative',
        'Uncle' => 'Uncle',
        'Aunt' => 'Aunt',
        'Grand Father In Law' => 'Grand Father In Law',
        'Grand Mother In Law' => 'Grand Mother In Law',
        'Spouse' => 'Spouse',
    ];

	public function employee()
	{
		return $this->belongsTo(Employee::class);
	}

	public function firm()
	{
		return $this->belongsTo(Firm::class);
	}

    public function getRelationLabelAttribute($value)
    {
        return static::RELATION_SELECT[$this->relation] ?? null;
    }
}
