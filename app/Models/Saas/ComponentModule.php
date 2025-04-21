<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Saas;

use Illuminate\Database\Eloquent\Model;

/**
 * Class ComponentModule
 * 
 * @property int $id
 * @property int $module_id
 * @property int $component_id
 * 
 * @property Component $component
 * @property Module $module
 *
 * @package App\Models\Saas
 */
class ComponentModule extends Model
{
	protected $table = 'component_module';
	public $timestamps = false;

	protected $casts = [
		'module_id' => 'int',
		'component_id' => 'int'
	];

	protected $fillable = [
		'module_id',
		'component_id'
	];

	public function component()
	{
		return $this->belongsTo(Component::class);
	}

	public function module()
	{
		return $this->belongsTo(Module::class);
	}
}
