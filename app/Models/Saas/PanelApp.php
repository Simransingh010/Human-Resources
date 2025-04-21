<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Saas;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class PanelApp
 * 
 * @property int $id
 * @property int $panel_id
 * @property int $app_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property App $app
 * @property Panel $panel
 *
 * @package App\Models\Saas
 */
class PanelApp extends Model
{
	use SoftDeletes;
	protected $table = 'panel_app';

	protected $casts = [
		'panel_id' => 'int',
		'app_id' => 'int'
	];

	protected $fillable = [
		'panel_id',
		'app_id'
	];

	public function app()
	{
		return $this->belongsTo(App::class);
	}

	public function panel()
	{
		return $this->belongsTo(Panel::class);
	}
}
