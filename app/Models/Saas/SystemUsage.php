<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Saas;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class SystemUsage
 * 
 * @property int $id
 * @property int $firm_id
 * @property int $user_id
 * @property int $version_id
 * @property Carbon|null $last_accessed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property User $user
 * @property Version $version
 *
 * @package App\Models\Saas
 */
class SystemUsage extends Model
{
	use SoftDeletes;
	protected $table = 'system_usages';

	protected $casts = [
		'firm_id' => 'int',
		'user_id' => 'int',
		'version_id' => 'int',
		'last_accessed_at' => 'datetime'
	];

	protected $fillable = [
		'firm_id',
		'user_id',
		'version_id',
		'last_accessed_at'
	];

	public function user()
	{
		return $this->belongsTo(User::class);
	}

	public function version()
	{
		return $this->belongsTo(Version::class);
	}
}
