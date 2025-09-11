<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use App\Models\Hrms\EmpWorkShift;
use App\Models\Hrms\SalaryComponentsEmployee;

/**
 * Class BatchItem
 *
 * @property int $id
 * @property int $batch_id
 * @property string $operation
 * @property string $model_type
 * @property int|null $model_id
 * @property string|null $original_data
 * @property string|null $new_data
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property Batch $batch
 *
 * @package App\Models
 */
class BatchItem extends Model
{
	protected $table = 'batch_items';

	protected $casts = [
		'batch_id' => 'int',
		'model_id' => 'int'
	];

	protected $fillable = [
		'batch_id',
		'operation',
		'model_type',
		'model_id',
		'original_data',
		'new_data'
	];
	protected $guarded = [];
	public function batch()
	{
		return $this->belongsTo(Batch::class);
	}

	public function model()
	{
		return $this->morphTo();
	}

	public function empWorkShift()
	{
		return $this->belongsTo(EmpWorkShift::class, 'model_id');
	}

	public function salaryComponentEmployee()
	{
		return $this->belongsTo(SalaryComponentsEmployee::class, 'model_id');
	}

	public function employee()
	{
		return $this->hasOne(\App\Models\Hrms\Employee::class, 'user_id', 'model_id');
	}

	public function role()
	{
		return $this->belongsTo(\App\Models\Saas\RoleUser::class, 'model_id');
	}

	public function panelUser()
	{
		return $this->belongsTo(\App\Models\Saas\PanelUser::class, 'model_id');
	}
}
