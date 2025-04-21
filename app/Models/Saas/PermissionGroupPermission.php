<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Saas;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class PermissionGroupPermission
 * 
 * @property int $id
 * @property int $permission_group_id
 * @property int $permission_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property PermissionGroup $permission_group
 * @property Permission $permission
 *
 * @package App\Models\Saas
 */
class PermissionGroupPermission extends Model
{
	use SoftDeletes;
	protected $table = 'permission_group_permission';

	protected $casts = [
		'permission_group_id' => 'int',
		'permission_id' => 'int'
	];

	protected $fillable = [
		'permission_group_id',
		'permission_id'
	];

	public function permission_group()
	{
		return $this->belongsTo(PermissionGroup::class);
	}

	public function permission()
	{
		return $this->belongsTo(Permission::class);
	}
}
