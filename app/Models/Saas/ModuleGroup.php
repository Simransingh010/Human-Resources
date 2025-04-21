<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Saas;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class ModuleGroup
 * 
 * @property int $id
 * @property int $app_id
 * @property string $name
 * @property string|null $description
 * @property bool $is_inactive
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property App $app
 * @property Collection|AppModule[] $app_modules
 *
 * @package App\Models\Saas
 */
class ModuleGroup extends Model
{
	use SoftDeletes;
	protected $table = 'module_groups';

	protected $casts = [
		'app_id' => 'int',
		'is_inactive' => 'bool'
	];

	protected $fillable = [
		'app_id',
		'name',
		'description',
		'is_inactive'
	];

	public function app()
	{
		return $this->belongsTo(App::class);
	}

	public function app_modules()
	{
		return $this->hasMany(AppModule::class);
	}
}
