<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Hrms;

use App\Models\Saas\Firm;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class StudyCentre
 *
 * @property int $id
 * @property int $firm_id
 * @property string $name
 * @property string|null $code
 * @property int|null $established_year
 * @property string|null $address
 * @property string|null $city
 * @property string|null $state
 * @property string|null $country
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $website
 * @property bool $is_inactive
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 *
 * @property Firm $firm
 * @property Collection|StudyGroup[] $study_groups
 * @property Collection|Student[] $students
 * @property Collection|StudentEducationDetail[] $student_education_details
 * @property Collection|StudentAttendance[] $student_attendances
 * @property Collection|StudentPunch[] $student_punches
 *
 * @package App\Models\Hrms
 */
class StudyCentre extends Model
{
	use SoftDeletes;
	protected $table = 'study_centres';

	protected $casts = [
		'firm_id' => 'int',
		'established_year' => 'int',
		'is_inactive' => 'bool'
	];

	protected $fillable = [
		'firm_id',
		'name',
		'code',
		'established_year',
		'address',
		'city',
		'state',
		'country',
		'phone',
		'email',
		'website',
		'is_inactive'
	];

	public function firm()
	{
		return $this->belongsTo(Firm::class);
	}



	public function study_groups()
	{
		return $this->hasMany(StudyGroup::class);
	}

	public function students()
	{
		return $this->hasMany(Student::class);
	}

	public function student_education_details()
	{
		return $this->hasMany(StudentEducationDetail::class);
	}

	public function student_attendances()
	{
		return $this->hasMany(StudentAttendance::class);
	}

	public function student_punches()
	{
		return $this->hasMany(StudentPunch::class);
	}
}

