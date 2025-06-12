<?php

namespace App\Models\Hrms;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;


/**
 * Class TaxBracketBreakdown
 *
 * @property int $id
 * @property int $firm_id
 * @property int $tax_bracket_id
 * @property float|null $breakdown_amount_from
 * @property float|null $breakdown_amount_to
 * @property float|null $rate
 * @property bool $is_inactive
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at

 *
 * @property Firm $firm
 * @property TaxBracket $tax_bracket
 *
 * @package App\Models\Hrms
 */
class TaxBracketBreakdown extends Model
{
   
    protected $table = 'tax_bracket_breakdowns';

    protected $casts = [
        'firm_id' => 'int',
        'tax_bracket_id' => 'int',
        'breakdown_amount_from' => 'float',
        'breakdown_amount_to' => 'float',
        'rate' => 'float',
        'is_inactive' => 'bool',
    ];

    protected $fillable = [
        'firm_id',
        'tax_bracket_id',
        'breakdown_amount_from',
        'breakdown_amount_to',
        'rate',
        'is_inactive',
    ];

    public function firm()
    {
        return $this->belongsTo(Firm::class);
    }

    public function tax_bracket()
    {
        return $this->belongsTo(TaxBracket::class);
    }
} 