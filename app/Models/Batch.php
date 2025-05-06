<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
/**
 * Class Batch
 *
 * @property int $id
 * @property int $firm_id
 * @property int $user_id
 * @property string $modulecomponent
 * @property string|null $action
 * @property string|null $title
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property Firm $firm
 * @property User $user
 * @property Collection|BatchItem[] $batch_items
 *
 * @package App\Models
 */
class Batch extends Model
{
	protected $table = 'batches';

	protected $casts = [
		'firm_id' => 'int',
		'user_id' => 'int'
	];

	protected $fillable = [
		'firm_id',
		'user_id',
		'modulecomponent',
		'action',
		'title'
	];
    protected $guarded = [];
    protected static function booted()
    {
        // auto-scope to current tenant (firm)
        static::addGlobalScope('firm', function (Builder $q) {
            $q->where('firm_id', session('firm_id'));
        });
    }

	public function firm()
	{
		return $this->belongsTo(Firm::class);
	}

	public function user()
	{
		return $this->belongsTo(User::class);
	}

    public function items()
    {
        return $this->hasMany(BatchItem::class);
    }
}
