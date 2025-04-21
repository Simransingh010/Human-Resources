<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Hrms;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class AttendancePolicy
 *
 * @property int $id
 * @property int $firm_id
 * @property int|null $employee_id
 * @property string $camshot
 * @property string $geo
 * @property string $manual_marking
 * @property string|null $geo_validation
 * @property string|null $ip_validation
 * @property int|null $back_date_max_minutes
 * @property int|null $max_punches_a_day
 * @property string|null $grace_period_minutes
 * @property string|null $mark_absent_rule
 * @property string|null $overtime_rule
 * @property string|null $custom_rules
 * @property Carbon|null $valid_from
 * @property Carbon|null $valid_to
 * @property string|null $policy_text
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 *
 * @property Employee|null $employee
 * @property Firm $firm
 *
 * @package App\Models\Hrms
 */
class AttendancePolicy extends Model
{
	use SoftDeletes;
	protected $table = 'attendance_policies';

    public const CAMSHOT_SELECT = [
        '1' => 'Allowed',
        '2' => 'Required',
        '3' => 'Denied',
    ];

    public const GEO_SELECT = [
        '1' => 'Allowed',
        '2' => 'Required',
        '3' => 'Denied',
    ];
    public const MANUAL_MARKING_SELECT = [
        '1' => 'Allowed',
        '2' => 'Required',
        '3' => 'Denied',
    ];


	protected $casts = [
		'firm_id' => 'int',
		'employee_id' => 'int',
		'back_date_max_minutes' => 'int',
		'max_punches_a_day' => 'int',
		'valid_from' => 'datetime',
		'valid_to' => 'datetime'
	];

	protected $fillable = [
		'firm_id',
		'employee_id',
		'camshot',
		'geo',
		'manual_marking',
		'geo_validation',
		'ip_validation',
		'back_date_max_minutes',
		'max_punches_a_day',
		'grace_period_minutes',
		'mark_absent_rule',
		'overtime_rule',
		'custom_rules',
		'valid_from',
		'valid_to',
		'policy_text'
	];

	public function employee()
	{
		return $this->belongsTo(Employee::class);
	}

	public function firm()
	{
		return $this->belongsTo(Firm::class);
	}

    public function getCamshotLabelAttribute($value)
    {
        return static::CAMSHOT_SELECT[$this->camshot] ?? null;
    }
    public function getGeoLabelAttribute($value)
    {
        return static::GEO_SELECT[$this->geo] ?? null;
    }
    public function getManualMarkingLabelAttribute($value)
    {
        return static::MANUAL_MARKING_SELECT[$this->manual_marking] ?? null;
    }

}
