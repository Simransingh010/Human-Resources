<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Saas;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class FirmAppAccess
 *
 * @property int $id
 * @property int $firm_id
 * @property int $app_id
 * @property int|null $app_module_id
 * @property bool $is_inactive
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 *
 * @property App $app
 * @property AppModule|null $app_module
 * @property Firm $firm
 *
 * @package App\Models\Saas
 */
class FirmAppAccess extends Model
{

	protected $table = 'firm_app_access';

	protected $casts = [
		'firm_id' => 'int',
		'app_id' => 'int',
		'app_module_id' => 'int',
		'is_inactive' => 'bool'
	];

	protected $fillable = [
		'firm_id',
		'app_id',
		'app_module_id',
		'is_inactive'
	];

	public function app()
	{
		return $this->belongsTo(App::class);
	}

	public function app_module()
	{
		return $this->belongsTo(AppModule::class);
	}

	public function firm()
	{
		return $this->belongsTo(Firm::class);
	}
}
