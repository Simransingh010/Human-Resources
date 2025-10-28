<?php

namespace App\Models\Saas;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class College extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'college';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'firm_id',
        'code',
        'established_year',
        'address',
        'city',
        'state',
        'country',
        'phone',
        'email',
        'website',
        'is_inactive',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'established_year' => 'integer',
        'is_inactive' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the firm that owns the college.
     */
    public function firm(): BelongsTo
    {
        return $this->belongsTo(Firm::class);
    }

    /**
     * Get the college employees for the college.
     */
    public function collegeEmployees()
    {
        return $this->hasMany(CollegeEmployee::class);
    }

    /**
     * Get the employees associated with this college.
     */
    public function employees()
    {
        return $this->belongsToMany(\App\Models\Hrms\Employee::class, 'college_employee', 'college_id', 'employee_id')
                    ->withTimestamps();
    }

    /**
     * Scope a query to only include active colleges.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_inactive', false);
    }

    /**
     * Scope a query to only include inactive colleges.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInactive($query)
    {
        return $query->where('is_inactive', true);
    }

    /**
     * Get the college's full address.
     *
     * @return string
     */
    public function getFullAddressAttribute(): string
    {
        return "{$this->address}, {$this->city}, {$this->state}, {$this->country}";
    }

    /**
     * Get the college's display name with code.
     *
     * @return string
     */
    public function getDisplayNameAttribute(): string
    {
        return "{$this->name} ({$this->code})";
    }
}
