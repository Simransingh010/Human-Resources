<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Saas;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class PermissionGroupUser
 * 
 * @property int $id
 * @property int $permission_group_id
 * @property int $user_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property PermissionGroup $permission_group
 * @property User $user
 *
 * @package App\Models\Saas
 */
class PermissionGroupUser extends Model
{
	use SoftDeletes;
	protected $table = 'permission_group_user';

	protected $casts = [
		'permission_group_id' => 'int',
		'user_id' => 'int'
	];

	protected $fillable = [
		'permission_group_id',
		'user_id'
	];

	public function permission_group()
	{
		return $this->belongsTo(PermissionGroup::class);
	}

	public function user()
	{
		return $this->belongsTo(User::class);
	}
}
