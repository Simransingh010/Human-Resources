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
 * Class SalaryTemplateGroup
 * 
 * @property int $id
 * @property int $firm_id
 * @property string $title
 * @property string|null $description
 * @property int|null $parent_salary_template_group_id
 * @property string $cycle_unit
 * @property string $cycle_value
 * @property string|null $cycle_start_unit
 * @property string|null $cycle_start_value
 * @property bool $is_inactive
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property Firm $firm
 * @property SalaryTemplateGroup|null $salary_template_group
 * @property Collection|SalaryTemplateGroup[] $salary_template_groups
 * @property Collection|SalaryTemplate[] $salary_templates
 *
 * @package App\Models\Hrms
 */
class SalaryTemplateGroup extends Model
{
	use SoftDeletes;
	protected $table = 'salary_template_groups';

	protected $casts = [
		'firm_id' => 'int',
		'parent_salary_template_group_id' => 'int',
		'is_inactive' => 'bool'
	];

	protected $fillable = [
		'firm_id',
		'title',
		'description',
		'parent_salary_template_group_id',
		'cycle_unit',
		'cycle_value',
		'cycle_start_unit',
		'cycle_start_value',
		'is_inactive'
	];

	public const CYCLE_UNIT_SELECT = [
		'day' => 'Day',
		'week' => 'Week',
		'month' => 'Month',
		'year' => 'Year'
	];

	public const CYCLE_START_UNIT_SELECT = [
		'week_day' => 'Week Day',
		'month_day' => 'Month Day',
		'month_date' => 'Month Date'
	];

	public function firm()
	{
		return $this->belongsTo(Firm::class);
	}

	public function salary_template_group()
	{
		return $this->belongsTo(SalaryTemplateGroup::class, 'parent_salary_template_group_id');
	}

	public function salary_template_groups()
	{
		return $this->hasMany(SalaryTemplateGroup::class, 'parent_salary_template_group_id');
	}

	public function salary_templates()
	{
		return $this->hasMany(SalaryTemplate::class);
	}
}
