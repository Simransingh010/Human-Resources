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
 * Class State
 * 
 * @property int $id
 * @property int|null $firm_id
 * @property string $name
 * @property string|null $code
 * @property int $country_id
 * @property bool $is_inactive
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property Country $country
 * @property Firm|null $firm
 * @property Collection|District[] $districts
 * @property Collection|EmployeeAddress[] $employee_addresses
 * @property Collection|Joblocation[] $joblocations
 *
 * @package App\Models\Settings
 */
class State extends Model
{
	use SoftDeletes;
	protected $table = 'states';

	protected $casts = [
		'firm_id' => 'int',
		'country_id' => 'int',
		'is_inactive' => 'bool'
	];

	protected $fillable = [
		'firm_id',
		'name',
		'code',
		'country_id',
		'is_inactive'
	];

	public function country()
	{
		return $this->belongsTo(Country::class);
	}

	public function firm()
	{
		return $this->belongsTo(Firm::class);
	}

	public function districts()
	{
		return $this->hasMany(District::class);
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
