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
 * Class Country
 * 
 * @property int $id
 * @property int|null $firm_id
 * @property string $name
 * @property string|null $code
 * @property bool $is_inactive
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property Firm|null $firm
 * @property Collection|EmployeeAddress[] $employee_addresses
 * @property Collection|Joblocation[] $joblocations
 * @property Collection|State[] $states
 *
 * @package App\Models\Settings
 */
class Country extends Model
{
	use SoftDeletes;
	protected $table = 'countries';

	protected $casts = [
		'firm_id' => 'int',
		'is_inactive' => 'bool'
	];

	protected $fillable = [
		'firm_id',
		'name',
		'code',
		'is_inactive'
	];

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

	public function states()
	{
		return $this->hasMany(State::class);
	}
}
