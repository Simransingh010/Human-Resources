<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Saas;

use Illuminate\Database\Eloquent\Model;

/**
 * Class ComponentPanel
 * 
 * @property int $id
 * @property int $panel_id
 * @property int $component_id
 * 
 * @property Component $component
 * @property Panel $panel
 *
 * @package App\Models\Saas
 */
class ComponentPanel extends Model
{
	protected $table = 'component_panel';
	public $timestamps = false;

	protected $casts = [
		'panel_id' => 'int',
		'component_id' => 'int'
	];

	protected $fillable = [
		'panel_id',
		'component_id'
	];

	public function component()
	{
		return $this->belongsTo(Component::class);
	}

	public function panel()
	{
		return $this->belongsTo(Panel::class);
	}
}
