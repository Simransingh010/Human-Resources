<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Hrms;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
 use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Class StudentPunch
 *
 * @property int $id
 * @property int|null $study_centre_id
 * @property int $student_id
 * @property int|null $student_attendance_id
 * @property Carbon $date
 * @property Carbon $punch_datetime
 * @property int|null $attendance_location_id
 * @property array|null $punch_geolocation
 * @property string|null $in_out
 * @property string|null $punch_type
 * @property string|null $device_id
 * @property array|null $punch_details
 * @property int|null $marked_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 *
 * @property StudyCentre|null $study_centre
 * @property Student $student
 * @property StudentAttendance|null $student_attendance
 * @property AttendanceLocation|null $attendance_location
 * @property User|null $marked_by_user
 *
 * @package App\Models\Hrms
 */
class StudentPunch extends Model implements HasMedia
{
	use SoftDeletes, InteractsWithMedia;

	protected $table = 'student_punches';

	protected $casts = [
		'study_centre_id' => 'int',
		'student_id' => 'int',
		'student_attendance_id' => 'int',
		'date' => 'date',
		'punch_datetime' => 'datetime',
		'attendance_location_id' => 'int',
		'punch_geolocation' => 'array',
		'punch_details' => 'array',
		'marked_by' => 'int'
	];

	protected $fillable = [
		'study_centre_id',
		'student_id',
		'student_attendance_id',
		'date',
		'punch_datetime',
		'attendance_location_id',
		'punch_geolocation',
		'in_out',
		'punch_type',
		'device_id',
		'punch_details',
		'marked_by'
	];

	protected function serializeDate(\DateTimeInterface $date)
	{
		return $date->format('Y-m-d H:i:s');
	}

	public function study_centre()
	{
		return $this->belongsTo(StudyCentre::class);
	}

	public function student()
	{
		return $this->belongsTo(Student::class);
	}

	public function student_attendance()
	{
		return $this->belongsTo(StudentAttendance::class);
	}

	public function attendance_location()
	{
		return $this->belongsTo(AttendanceLocation::class, 'attendance_location_id');
	}

	public function marked_by_user()
	{
		return $this->belongsTo(User::class, 'marked_by');
	}
 
 	/**
 	 * Register media collections for student punch.
 	 * Defines a dedicated 'selfie' collection with single-file behavior and basic conversions.
 	 */
 	public function registerMediaCollections(): void
 	{
 		$this
 			->addMediaCollection('selfie')
 			->singleFile();
 	}
 
 	/**
 	 * Optional: basic thumbnail conversion for quick previews.
 	 */
 	public function registerMediaConversions(Media $media = null): void
 	{
 		$this
 			->addMediaConversion('thumb')
 			->width(200)
 			->height(200)
 			->nonQueued();
 	}
}

