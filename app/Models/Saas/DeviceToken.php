<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Saas;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class DeviceToken
 * 
 * @property int $id
 * @property int $firm_id
 * @property int $user_id
 * @property string $token
 * @property string $device_type
 * @property string|null $device_name
 * @property string|null $os_version
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property Firm $firm
 * @property User $user
 *
 * @package App\Models\Saas
 */
class DeviceToken extends Model
{
	use SoftDeletes;
	protected $table = 'device_tokens';

	protected $casts = [
		'firm_id' => 'int',
		'user_id' => 'int',
		'is_active' => 'bool'
	];

	protected $hidden = [
		'token'
	];

	protected $fillable = [
		'firm_id',
		'user_id',
		'token',
		'device_type',
		'device_name',
		'os_version',
		'is_active'
	];

	public function firm()
	{
		return $this->belongsTo(Firm::class);
	}

	public function user()
	{
		return $this->belongsTo(User::class);
	}
}
