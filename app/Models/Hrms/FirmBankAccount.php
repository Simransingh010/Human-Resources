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
 * Class FirmBankAccount
 * 
 * @property int $id
 * @property int $firm_id
 * @property string $account_name
 * @property string $account_number
 * @property string $bank_name
 * @property string|null $bank_address
 * @property string $ifsc_code
 * @property bool $is_inactive
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property Firm $firm
 * @property Collection|SalaryDisbursementBatch[] $salary_disbursement_batches
 *
 * @package App\Models\Hrms
 */
class FirmBankAccount extends Model
{
	use SoftDeletes;
	protected $table = 'firm_bank_accounts';

	protected $casts = [
		'firm_id' => 'int',
		'is_inactive' => 'bool'
	];

	protected $fillable = [
		'firm_id',
		'account_name',
		'account_number',
		'bank_name',
		'bank_address',
		'ifsc_code',
		'is_inactive'
	];

	public function firm()
	{
		return $this->belongsTo(Firm::class);
	}

	public function salary_disbursement_batches()
	{
		return $this->hasMany(SalaryDisbursementBatch::class);
	}
}
