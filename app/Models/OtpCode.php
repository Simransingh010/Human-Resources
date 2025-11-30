<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class OtpCode
 *
 * @property int $id
 * @property int $user_id
 * @property string $otp
 * @property Carbon $expires_at
 * @property bool $is_used
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property User $user
 *
 * @package App\Models\Hrms
 */
class OtpCode extends Model
{
	protected $table = 'otp_codes';

	protected $casts = [
		'user_id' => 'int',
		'expires_at' => 'datetime',
		'is_used' => 'bool'
	];

	protected $fillable = [
		'user_id',
		'otp',
		'expires_at',
		'is_used'
	];

	public function user()
	{
		return $this->belongsTo(User::class);
	}
}
