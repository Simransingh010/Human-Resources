<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Hrms;

use App\Models\User;
use App\Models\Saas\Firm;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Student
 *
 * @property int $id
 * @property int $firm_id
 * @property int|null $study_centre_id
 * @property int|null $user_id
 * @property string|null $fname
 * @property string|null $mname
 * @property string|null $lname
 * @property string|null $email
 * @property string|null $phone
 * @property bool $is_inactive
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 *
 * @property Firm $firm
 * @property StudyCentre|null $study_centre
 * @property Collection|StudyGroup[] $study_groups
 * @property User|null $user
 * @property Collection|StudentPersonalDetail[] $student_personal_details
 * @property Collection|StudentEducationDetail[] $student_education_details
 * @property Collection|StudentAttendance[] $student_attendances
 * @property Collection|StudentPunch[] $student_punches
 *
 * @package App\Models\Hrms
 */
class Student extends Model
{
	use SoftDeletes;
	protected $table = 'students';

	protected $casts = [
		'firm_id' => 'int',
		'study_centre_id' => 'int',
		'user_id' => 'int',
		'is_inactive' => 'bool'
	];

	protected $fillable = [
		'firm_id',
		'study_centre_id',
		'user_id',
		'fname',
		'mname',
		'lname',
		'email',
		'phone',
		'is_inactive'
	];

	public function firm()
	{
		return $this->belongsTo(Firm::class);
	}

	public function study_centre()
	{
		return $this->belongsTo(StudyCentre::class);
	}

	public function study_groups()
	{
		return $this->belongsToMany(StudyGroup::class, 'study_group_student')
			->withPivot('joined_at', 'left_at')
			->withTimestamps();
	}

	public function user()
	{
		return $this->belongsTo(User::class);
	}

	public function student_personal_detail()
	{
		return $this->hasOne(StudentPersonalDetail::class);
	}

	public function student_education_detail()
	{
		return $this->hasOne(StudentEducationDetail::class);
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

