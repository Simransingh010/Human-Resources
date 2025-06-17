<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Hrms;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class HolidayCalendar
 *
 * @property int $id
 * @property int $firm_id
 * @property string $title
 * @property string|null $description
 * @property bool $is_inactive
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 *
 * @property Firm $firm
 * @property Collection|Holiday[] $holidays
 *
 * @package App\Models\Hrms
 */
class HolidayCalendar extends Model
{
	use SoftDeletes;
	protected $table = 'holiday_calendars';

	protected $casts = [
		'firm_id' => 'int',
		'is_inactive' => 'bool'
	];

	protected $fillable = [
		'firm_id',
		'title',
		'description',
        'start_date',
        'end_date',
		'is_inactive'
	];
	

	public function firm()
	{
		return $this->belongsTo(Firm::class);
	}

	public function holidays()
	{
		return $this->hasMany(Holiday::class);
	}
}
