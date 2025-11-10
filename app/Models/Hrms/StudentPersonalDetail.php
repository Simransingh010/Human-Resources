<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Hrms;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class StudentPersonalDetail
 *
 * @property int $id
 * @property int $student_id
 * @property string|null $gender
 * @property string|null $fathername
 * @property string|null $mothername
 * @property string|null $mobile_number
 * @property Carbon|null $dob
 * @property Carbon|null $admission_date
 * @property string|null $marital_status
 * @property Carbon|null $doa
 * @property string|null $nationality
 * @property string|null $adharno
 * @property string|null $panno
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 *
 * @property Student $student
 *
 * @package App\Models\Hrms
 */
class StudentPersonalDetail extends Model
{
	use SoftDeletes;
	protected $table = 'student_personal_details';

	protected $casts = [
		'student_id' => 'int',
		'dob' => 'date',
		'admission_date' => 'date',
		'doa' => 'date'
	];

	protected $fillable = [
		'student_id',
		'gender',
		'fathername',
		'mothername',
		'mobile_number',
		'dob',
		'admission_date',
		'marital_status',
		'doa',
		'nationality',
		'adharno',
		'panno'
	];

	public function student()
	{
		return $this->belongsTo(Student::class);
	}
}

