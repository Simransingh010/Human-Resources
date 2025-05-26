<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Hrms;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class TaxBracket
 * 
 * @property int $id
 * @property int $firm_id
 * @property int $regime_id
 * @property string $type
 * @property float $income_from
 * @property float|null $income_to
 * @property float $rate
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property Firm $firm
 * @property TaxRegime $tax_regime
 *
 * @package App\Models\Hrms
 */
class TaxBracket extends Model
{
	use SoftDeletes;
	protected $table = 'tax_brackets';

	protected $casts = [
		'firm_id' => 'int',
		'regime_id' => 'int',
		'income_from' => 'float',
		'income_to' => 'float',
		'rate' => 'float'
	];

	protected $fillable = [
		'firm_id',
		'regime_id',
		'type',
		'income_from',
		'income_to',
		'rate'
	];

	public function firm()
	{
		return $this->belongsTo(Firm::class);
	}

	public function tax_regime()
	{
		return $this->belongsTo(TaxRegime::class, 'regime_id');
	}
}
