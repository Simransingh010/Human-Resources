<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Saas;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Role
 * 
 * @property int $id
 * @property int $firm_id
 * @property string $name
 * @property string|null $description
 * @property bool $is_inactive
 * @property string|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Firm $firm
 * @property Collection|Action[] $actions
 * @property Collection|User[] $users
 *
 * @package App\Models\Saas
 */
class Role extends Model
{
	use SoftDeletes;
	protected $table = 'roles';

	protected $casts = [
		'firm_id' => 'integer',
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

	public function actions()
	{
		return $this->belongsToMany(Action::class)
					->withPivot('id', 'firm_id', 'records_scope');
	}

	public function users()
	{
		return $this->belongsToMany(User::class)
					->withPivot('id', 'firm_id');
	}

	public function permissions()
	{
		return $this->belongsToMany(Permission::class, 'permission_role');
	}
}
