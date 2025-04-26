<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Hrms;
use App\Models\Settings\State;
use App\Models\Settings\Postoffice;
use App\Models\Settings\Subdivision;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class EmployeeAddress
 *
 * @property int $id
 * @property int $firm_id
 * @property int $employee_id
 * @property int|null $country_id
 * @property int|null $state_id
 * @property int|null $district_id
 * @property int|null $subdivision_id
 * @property int|null $city_or_village_id
 * @property int|null $postoffice_id
 * @property string $address
 * @property bool $is_primary
 * @property bool $is_permanent
 * @property bool $is_inactive
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 *
 * @property CitiesOrVillage|null $cities_or_village
 * @property Country|null $country
 * @property District|null $district
 * @property Employee $employee
 * @property Firm $firm
 * @property Postoffice|null $postoffice
 * @property State|null $state
 * @property Subdivision|null $subdivision
 *
 * @package App\Models\Hrms
 */
class EmployeeAddress extends Model
{
	use SoftDeletes;
	protected $table = 'employee_addresses';

	protected $casts = [
		'firm_id' => 'int',
		'employee_id' => 'int',
		'country_id' => 'int',
		'state_id' => 'int',
		'district_id' => 'int',
		'subdivision_id' => 'int',
		'city_or_village_id' => 'int',
		'postoffice_id' => 'int',
		'is_primary' => 'bool',
		'is_permanent' => 'bool',
		'is_inactive' => 'bool'
	];

	protected $fillable = [
		'firm_id',
		'employee_id',
		'country_id',
		'state_id',
		'district_id',
		'subdivision_id',
		'city_or_village_id',
		'postoffice_id',
		'address',
		'is_primary',
		'is_permanent',
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

	public function employee()
	{
		return $this->belongsTo(Employee::class);
	}

	public function firm()
	{
		return $this->belongsTo(Firm::class);
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
}
