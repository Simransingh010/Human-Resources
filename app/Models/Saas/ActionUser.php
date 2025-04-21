<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Saas;

use Illuminate\Database\Eloquent\Model;

/**
 * Class ActionUser
 * 
 * @property int $id
 * @property int $firm_id
 * @property int $user_id
 * @property int $action_id
 * @property string|null $records_scope
 * 
 * @property Action $action
 * @property Firm $firm
 * @property User $user
 *
 * @package App\Models\Saas
 */
class ActionUser extends Model
{
	protected $table = 'action_user';
	public $timestamps = false;

	protected $casts = [
		'firm_id' => 'int',
		'user_id' => 'int',
		'action_id' => 'int'
	];

	protected $fillable = [
		'firm_id',
		'user_id',
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

	public function user()
	{
		return $this->belongsTo(User::class);
	}
}
