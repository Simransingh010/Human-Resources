<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Settings;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class EmploymentType
 * 
 * @property int $id
 * @property int $firm_id
 * @property string $title
 * @property string $code
 * @property string|null $description
 * @property bool $is_inactive
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property Firm $firm
 * @property Collection|EmployeeJobProfile[] $employee_job_profiles
 *
 * @package App\Models\Settings
 */
class EmploymentType extends Model
{
	use SoftDeletes;
	protected $table = 'employment_types';

	protected $casts = [
		'firm_id' => 'int',
		'is_inactive' => 'bool'
	];

	protected $fillable = [
		'firm_id',
		'title',
		'code',
		'description',
		'is_inactive'
	];

	public function firm()
	{
		return $this->belongsTo(Firm::class);
	}

	public function employee_job_profiles()
	{
		return $this->hasMany(EmployeeJobProfile::class, 'employment_type');
	}
}
