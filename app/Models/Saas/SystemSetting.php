<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Saas;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class SystemSetting
 * 
 * @property int $id
 * @property int|null $firm_id
 * @property string $key
 * @property string|null $value
 * @property string $type
 * @property bool $is_editable
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property Firm|null $firm
 *
 * @package App\Models\Saas
 */
class SystemSetting extends Model
{
	use SoftDeletes;
	protected $table = 'system_settings';

	protected $casts = [
		'firm_id' => 'int',
		'is_editable' => 'bool',
		'is_active' => 'bool'
	];

	protected $fillable = [
		'firm_id',
		'key',
		'value',
		'type',
		'is_editable',
		'is_active'
	];

	public function firm()
	{
		return $this->belongsTo(Firm::class);
	}
}
