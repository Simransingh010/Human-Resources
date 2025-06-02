<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Saas;

use Illuminate\Database\Eloquent\Model;

/**
 * Class RoleUser
 * 
 * @property int $id
 * @property int $firm_id
 * @property int $user_id
 * @property int $role_id
 * 
 * @property Firm $firm
 * @property Role $role
 * @property User $user
 *
 * @package App\Models\Saas
 */
class RoleUser extends Model
{
	protected $table = 'role_user';
	public $timestamps = false;

	protected $casts = [
		'firm_id' => 'int',
		'user_id' => 'int',
		'role_id' => 'int'
	];

	protected $fillable = [
		'firm_id',
		'user_id',
		'role_id'
	];

	public function firm()
	{
		return $this->belongsTo(Firm::class);
	}

	public function role()
	{
		return $this->belongsTo(Role::class);
	}

	public function user()
	{
		return $this->belongsTo(User::class);
	}
}
