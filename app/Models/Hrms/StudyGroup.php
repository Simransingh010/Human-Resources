<?php

namespace App\Models\Hrms;

use App\Models\Saas\Firm;
use Illuminate\Database\Eloquent\Model;

class StudyGroup extends Model
{
	protected $table = 'study_groups';

	protected $casts = [
		'firm_id' => 'int',
		'study_centre_id' => 'int',
		'coach_id' => 'int',
		'is_active' => 'bool',
	];

	protected $fillable = [
		'firm_id',
		'study_centre_id',
		'name',
		'coach_id',
		'is_active',
	];

	public function firm()
	{
		return $this->belongsTo(Firm::class);
	}

	public function study_centre()
	{
		return $this->belongsTo(StudyCentre::class);
	}

	public function coach()
	{
		return $this->belongsTo(Employee::class, 'coach_id');
	}

	public function students()
	{
		return $this->belongsToMany(Student::class, 'study_group_student')
			->withPivot('joined_at', 'left_at')
			->withTimestamps();
	}
}


