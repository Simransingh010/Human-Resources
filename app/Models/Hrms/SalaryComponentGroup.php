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
 * Class SalaryComponentGroup
 * 
 * @property int $id
 * @property int $firm_id
 * @property string $title
 * @property string|null $description
 * @property int|null $parent_salary_component_group_id
 * @property bool $is_inactive
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property Firm $firm
 * @property SalaryComponentGroup|null $salary_component_group
 * @property Collection|SalaryComponentGroup[] $salary_component_groups
 * @property Collection|SalaryComponent[] $salary_components
 * @property Collection|SalaryComponentsEmployee[] $salary_components_employees
 * @property Collection|SalaryTemplatesComponent[] $salary_templates_components
 *
 * @package App\Models\Hrms
 */
class SalaryComponentGroup extends Model
{
	use SoftDeletes;
	protected $table = 'salary_component_groups';

	protected $casts = [
		'firm_id' => 'int',
		'parent_salary_component_group_id' => 'int',
		'is_inactive' => 'bool'
	];

	protected $fillable = [
		'firm_id',
		'title',
		'description',
		'parent_salary_component_group_id',
		'is_inactive'
	];

	public function firm()
	{
		return $this->belongsTo(Firm::class);
	}

	public function salary_component_group()
	{
		return $this->belongsTo(SalaryComponentGroup::class, 'parent_salary_component_group_id');
	}

	public function salary_component_groups()
	{
		return $this->hasMany(SalaryComponentGroup::class, 'parent_salary_component_group_id');
	}

	public function salary_components()
	{
		return $this->hasMany(SalaryComponent::class);
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
