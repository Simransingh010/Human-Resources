<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Hrms;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;


/**
 * Class EmpPunch
 *
 * @property int $id
 * @property int $firm_id
 * @property int $employee_id
 * @property Carbon $work_date
 * @property Carbon $punch_datetime
 * @property string $in_out
 * @property string|null $punch_location
 * @property string|null $punch_type
 * @property string|null $device_id
 * @property bool $is_final
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 *
 * @property Employee $employee
 * @property Firm $firm
 *
 * @package App\Models\Hrms
 */
class EmpPunch extends Model implements HasMedia

{
	use SoftDeletes, InteractsWithMedia;

	protected $table = 'emp_punches';

	protected $casts = [
		'firm_id' => 'int',
		'employee_id' => 'int',
		'work_date' => 'datetime',
		'punch_datetime' => 'datetime',
		'is_final' => 'bool',
        'punch_geo_location' => 'array',
        'punch_details' => 'array',
	];

	protected $fillable = [
		'firm_id',
		'employee_id',
        'emp_attendance_id',
		'work_date',
		'punch_datetime',
		'in_out',
		'attend_location_id',
        'punch_geo_location',
		'punch_type',
		'device_id',
		'is_final'
	];

    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

	public function employee()
	{
		return $this->belongsTo(Employee::class);
	}

	public function firm()
	{
		return $this->belongsTo(Firm::class);
	}

	public function emp_attendance()
    {
        return $this->belongsTo(EmpAttendance::class);
    }

    public function location()
    {
        return $this->belongsTo(AttendanceLocation::class, 'attend_location_id');
    }

    public function getPunchGeoLocationAttribute($value)
    {
        return json_decode($value, true);
    }

    public function setPunchGeoLocationAttribute($value)
    {
        $this->attributes['punch_geo_location'] = json_encode($value);
    }

    public function getPunchDetailsAttribute($value)
    {
        return json_decode($value, true);
    }

    public function setPunchDetailsAttribute($value)
    {
        $this->attributes['punch_details'] = json_encode($value);
    }
}
