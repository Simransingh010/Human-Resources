<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Saas;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class PanelUser
 * 
 * @property int $id
 * @property int $user_id
 * @property int $panel_id
 * @property int $firm_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property Panel $panel
 * @property User $user
 *
 * @package App\Models\Saas
 */
class PanelUser extends Model
{
	use SoftDeletes;
	protected $table = 'panel_user';

	protected $casts = [
		'user_id' => 'int',
		'panel_id' => 'int',
		'firm_id' => 'int'
	];

	protected $fillable = [
		'user_id',
		'panel_id',
		'firm_id'
	];

	public function panel()
	{
		return $this->belongsTo(Panel::class);
	}

	public function user()
	{
		return $this->belongsTo(User::class);
	}
}
