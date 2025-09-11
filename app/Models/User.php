<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Saas\Action;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Saas\Firm;
use App\Models\Saas\Panel;
use App\Models\Hrms\Employee;
use App\Models\Saas\PermissionGroup;
use App\Models\Saas\Permission;
use App\Models\Saas\Role;


class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasApiTokens, Notifiable;
    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'passcode',
        'is_inactive',
        'role_main',
    ];

    public const ROLE_MAIN_TYPES = [
        'L0_emp' => 'L0 Employee',
        'L1_firm' => 'L1 Firm',
        'L2_agency' => 'L2 Agency',
        'L3_company' => 'L3 Company',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->map(fn (string $name) => Str::of($name)->substr(0, 1))
            ->implode('');
    }

    public function panels()
    {
        return $this->belongsToMany(Panel::class, 'panel_user')
                    ->withPivot('firm_id')
                    ->withTimestamps();
    }

    public function firms()
    {
        return $this->belongsToMany(Firm::class, 'firm_user')->withPivot('is_default');
    }


    public function employee()
    {
        return $this->hasOne(Employee::class, 'user_id');
    }
    public function permissionGroups()
    {
        return $this->belongsToMany(PermissionGroup::class, 'permission_group_user');
    }
//    public function permissions()
//    {
//        return $this->belongsToMany(Permission::class, 'user_permission');
//    }
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'user_permission')
            ->withPivot('firm_id')
            ->withTimestamps();
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_user');
    }

    public function actions()
    {
        return $this->belongsToMany(Action::class, 'action_user')
            ->withPivot('id', 'firm_id', 'records_scope');
    }
}
