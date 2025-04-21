<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Saas;

use Illuminate\Database\Eloquent\Model;

/**
 * Class ActionRole
 * 
 * @property int $id
 * @property int $firm_id
 * @property int $role_id
 * @property int $action_id
 * @property string|null $records_scope
 * 
 * @property Action $action
 * @property Firm $firm
 * @property Role $role
 *
 * @package App\Models\Saas
 */
class ActionRole extends Model
{
	protected $table = 'action_role';
	public $timestamps = false;

	protected $casts = [
		'firm_id' => 'int',
		'role_id' => 'int',
		'action_id' => 'int'
	];

	protected $fillable = [
		'firm_id',
		'role_id',
		'action_id',
		'records_scope'
	];

	public function action()
	{
		return $this->belongsTo(Action::class);
	}

	public function firm()
	{
		return $this->belongsTo(Firm::class);
	}

	public function role()
	{
		return $this->belongsTo(Role::class);
	}
}
