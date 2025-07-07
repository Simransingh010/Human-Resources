<?php

namespace App\Models\Hrms;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmpAttendanceStatuses extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'emp_attendance_statuses';

    protected $fillable = [
        'firm_id',
        'attendance_status_code',
        'attendance_status_label',
        'attendance_status_desc',
        'paid_percent',
        'attendance_status_main',
        'attribute_json',
        'is_inactive',
        'work_shift_id',
    ];

    protected $casts = [
        'firm_id' => 'integer',
        'paid_percent' => 'decimal:2',
        'is_inactive' => 'boolean',
        'work_shift_id' => 'integer',
        'attribute_json' => 'array',
    ];

    // Attendance status main options
    public const ATTENDANCE_STATUS_MAIN_OPTIONS = [
        'P'   => 'Present',
        'A'   => 'Absent',
        'HD'  => 'Half Day',
        'PW'  => 'Partial Working',
        'L'   => 'Leave',
        'WFR' => 'Work from Remote',
        'CW'  => 'Compensatory Work',
        'OD'  => 'On Duty',
        'H'   => 'Holiday',
        'W'   => 'Week Off',
        'S'   => 'Suspended',
        'POW' => 'Present on Work Off',
        'LM'  => 'Late Marked',
        'NM'  => 'Not Marked',
    ];

    // Relationships
    public function firm()
    {
        return $this->belongsTo(\App\Models\Saas\Firm::class);
    }

    public function workShift()
    {
        return $this->belongsTo(WorkShift::class);
    }

    // Accessors
    public function getAttendanceStatusMainLabelAttribute()
    {
        return self::ATTENDANCE_STATUS_MAIN_OPTIONS[$this->attendance_status_main] ?? $this->attendance_status_main;
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_inactive', false);
    }

    public function scopeByFirm($query, $firmId)
    {
        return $query->where('firm_id', $firmId);
    }

    // Cache management
    protected static function boot()
    {
        parent::boot();

        // Clear cache when attendance status is created, updated, or deleted
        static::saved(function ($model) {
            $model->clearAttendanceStatusesCache();
        });

        static::deleted(function ($model) {
            $model->clearAttendanceStatusesCache();
        });
    }

    /**
     * Clear the attendance statuses cache for this firm
     */
    public function clearAttendanceStatusesCache()
    {
        $cacheKey = "attendance_statuses_firm_{$this->firm_id}";
        \Cache::forget($cacheKey);
    }

    /**
     * Clear cache for a specific firm
     */
    public static function clearCacheForFirm($firmId)
    {
        $cacheKey = "attendance_statuses_firm_{$firmId}";
        \Cache::forget($cacheKey);
    }
}
