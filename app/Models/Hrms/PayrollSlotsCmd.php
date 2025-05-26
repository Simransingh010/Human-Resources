<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Hrms;

use App\Livewire\Saas\Firms;
use App\Livewire\Saas\Users;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class PayrollSlotsCmd
 * 
 * @property int $id
 * @property int $firm_id
 * @property int $payroll_slot_id
 * @property string $payroll_slot_status
 * @property string|null $run_payroll_remarks
 * @property int|null $user_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Firm $firm
 * @property PayrollSlot $payroll_slot
 * @property User|null $user
 * @property Collection|PayrollComponentsEmployeesTrack[] $payroll_components_employees_tracks
 *
 * @package App\Models\Hrms
 */
class PayrollSlotsCmd extends Model
{
	protected $table = 'payroll_slots_cmds';

	protected $casts = [
		'firm_id' => 'int',
		'payroll_slot_id' => 'int',
		'user_id' => 'int',
         'run_payroll_remarks' => 'array',
	];



    protected $fillable = [
		'firm_id',
		'payroll_slot_id',
		'payroll_slot_status',
		'run_payroll_remarks',
		'user_id'
	];
    public const PAYROLL_SLOT_STATUS = [
        'PN'=> 'Pending',
        'ND' => 'Not Due',
        'NX' => 'Next Due',
        'ST' => 'Started',
        'HT' => 'Halted',
        'SP' => 'Suspended',
        'CM' => 'Completed',
        "IP" =>"In Progress"
    ];

	public function firm()
	{
		return $this->belongsTo(Firms::class);
	}

	public function payroll_slot()
	{
		return $this->belongsTo(PayrollSlot::class);
	}

	public function user()
	{
		return $this->belongsTo(Users::class);
	}

	public function payroll_components_employees_tracks()
	{
		return $this->hasMany(PayrollComponentsEmployeesTrack::class);
	}

    public function getPayrollSlotStatusLabelAttribute($value)
    {
        return static::PAYROLL_SLOT_STATUS[$this->payroll_slot_status] ?? null;
    }
}
