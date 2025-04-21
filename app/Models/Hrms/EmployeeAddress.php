<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Hrms;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class EmployeeAddress
 * 
 * @property int $id
 * @property int $firm_id
 * @property int $employee_id
 * @property string $country
 * @property string $state
 * @property string $city
 * @property string|null $town
 * @property string|null $postoffice
 * @property string|null $village
 * @property string $pincode
 * @property string $address
 * @property bool $is_primary
 * @property bool $is_permanent
 * @property bool $is_inactive
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property Employee $employee
 * @property Firm $firm
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
		'is_primary' => 'bool',
		'is_permanent' => 'bool',
		'is_inactive' => 'bool'
	];

	protected $fillable = [
		'firm_id',
		'employee_id',
		'country',
		'state',
		'city',
		'town',
		'postoffice',
		'village',
		'pincode',
		'address',
		'is_primary',
		'is_permanent',
		'is_inactive'
	];

	public function employee()
	{
		return $this->belongsTo(Employee::class);
	}

	public function firm()
	{
		return $this->belongsTo(Firm::class);
	}
}
