<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Hrms;

use App\Livewire\Saas\Firms;
use App\Livewire\Saas\Users;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class PayrollStepPayrollSlotCmd
 * 
 * @property int $id
 * @property int $firm_id
 * @property int $payroll_step_payroll_slot_id
 * @property string $payroll_step_status
 * @property string|null $step_remarks
 * @property int|null $user_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Firm $firm
 * @property PayrollStepPayrollSlot $payroll_step_payroll_slot
 * @property User|null $user
 *
 * @package App\Models\Hrms
 */
class PayrollStepPayrollSlotCmd extends Model
{
	protected $table = 'payroll_step_payroll_slot_cmds';

	protected $casts = [
		'firm_id' => 'int',
		'payroll_step_payroll_slot_id' => 'int',
		'user_id' => 'int'
	];

	protected $fillable = [
		'firm_id',
		'payroll_step_payroll_slot_id',
		'payroll_step_status',
		'step_remarks',
		'user_id'
	];

	public function firm()
	{
		return $this->belongsTo(Firms::class);
	}

	public function payroll_step_payroll_slot()
	{
		return $this->belongsTo(PayrollStepPayrollSlot::class);
	}

	public function user()
	{
		return $this->belongsTo(Users::class);
	}
}
