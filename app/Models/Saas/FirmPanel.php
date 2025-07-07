<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Saas;

use Illuminate\Database\Eloquent\Model;

/**
 * Class FirmPanel
 * 
 * @property int $id
 * @property int $firm_id
 * @property int $panel_id
 * 
 * @property Firm $firm
 * @property Panel $panel
 *
 * @package App\Models\Saas
 */
class FirmPanel extends Model
{
	protected $table = 'firm_panel';
	public $timestamps = false;

	protected $casts = [
		'firm_id' => 'int',
		'panel_id' => 'int'
	];

	protected $fillable = [
		'firm_id',
		'panel_id'
	];

	public function firm()
	{
		return $this->belongsTo(Firm::class);
	}

	public function panel()
	{
		return $this->belongsTo(Panel::class);
	}
}
