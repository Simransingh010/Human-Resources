<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Saas;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Testing\Fluent\Concerns\Has;

/**
 * Class Panel
 *
 * @property int $id
 * @property string $name
 * @property string|null $code
 * @property string|null $description
 * @property string $panel_type
 * @property bool $is_inactive
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 *
 * @property Collection|App[] $apps
 * @property Collection|AppModule[] $app_modules
 * @property Collection|User[] $users
 *
 * @package App\Models\Saas
 */
class Panel extends Model
{
	use SoftDeletes,HasFactory;
	protected $table = 'panels';

	protected $casts = [
		'is_inactive' => 'bool'
	];

    public const PANEL_TYPE_SELECT = [
        '1' => 'Mobile App',
        '2' => 'Web App',
        '3' => 'Admin Panel',
        '4' => 'Client Portal'
    ];


    protected $fillable = [
		'name',
		'code',
		'description',
		'panel_type',
        'icon',
        'color',
        'tooltip',
        'order',
        'badge',
        'custom_css',
        'is_inactive'
	];

    public function getPanelTypeLabelAttribute($value)
    {
        return static::PANEL_TYPE_SELECT[$this->panel_type] ?? null;
    }


    public function apps()
	{
		return $this->belongsToMany(App::class, 'panel_app')
					->withPivot('id', 'deleted_at')
					->withTimestamps();
	}

	public function app_modules()
	{
		return $this->belongsToMany(AppModule::class, 'panel_app_module')
					->withPivot('id', 'deleted_at')
					->withTimestamps();
	}

	public function users()
	{
		return $this->belongsToMany(User::class)
					->withPivot('id', 'deleted_at')
					->withTimestamps();
	}
    public function components()
    {
        return $this->belongsToMany(Component::class, 'component_panel')
                     ->withPivot('id');
    }
}
