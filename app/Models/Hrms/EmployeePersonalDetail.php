<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Hrms;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * Class EmployeePersonalDetail
 * 
 * @property int $id
 * @property int $firm_id
 * @property int $employee_id
 * @property Carbon|null $dob
 * @property string|null $marital_status
 * @property Carbon|null $doa
 * @property string|null $nationality
 * @property string|null $fathername
 * @property string|null $mothername
 * @property string|null $adharno
 * @property string|null $panno
 * @property string|null $employee_image
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property Employee $employee
 * @property Firm $firm
 *
 * @package App\Models\Hrms
 */
class EmployeePersonalDetail extends Model implements HasMedia
{
	use SoftDeletes, InteractsWithMedia;
	protected $table = 'employee_personal_details';

	protected $casts = [
		'firm_id' => 'int',
		'employee_id' => 'int',
		'dob' => 'datetime',
		'doa' => 'datetime'
	];

	protected $fillable = [
		'firm_id',
		'employee_id',
		'dob',
		'marital_status',
		'doa',
		'nationality',
		'fathername',
		'mothername',
		'adharno',
		'panno',
		'employee_image'
	];

	public function employee()
	{
		return $this->belongsTo(Employee::class);
	}

	public function firm()
	{
		return $this->belongsTo(Firm::class);
	}
}
