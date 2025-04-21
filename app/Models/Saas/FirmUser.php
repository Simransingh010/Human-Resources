<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Saas;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class FirmUser
 * 
 * @property int $id
 * @property int $user_id
 * @property int $firm_id
 * @property bool $is_default
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property Firm $firm
 * @property User $user
 *
 * @package App\Models\Saas
 */
class FirmUser extends Model
{
	use SoftDeletes;
	protected $table = 'firm_user';

	protected $casts = [
		'user_id' => 'int',
		'firm_id' => 'int',
		'is_default' => 'bool'
	];

	protected $fillable = [
		'user_id',
		'firm_id',
		'is_default'
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
