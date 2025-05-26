<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Hrms;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class TaxRegime
 * 
 * @property int $id
 * @property int $firm_id
 * @property string $code
 * @property string $name
 * @property string|null $description
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property Firm $firm
 * @property Collection|Employee[] $employees
 * @property Collection|TaxBracket[] $tax_brackets
 *
 * @package App\Models\Hrms
 */
class TaxRegime extends Model
{
	use SoftDeletes;
	protected $table = 'tax_regimes';

	protected $casts = [
		'firm_id' => 'int',
		'is_active' => 'bool'
	];

	protected $fillable = [
		'firm_id',
		'code',
		'name',
		'description',
		'is_active'
	];

	public function firm()
	{
		return $this->belongsTo(Firm::class);
	}

	public function employees()
	{
		return $this->belongsToMany(Employee::class, 'employee_tax_regimes', 'regime_id')
					->withPivot('id', 'firm_id', 'effective_from', 'effective_to', 'deleted_at')
					->withTimestamps();
	}

	public function tax_brackets()
	{
		return $this->hasMany(TaxBracket::class, 'regime_id');
	}
}
