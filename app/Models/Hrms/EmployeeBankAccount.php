<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Hrms;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class EmployeeBankAccount
 * 
 * @property int $id
 * @property int $firm_id
 * @property int $employee_id
 * @property string $bank_name
 * @property string $branch_name
 * @property string|null $address
 * @property string $ifsc
 * @property string $bankaccount
 * @property bool $is_primary
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
class EmployeeBankAccount extends Model
{
	use SoftDeletes;
	protected $table = 'employee_bank_accounts';

	protected $casts = [
		'firm_id' => 'int',
		'employee_id' => 'int',
		'is_primary' => 'bool',
		'is_inactive' => 'bool'
	];

	protected $fillable = [
		'firm_id',
		'employee_id',
		'bank_name',
		'branch_name',
		'address',
		'ifsc',
		'bankaccount',
		'is_primary',
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
