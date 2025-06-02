<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Hrms;

use App\Models\Saas\Firm;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class EmpLeaveTransaction
 * 
 * @property int $id
 * @property int $leave_balance_id
 * @property string $transaction_type
 * @property Carbon $transaction_date
 * @property float $amount
 * @property int|null $reference_id
 * @property int $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property int $firm_id
 * 
 * @property User $user
 * @property Firm $firm
 * @property EmpLeaveBalance $emp_leave_balance
 *
 * @package App\Models\Hrms
 */
class EmpLeaveTransaction extends Model
{
	protected $table = 'emp_leave_transactions';

	protected $casts = [
		'leave_balance_id' => 'int',
		'transaction_date' => 'datetime',
		'amount' => 'float',
		'reference_id' => 'int',
		'created_by' => 'int',
		'firm_id' => 'int'
	];

	protected $fillable = [
		'leave_balance_id',
		'transaction_type',
		'transaction_date',
		'amount',
		'reference_id',
		'created_by',
		'firm_id'
	];

	public function user()
	{
		return $this->belongsTo(User::class, 'created_by');
	}

	public function firm()
	{
		return $this->belongsTo(Firm::class);
	}

	public function emp_leave_balance()
	{
		return $this->belongsTo(EmpLeaveBalance::class, 'leave_balance_id');
	}
}
