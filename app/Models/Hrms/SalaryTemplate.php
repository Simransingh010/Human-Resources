<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Hrms;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class SalaryTemplate
 * 
 * @property int $id
 * @property int $firm_id
 * @property string $title
 * @property string|null $description
 * @property int $salary_template_group_id
 * @property Carbon $effective_from
 * @property Carbon|null $effective_to
 * @property bool $is_inactive
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property Firm $firm
 * @property SalaryTemplateGroup $salary_template_group
 * @property Collection|SalaryComponentsEmployee[] $salary_components_employees
 * @property Collection|SalaryTemplatesComponent[] $salary_templates_components
 *
 * @package App\Models\Hrms
 */
class SalaryTemplate extends Model
{
	use SoftDeletes;
	protected $table = 'salary_templates';

	protected $casts = [
		'firm_id' => 'int',
		'salary_template_group_id' => 'int',
		'effective_from' => 'datetime',
		'effective_to' => 'datetime',
		'is_inactive' => 'bool'
	];

	protected $fillable = [
		'firm_id',
		'title',
		'description',
		'salary_template_group_id',
		'effective_from',
		'effective_to',
		'is_inactive'
	];

	public function firm()
	{
		return $this->belongsTo(Firm::class);
	}

	public function salary_template_group()
	{
		return $this->belongsTo(SalaryTemplateGroup::class);
	}

	public function salary_components_employees()
	{
		return $this->hasMany(SalaryComponentsEmployee::class);
	}

	public function salary_templates_components()
	{
		return $this->hasMany(SalaryTemplatesComponent::class);
	}
}
