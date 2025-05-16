<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Hrms;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class SalaryTemplatesComponent
 * 
 * @property int $id
 * @property int $firm_id
 * @property int $salary_template_id
 * @property int $salary_component_id
 * @property int|null $salary_component_group_id
 * @property int $sequence
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property Firm $firm
 * @property SalaryComponentGroup|null $salary_component_group
 * @property SalaryComponent $salary_component
 * @property SalaryTemplate $salary_template
 *
 * @package App\Models\Hrms
 */
class SalaryTemplatesComponent extends Model
{
	use SoftDeletes;
	protected $table = 'salary_templates_components';

	protected $casts = [
		'firm_id' => 'int',
		'salary_template_id' => 'int',
		'salary_component_id' => 'int',
		'salary_component_group_id' => 'int',
		'sequence' => 'int'
	];

	protected $fillable = [
		'firm_id',
		'salary_template_id',
		'salary_component_id',
		'salary_component_group_id',
		'sequence'
	];

	public function firm()
	{
		return $this->belongsTo(Firm::class);
	}

	public function salary_component_group()
	{
		return $this->belongsTo(SalaryComponentGroup::class);
	}

	public function salary_component()
	{
		return $this->belongsTo(SalaryComponent::class);
	}

	public function salary_template()
	{
		return $this->belongsTo(SalaryTemplate::class);
	}
}
