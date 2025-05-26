<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Hrms;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class EmployeeTaxRegime
 * 
 * @property int $id
 * @property int $firm_id
 * @property int $employee_id
 * @property int $regime_id
 * @property Carbon|null $effective_from
 * @property Carbon|null $effective_to
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property Employee $employee
 * @property Firm $firm
 * @property TaxRegime $tax_regime
 *
 * @package App\Models\Hrms
 */
class EmployeeTaxRegime extends Model
{
	use SoftDeletes;
	protected $table = 'employee_tax_regimes';

	protected $casts = [
		'firm_id' => 'int',
		'employee_id' => 'int',
		'regime_id' => 'int',
		'effective_from' => 'datetime',
		'effective_to' => 'datetime'
	];

	protected $fillable = [
		'firm_id',
		'employee_id',
		'regime_id',
		'effective_from',
		'effective_to'
	];

	public function employee()
	{
		return $this->belongsTo(Employee::class);
	}

	public function firm()
	{
		return $this->belongsTo(Firm::class);
	}

	public function tax_regime()
	{
		return $this->belongsTo(TaxRegime::class, 'regime_id');
	}
}
