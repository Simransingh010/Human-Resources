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
 * Class PermissionGroup
 *
 * @property int $id
 * @property int $firm_id
 * @property string $name
 * @property string|null $description
 * @property bool $is_inactive
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 *
 * @property Firm $firm
 * @property Collection|Permission[] $permissions
 * @property Collection|User[] $users
 *
 * @package App\Models\Saas
 */
class PermissionGroup extends Model
{
	use SoftDeletes,HasFactory;
	protected $table = 'permission_groups';

	protected $casts = [
		'firm_id' => 'int',
		'is_inactive' => 'bool'
	];

	protected $fillable = [
		'firm_id',
		'name',
		'description',
		'is_inactive'
	];

	public function firm()
	{
		return $this->belongsTo(Firm::class);
	}

	public function permissions()
	{
		return $this->belongsToMany(Permission::class, 'permission_group_permission')
					->withPivot('id', 'deleted_at')
					->withTimestamps();
	}

	public function users()
	{
		return $this->belongsToMany(User::class)
					->withPivot('id', 'deleted_at')
					->withTimestamps();
	}

    public function permission_group_permissions()
    {
        return $this->hasMany(PermissionGroupPermission::class);
    }
}
