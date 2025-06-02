<?php

namespace App\Models\Hrms;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class AttendanceLocation
 * 
 * @property int $id
 * @property int $firm_id
 * @property string $title
 * @property string|null $description
 * @property bool $is_inactive
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property Firm $firm
 *
 * @package App\Models\Hrms
 */
class AttendanceLocation extends Model
{
    use SoftDeletes;

    protected $table = 'attend_locations';

    protected $casts = [
        'firm_id' => 'int',
        'is_inactive' => 'bool'
    ];

    protected $fillable = [
        'firm_id',
        'title',
        'description',
        'is_inactive'
    ];

    public function firm()
    {
        return $this->belongsTo(Firm::class);
    }
} 