<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Hrms;

use App\Livewire\Saas\Firms;
use App\Models\Settings\DocumentType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * Class EmployeeDoc
 * 
 * @property int $id
 * @property int $firm_id
 * @property int $employee_id
 * @property int $document_type_id
 * @property string $document_number
 * @property Carbon|null $issued_date
 * @property Carbon|null $expiry_date
 * @property string|null $doc_url
 * @property bool $is_inactive
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property DocumentType $document_type
 * @property Employee $employee
 * @property Firm $firm
 *
 * @package App\Models\Hrms
 */
class EmployeeDoc extends Model implements HasMedia
{
	use SoftDeletes, InteractsWithMedia;
	protected $table = 'employee_docs';

	protected $casts = [
		'firm_id' => 'int',
		'employee_id' => 'int',
		'document_type_id' => 'int',
		'issued_date' => 'datetime',
		'expiry_date' => 'datetime',
		'is_inactive' => 'bool'
	];

	protected $fillable = [
		'firm_id',
		'employee_id',
		'document_type_id',
		'document_number',
		'issued_date',
		'expiry_date',
		'doc_url',
		'is_inactive'
	];

	public function document_type()
	{
		return $this->belongsTo(DocumentType::class);
	}

	public function employee()
	{
		return $this->belongsTo(Employee::class);
	}

	public function firm()
	{
		return $this->belongsTo(Firms::class);
	}
}
