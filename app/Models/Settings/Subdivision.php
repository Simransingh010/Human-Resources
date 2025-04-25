<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Settings;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Subdivision
 * 
 * @property int $id
 * @property int|null $firm_id
 * @property string $name
 * @property string|null $code
 * @property string|null $type
 * @property int $district_id
 * @property bool $is_inactive
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property District $district
 * @property Firm|null $firm
 * @property Collection|CitiesOrVillage[] $cities_or_villages
 * @property Collection|EmployeeAddress[] $employee_addresses
 * @property Collection|Joblocation[] $joblocations
 *
 * @package App\Models\Settings
 */
class Subdivision extends Model
{
	use SoftDeletes;
	protected $table = 'subdivisions';

	protected $casts = [
		'firm_id' => 'int',
		'district_id' => 'int',
		'is_inactive' => 'bool'
	];

	protected $fillable = [
		'firm_id',
		'name',
		'code',
		'type',
		'district_id',
		'is_inactive'
	];

	public function district()
	{
		return $this->belongsTo(District::class);
	}

	public function firm()
	{
		return $this->belongsTo(Firm::class);
	}

	public function cities_or_villages()
	{
		return $this->hasMany(CitiesOrVillage::class);
	}

	public function employee_addresses()
	{
		return $this->hasMany(EmployeeAddress::class);
	}

	public function joblocations()
	{
		return $this->hasMany(Joblocation::class);
	}
}
