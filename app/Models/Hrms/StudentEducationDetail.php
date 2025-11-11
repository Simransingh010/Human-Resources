<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Hrms;

use App\Models\Settings\Joblocation;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class StudentEducationDetail
 *
 * @property int $id
 * @property int $student_id
 * @property string|null $student_code
 * @property Carbon|null $doh
 * @property int|null $study_centre_id
 * @property int|null $reporting_coach_id
 * @property int|null $location_id
 * @property Carbon|null $doe
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 *
 * @property Student $student
 * @property StudyCentre|null $study_centre
 * @property Employee|null $reporting_coach
 * @property Joblocation|null $location
 *
 * @package App\Models\Hrms
 */
class StudentEducationDetail extends Model
{
	use SoftDeletes;
	protected $table = 'student_education_details';

	protected $casts = [
		'student_id' => 'int',
		'study_centre_id' => 'int',
		'reporting_coach_id' => 'int',
		'location_id' => 'int',
		'doh' => 'date',
		'doe' => 'date'
	];

	protected $fillable = [
		'student_id',
		'student_code',
		'doh',
		'study_centre_id',
		'reporting_coach_id',
		'location_id',
		'doe'
	];

	public function student()
	{
		return $this->belongsTo(Student::class);
	}

	public function study_centre()
	{
		return $this->belongsTo(StudyCentre::class);
	}

	public function reporting_coach()
	{
		return $this->belongsTo(Employee::class, 'reporting_coach_id');
	}

	public function location()
	{
		return $this->belongsTo(Joblocation::class);
	}
}

