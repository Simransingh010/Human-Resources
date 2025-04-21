<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Saas;

use Illuminate\Database\Eloquent\Model;

/**
 * Class ModuleclusterModule
 * 
 * @property int $id
 * @property int $modulecluster_id
 * @property int $module_id
 * 
 * @property Module $module
 * @property Modulecluster $modulecluster
 *
 * @package App\Models\Saas
 */
class ModuleclusterModule extends Model
{
	protected $table = 'modulecluster_module';
	public $timestamps = false;

	protected $casts = [
		'modulecluster_id' => 'int',
		'module_id' => 'int'
	];

	protected $fillable = [
		'modulecluster_id',
		'module_id'
	];

	public function module()
	{
		return $this->belongsTo(Module::class);
	}

	public function modulecluster()
	{
		return $this->belongsTo(Modulecluster::class);
	}
}
