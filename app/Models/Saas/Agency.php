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
 * Class Agency
 * 
 * @property int $id
 * @property string $name
 * @property string|null $email
 * @property string|null $phone
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property Collection|Firm[] $firms
 *
 * @package App\Models\Saas
 */
class Agency extends Model
{
	use SoftDeletes;
	protected $table = 'agencies';

	protected $fillable = [
		'name',
		'email',
		'phone'
	];

	public function firms()
	{
		return $this->hasMany(Firm::class);
	}
}
