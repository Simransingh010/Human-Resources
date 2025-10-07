<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Hrms;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class DeclarationGroup
 *
 * @property int $id
 * @property string $name
 * @property string $code
 * @property int|null $section_code
 * @property float|null $max_cap
 * @property string|null $regime_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 */
class DeclarationGroup extends Model
{
    use SoftDeletes;

    protected $table = 'declaration_group';

    protected $casts = [
        'section_code' => 'int',
        'max_cap' => 'float',
    ];

    protected $fillable = [
        'name',
        'code',
        'section_code',
        'max_cap',
        'regime_id',
    ];
}



