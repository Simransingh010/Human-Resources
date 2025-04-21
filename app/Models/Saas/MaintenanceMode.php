<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Saas;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class MaintenanceMode
 * 
 * @property int $id
 * @property int|null $firm_id
 * @property string $platform
 * @property bool $is_maintenance
 * @property string|null $message
 * @property Carbon|null $start_time
 * @property Carbon|null $end_time
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property Firm|null $firm
 *
 * @package App\Models\Saas
 */
class MaintenanceMode extends Model
{
	use SoftDeletes;
	protected $table = 'maintenance_modes';

	protected $casts = [
		'firm_id' => 'int',
		'is_maintenance' => 'bool',
		'start_time' => 'datetime',
		'end_time' => 'datetime',
		'is_active' => 'bool'
	];

	protected $fillable = [
		'firm_id',
		'platform',
		'is_maintenance',
		'message',
		'start_time',
		'end_time',
		'is_active'
	];

	public function firm()
	{
		return $this->belongsTo(Firm::class);
	}
}
