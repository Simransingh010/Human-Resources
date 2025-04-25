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
 * Class Postoffice
 * 
 * @property int $id
 * @property int|null $firm_id
 * @property string $name
 * @property string|null $code
 * @property int $city_or_village_id
 * @property string|null $pincode
 * @property bool $is_inactive
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property CitiesOrVillage $cities_or_village
 * @property Firm|null $firm
 * @property Collection|EmployeeAddress[] $employee_addresses
 * @property Collection|Joblocation[] $joblocations
 *
 * @package App\Models\Settings
 */
class Postoffice extends Model
{
	use SoftDeletes;
	protected $table = 'postoffices';

	protected $casts = [
		'firm_id' => 'int',
		'city_or_village_id' => 'int',
		'is_inactive' => 'bool'
	];

	protected $fillable = [
		'firm_id',
		'name',
		'code',
		'city_or_village_id',
		'pincode',
		'is_inactive'
	];

	public function cities_or_village()
	{
		return $this->belongsTo(CitiesOrVillage::class, 'city_or_village_id');
	}

	public function firm()
	{
		return $this->belongsTo(Firm::class);
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
