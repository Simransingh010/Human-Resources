<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Hrms;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class SalaryDisbursementBatch
 * 
 * @property int $id
 * @property int $firm_id
 * @property Carbon $transaction_date
 * @property float $amount
 * @property string|null $memo
 * @property string $mode
 * @property int $firm_bank_account_id
 * @property bool $is_inactive
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property FirmBankAccount $firm_bank_account
 * @property Firm $firm
 * @property Collection|EmployeePaidSalaryComponent[] $employee_paid_salary_components
 *
 * @package App\Models\Hrms
 */
class SalaryDisbursementBatch extends Model
{
	use SoftDeletes;
	protected $table = 'salary_disbursement_batches';

	protected $casts = [
		'firm_id' => 'int',
		'transaction_date' => 'datetime',
		'amount' => 'float',
		'firm_bank_account_id' => 'int',
		'is_inactive' => 'bool'
	];

	protected $fillable = [
		'firm_id',
		'transaction_date',
		'amount',
		'memo',
		'mode',
		'firm_bank_account_id',
		'is_inactive'
	];

	public function firm_bank_account()
	{
		return $this->belongsTo(FirmBankAccount::class);
	}

	public function firm()
	{
		return $this->belongsTo(Firm::class);
	}

	public function employee_paid_salary_components()
	{
		return $this->hasMany(EmployeePaidSalaryComponent::class);
	}
}
