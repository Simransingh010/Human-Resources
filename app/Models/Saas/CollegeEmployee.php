<?php

namespace App\Models\Saas;

use App\Models\Hrms\Employee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CollegeEmployee extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'college_employee';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'college_id',
        'employee_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'college_id' => 'integer',
        'employee_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the college that owns the college employee.
     */
    public function college(): BelongsTo
    {
        return $this->belongsTo(College::class);
    }

    /**
     * Get the employee that owns the college employee.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Scope a query to filter by college.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $collegeId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByCollege($query, $collegeId)
    {
        return $query->where('college_id', $collegeId);
    }

    /**
     * Scope a query to filter by employee.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $employeeId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    /**
     * Check if a specific college-employee relationship exists.
     *
     * @param  int  $collegeId
     * @param  int  $employeeId
     * @return bool
     */
    public static function exists($collegeId, $employeeId): bool
    {
        return static::where('college_id', $collegeId)
                    ->where('employee_id', $employeeId)
                    ->exists();
    }

    /**
     * Create or get a college-employee relationship.
     *
     * @param  int  $collegeId
     * @param  int  $employeeId
     * @return static
     */
    public static function createOrGet($collegeId, $employeeId): static
    {
        return static::firstOrCreate([
            'college_id' => $collegeId,
            'employee_id' => $employeeId,
        ]);
    }
}
