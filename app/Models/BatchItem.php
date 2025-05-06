<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class BatchItem
 *
 * @property int $id
 * @property int $batch_id
 * @property string $operation
 * @property string $model_type
 * @property int|null $model_id
 * @property string|null $original_data
 * @property string|null $new_data
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property Batch $batch
 *
 * @package App\Models
 */
class BatchItem extends Model
{
	protected $table = 'batch_items';

	protected $casts = [
		'batch_id' => 'int',
		'model_id' => 'int'
	];

	protected $fillable = [
		'batch_id',
		'operation',
		'model_type',
		'model_id',
		'original_data',
		'new_data'
	];
    protected $guarded = [];
	public function batch()
	{
		return $this->belongsTo(Batch::class);
	}
}
