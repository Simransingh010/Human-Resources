<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Settings;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class DocumentType
 * 
 * @property int $id
 * @property int $firm_id
 * @property string $title
 * @property string $code
 * @property string|null $description
 * @property bool $is_inactive
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property Firm $firm
 * @property Collection|EmployeeDoc[] $employee_docs
 *
 * @package App\Models\Settings
 */
class DocumentType extends Model
{
	use SoftDeletes;
	protected $table = 'document_types';

	protected $casts = [
		'firm_id' => 'int',
		'is_inactive' => 'bool'
	];

	protected $fillable = [
		'firm_id',
		'title',
		'code',
		'description',
		'is_inactive'
	];

	public function firm()
	{
		return $this->belongsTo(Firm::class);
	}

	public function employee_docs()
	{
		return $this->hasMany(EmployeeDoc::class);
	}
}
