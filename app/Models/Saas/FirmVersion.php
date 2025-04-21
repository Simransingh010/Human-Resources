<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Saas;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class FirmVersion
 * 
 * @property int $id
 * @property int $firm_id
 * @property int $version_id
 * @property string $type
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property Firm $firm
 * @property Version $version
 *
 * @package App\Models\Saas
 */
class FirmVersion extends Model
{
	use SoftDeletes;
	protected $table = 'firm_versions';

	protected $casts = [
		'firm_id' => 'int',
		'version_id' => 'int',
		'is_active' => 'bool'
	];

	protected $fillable = [
		'firm_id',
		'version_id',
		'type',
		'is_active'
	];

	public function firm()
	{
		return $this->belongsTo(Firm::class);
	}

	public function version()
	{
		return $this->belongsTo(Version::class);
	}
}
