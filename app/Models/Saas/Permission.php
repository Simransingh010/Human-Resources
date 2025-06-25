<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Saas;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class Permission
 *
 * @property int $id
 * @property int $app_module_id
 * @property string $name
 * @property string|null $code
 * @property string|null $description
 * @property string|null $icon
 * @property string|null $route
 * @property string|null $color
 * @property string|null $tooltip
 * @property int $order
 * @property string|null $badge
 * @property string|null $custom_css
 * @property bool $is_inactive
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 *
 * @property AppModule $app_module
 * @property Collection|PermissionGroupPermission[] $permission_group_permissions
 * @property Collection|User[] $users
 *
 * @package App\Models\Saas
 */
class Permission extends Model
{
	use SoftDeletes,HasFactory;
	protected $table = 'permissions';

	protected $casts = [
		'app_module_id' => 'int',
		'order' => 'int',
		'is_inactive' => 'bool'
	];

	protected $fillable = [
		'app_module_id',
		'name',
		'code',
        'title',
		'description',
		'icon',
		'route',
		'color',
		'tooltip',
		'order',
		'badge',
		'custom_css',
		'is_inactive'
	];

	public function app_module()
	{
		return $this->belongsTo(AppModule::class);
	}

	public function permission_group_permissions()
	{
		return $this->hasMany(PermissionGroupPermission::class);
	}

	public function users()
	{
		return $this->belongsToMany(User::class, 'user_permission')
					->withPivot('id', 'firm_id', 'deleted_at')
					->withTimestamps();
	}
}
