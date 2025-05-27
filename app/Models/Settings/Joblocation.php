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
 * Class Joblocation
 * 
 * @property int $id
 * @property int|null $firm_id
 * @property string $name
 * @property string|null $code
 * @property string|null $description
 * @property int|null $parent_joblocation_id
 * @property int|null $country_id
 * @property int|null $state_id
 * @property int|null $district_id
 * @property int|null $subdivision_id
 * @property int|null $city_or_village_id
 * @property int|null $postoffice_id
 * @property bool $is_inactive
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property CitiesOrVillage|null $cities_or_village
 * @property Country|null $country
 * @property District|null $district
 * @property Firm|null $firm
 * @property Joblocation|null $joblocation
 * @property Postoffice|null $postoffice
 * @property State|null $state
 * @property Subdivision|null $subdivision
 * @property Collection|EmployeeJobProfile[] $employee_job_profiles
 * @property Collection|Joblocation[] $joblocations
 *
 * @package App\Models\Settings
 */
class Joblocation extends Model
{
	use SoftDeletes;
	protected $table = 'joblocations';

	protected $casts = [
		'firm_id' => 'int',
		'parent_joblocation_id' => 'int',
		'country_id' => 'int',
		'state_id' => 'int',
		'district_id' => 'int',
		'subdivision_id' => 'int',
		'city_or_village_id' => 'int',
		'postoffice_id' => 'int',
		'is_inactive' => 'bool'
	];

	protected $fillable = [
		'firm_id',
		'name',
		'code',
		'description',
		'parent_joblocation_id',
		'country_id',
		'state_id',
		'district_id',
		'subdivision_id',
		'city_or_village_id',
		'postoffice_id',
		'is_inactive'
	];

	public function cities_or_village()
	{
		return $this->belongsTo(CitiesOrVillage::class, 'city_or_village_id');
	}

	public function country()
	{
		return $this->belongsTo(Country::class);
	}

	public function district()
	{
		return $this->belongsTo(District::class);
	}

	public function firm()
	{
		return $this->belongsTo(Firm::class);
	}

	public function joblocation()
	{
		return $this->belongsTo(Joblocation::class, 'parent_joblocation_id');
	}

	public function postoffice()
	{
		return $this->belongsTo(Postoffice::class);
	}

	public function state()
	{
		return $this->belongsTo(State::class);
	}

	public function subdivision()
	{
		return $this->belongsTo(Subdivision::class);
	}

	public function employee_job_profiles()
	{
		return $this->hasMany(EmployeeJobProfile::class);
	}

	public function joblocations()
	{
		return $this->hasMany(Joblocation::class, 'parent_joblocation_id');
	}
}
