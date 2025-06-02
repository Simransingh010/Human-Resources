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
 * Class Version
 * 
 * @property int $id
 * @property string $name
 * @property string|null $code
 * @property string|null $description
 * @property string|null $major_version
 * @property string|null $minor_version
 * @property bool $is_inactive
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property Collection|Firm[] $firms
 * @property Collection|SystemUsage[] $system_usages
 *
 * @package App\Models\Saas
 */
class Version extends Model
{
	use SoftDeletes;
	protected $table = 'versions';

	protected $casts = [
		'is_inactive' => 'bool'
	];

	public const DEVICE_TYPE_SELECT = [
        'ios' => 'ios',
        '2' => 'android',
    ];

	protected $fillable = [
		'name',
		'code',
		'description',
        'device_type',
		'major_version',
		'minor_version',
		'is_inactive'
	];

	public function getDeviceTypeLabelAttribute($value)
    {
        return self::DEVICE_TYPE_SELECT[$this->device_type] ?? null;
    }

	public function firms()
	{
		return $this->belongsToMany(Firm::class, 'firm_versions')
					->withPivot('id', 'type', 'is_inactive', 'deleted_at')
					->withTimestamps();
	}

	public function system_usages()
	{
		return $this->hasMany(SystemUsage::class);
	}
}
