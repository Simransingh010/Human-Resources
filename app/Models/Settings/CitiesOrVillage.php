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
 * Class CitiesOrVillage
 * 
 * @property int $id
 * @property int|null $firm_id
 * @property string $name
 * @property string|null $code
 * @property string|null $type
 * @property int|null $subdivision_id
 * @property int|null $district_id
 * @property bool $is_inactive
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property District|null $district
 * @property Firm|null $firm
 * @property Subdivision|null $subdivision
 * @property Collection|EmployeeAddress[] $employee_addresses
 * @property Collection|Joblocation[] $joblocations
 * @property Collection|Postoffice[] $postoffices
 *
 * @package App\Models\Settings
 */
class CitiesOrVillage extends Model
{
	use SoftDeletes;
	protected $table = 'cities_or_villages';

	protected $casts = [
		'firm_id' => 'int',
		'subdivision_id' => 'int',
		'district_id' => 'int',
		'is_inactive' => 'bool'
	];

	protected $fillable = [
		'firm_id',
		'name',
		'code',
		'type',
		'subdivision_id',
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

	public function subdivision()
	{
		return $this->belongsTo(Subdivision::class);
	}

	public function employee_addresses()
	{
		return $this->hasMany(EmployeeAddress::class, 'city_or_village_id');
	}

	public function joblocations()
	{
		return $this->hasMany(Joblocation::class, 'city_or_village_id');
	}

	public function postoffices()
	{
		return $this->hasMany(Postoffice::class, 'city_or_village_id');
	}
}
