<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Hrms;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class DeclarationType
 *
 * @property int $id
 * @property string $name
 * @property string $code
 * @property int|null $section_code
 * @property float|null $max_cap
 * @property int|null $declaration_group_id
 * @property bool $proof_required
 * @property string|null $validation_rules
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 */
class DeclarationType extends Model
{
    use SoftDeletes;

    protected $table = 'declaration_type';

    protected $casts = [
        'section_code' => 'int',
        'max_cap' => 'float',
        'declaration_group_id' => 'int',
        'proof_required' => 'bool',
    ];

    protected $fillable = [
        'name',
        'code',
        'section_code',
        'max_cap',
        'declaration_group_id',
        'proof_required',
        'validation_rules',
    ];
}



