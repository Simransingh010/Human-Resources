<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Saas;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class PanelAppModule
 * 
 * @property int $id
 * @property int $panel_id
 * @property int $app_module_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property AppModule $app_module
 * @property Panel $panel
 *
 * @package App\Models\Saas
 */
class PanelAppModule extends Model
{
	use SoftDeletes;
	protected $table = 'panel_app_module';

	protected $casts = [
		'panel_id' => 'int',
		'app_module_id' => 'int'
	];

	protected $fillable = [
		'panel_id',
		'app_module_id'
	];

	public function app_module()
	{
		return $this->belongsTo(AppModule::class);
	}

	public function panel()
	{
		return $this->belongsTo(Panel::class);
	}
}
