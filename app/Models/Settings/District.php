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
 * Class District
 * 
 * @property int $id
 * @property int|null $firm_id
 * @property string $name
 * @property string|null $code
 * @property int $state_id
 * @property bool $is_inactive
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property Firm|null $firm
 * @property State $state
 * @property Collection|CitiesOrVillage[] $cities_or_villages
 * @property Collection|EmployeeAddress[] $employee_addresses
 * @property Collection|Joblocation[] $joblocations
 * @property Collection|Subdivision[] $subdivisions
 *
 * @package App\Models\Settings
 */
class District extends Model
{
	use SoftDeletes;
	protected $table = 'districts';

	protected $casts = [
		'firm_id' => 'int',
		'state_id' => 'int',
		'is_inactive' => 'bool'
	];

	protected $fillable = [
		'firm_id',
		'name',
		'code',
		'state_id',
		'is_inactive'
	];

	public function firm()
	{
		return $this->belongsTo(Firm::class);
	}

	public function state()
	{
		return $this->belongsTo(State::class);
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

	public function subdivisions()
	{
		return $this->hasMany(Subdivision::class);
	}
}
