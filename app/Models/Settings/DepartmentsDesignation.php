<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Settings;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class DepartmentsDesignation
 * 
 * @property int $id
 * @property int $firm_id
 * @property int $designation_id
 * @property int $department_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property Department $department
 * @property Designation $designation
 * @property Firm $firm
 *
 * @package App\Models\Settings
 */
class DepartmentsDesignation extends Model
{
	use SoftDeletes;
	protected $table = 'departments_designations';

	protected $casts = [
		'firm_id' => 'int',
		'designation_id' => 'int',
		'department_id' => 'int'
	];

	protected $fillable = [
		'firm_id',
		'designation_id',
		'department_id'
	];

	public function department()
	{
		return $this->belongsTo(Department::class);
	}

	public function designation()
	{
		return $this->belongsTo(Designation::class);
	}

	public function firm()
	{
		return $this->belongsTo(Firm::class);
	}
}
