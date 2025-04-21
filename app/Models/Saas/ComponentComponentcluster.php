<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Saas;

use Illuminate\Database\Eloquent\Model;

/**
 * Class ComponentComponentcluster
 * 
 * @property int $id
 * @property int $componentcluster_id
 * @property int $component_id
 * 
 * @property Component $component
 * @property Componentcluster $componentcluster
 *
 * @package App\Models\Saas
 */
class ComponentComponentcluster extends Model
{
	protected $table = 'component_componentcluster';
	public $timestamps = false;

	protected $casts = [
		'componentcluster_id' => 'int',
		'component_id' => 'int'
	];

	protected $fillable = [
		'componentcluster_id',
		'component_id'
	];

	public function component()
	{
		return $this->belongsTo(Component::class);
	}

	public function componentcluster()
	{
		return $this->belongsTo(Componentcluster::class);
	}
}
