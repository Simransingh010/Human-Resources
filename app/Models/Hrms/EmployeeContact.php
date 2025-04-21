<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Hrms;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class EmployeeContact
 * 
 * @property int $id
 * @property int $firm_id
 * @property int $employee_id
 * @property string $contact_type
 * @property string $contact_value
 * @property string|null $contact_person
 * @property string|null $relation
 * @property bool $is_primary
 * @property bool $is_for_emergency
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
class EmployeeContact extends Model
{
	use SoftDeletes;
	protected $table = 'employee_contacts';

	protected $casts = [
		'firm_id' => 'int',
		'employee_id' => 'int',
		'is_primary' => 'bool',
		'is_for_emergency' => 'bool',
		'is_inactive' => 'bool'
	];

	protected $fillable = [
		'firm_id',
		'employee_id',
		'contact_type',
		'contact_value',
		'contact_person',
		'relation',
		'is_primary',
		'is_for_emergency',
		'is_inactive'
	];

	public const CONTACT_TYPE_SELECT = [
        'phone' => 'Phone',
        'whatsApp' => 'WhatsApp',
        'email' => 'Email',
        'linkedIn' => 'LinkedIn',
        'skype' => 'Skype',
    ];

	public function employee()
	{
		return $this->belongsTo(Employee::class);
	}

	public function firm()
	{
		return $this->belongsTo(Firm::class);
	}

	public function getContactTypeLabelAttribute($value)
    {
        return static::CONTACT_TYPE_SELECT[$this->contact_type] ?? null;
    }
}
