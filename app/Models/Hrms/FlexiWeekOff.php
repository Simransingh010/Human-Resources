<?php

namespace App\Models\Hrms;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FlexiWeekOff extends Model
{
    use HasFactory;

    protected $table = 'flexi_week_off';

    protected $fillable = [
        'firm_id',
        'employee_id',
        'attendance_status_main',
        'availed_emp_attendance_id',
        'consumed_emp_attendance_id',
        'week_off_Status',
    ];
    
    public const ATTENDANCE_STATUS_MAIN_SELECT = [
        'P'   => 'Present',
        'A'   => 'Absent',
        'HD'  => 'Half Day',
        'PW'  => 'Partial Working',
        'L'   => 'Leave',
        'WFR' => 'Work from Remote',
        'CW'   => 'Compensatory Work',
        'OD'  => 'On Duty',
        'H'   => 'Holiday',
        'W'   => 'Week Off',
        'S'   => 'Suspended',

    ];
    public const WEEK_OFF_STATUS_MAIN_SELECT = [
        'A'   => 'Available',
        'C'   => 'Consumed',
        'L'   => 'Lapsed',
        'CF'   => 'Carry Forward',

    ];
} 