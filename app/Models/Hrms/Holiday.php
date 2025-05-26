<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Hrms;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Holiday
 *
 * @property int $id
 * @property int $firm_id
 * @property int $holiday_calendar_id
 * @property string $holiday_title
 * @property string|null $holiday_desc
 * @property Carbon $start_date
 * @property Carbon|null $end_date
 * @property bool $repeat_annually
 * @property bool $is_inactive
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 *
 * @property Firm $firm
 * @property HolidayCalendar $holiday_calendar
 *
 * @package App\Models\Hrms
 */
class Holiday extends Model
{
	use SoftDeletes;
	protected $table = 'holidays';

	protected $casts = [
		'firm_id' => 'int',
		'holiday_calendar_id' => 'int',
		'repeat_annually' => 'bool',
		'is_inactive' => 'bool'
	];

	protected $fillable = [
		'firm_id',
		'holiday_calendar_id',
		'holiday_title',
		'holiday_desc',
		'start_date',
		'end_date',
		'repeat_annually',
		'is_inactive',
        'day_status_main'
	];

    public const WORK_STATUS_SELECT = [
        'F' => 'Full Working',
        'H' => 'Holiday',
        'W' => 'Week Off',
        'PW' => 'Partial Working',
        'S' => 'Suspended',
    ];

   

    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

	public function firm()
	{
		return $this->belongsTo(Firm::class);
	}

	public function holiday_calendar()
	{
		return $this->belongsTo(HolidayCalendar::class);
	}
}
