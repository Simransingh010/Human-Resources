<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Saas;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class UserPermission
 * 
 * @property int $id
 * @property int $user_id
 * @property int $permission_id
 * @property int|null $firm_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property Firm|null $firm
 * @property Permission $permission
 * @property User $user
 *
 * @package App\Models\Saas
 */
class UserPermission extends Model
{
	use SoftDeletes;
	protected $table = 'user_permission';

	protected $casts = [
		'user_id' => 'int',
		'permission_id' => 'int',
		'firm_id' => 'int'
	];

	protected $fillable = [
		'user_id',
		'permission_id',
		'firm_id'
	];

	public function firm()
	{
		return $this->belongsTo(Firm::class);
	}

	public function permission()
	{
		return $this->belongsTo(Permission::class);
	}

	public function user()
	{
		return $this->belongsTo(User::class);
	}
}
