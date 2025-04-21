<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Settings;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Department
 * 
 * @property int $id
 * @property int $firm_id
 * @property string $title
 * @property string $code
 * @property string|null $description
 * @property int|null $parent_department_id
 * @property bool $is_inactive
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property Firm $firm
 * @property Department|null $department
 * @property Collection|Department[] $departments
 * @property Collection|Designation[] $designations
 * @property Collection|EmployeeJobProfile[] $employee_job_profiles
 *
 * @package App\Models\Settings
 */
class Department extends Model
{
	use SoftDeletes;
	protected $table = 'departments';

	protected $casts = [
		'firm_id' => 'int',
		'parent_department_id' => 'int',
		'is_inactive' => 'bool'
	];

	protected $fillable = [
		'firm_id',
		'title',
		'code',
		'description',
		'parent_department_id',
		'is_inactive'
	];

	public function firm()
	{
		return $this->belongsTo(Firm::class);
	}

	public function department()
	{
		return $this->belongsTo(Department::class, 'parent_department_id');
	}

	public function departments()
	{
		return $this->hasMany(Department::class, 'parent_department_id');
	}

	public function designations()
	{
		return $this->belongsToMany(Designation::class, 'departments_designations')
					->withPivot('id', 'firm_id', 'deleted_at')
					->withTimestamps();
	}

	public function employee_job_profiles()
	{
		return $this->hasMany(EmployeeJobProfile::class);
	}
}
