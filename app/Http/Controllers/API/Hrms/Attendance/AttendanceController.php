<?php

namespace App\Http\Controllers\API\Hrms\Attendance;

use App\Http\Controllers\Controller;
use App\Models\Hrms\AttendancePolicy;

// Updated namespace
use App\Models\Hrms\EmpPunch;
use App\Models\Hrms\EmpAttendance;
use App\Models\Hrms\FlexiWeekOff;


// Updated namespace
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
//simranpreet singh is th 
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class AttendanceController extends Controller
{

    public function punch(Request $request)
    {
        try {
           
            $rules = [
                'firm_id' => 'required',
                'in_out' => 'required|in:in,out',
                'latitude' => 'required|numeric|between:-90,90',
                'longitude' => 'required|numeric|between:-180,180',
                'selfie' => 'nullable|image|max:2048',
                'punch_type' => 'required|in:manual,auto',
            ];

            // Define custom validation messages
            $messages = [
                'firm_id.required' => 'No Firm is Selected',
                'in_out.required' => 'The in_out field is required.',
                'in_out.in' => 'The in_out field must be either "in" or "out".',
                'latitude.required' => 'The latitude field is required.',
                'latitude.numeric' => 'The latitude must be a number.',
                'latitude.between' => 'The latitude must be between -90 and 90 degrees.',
                'longitude.required' => 'The longitude field is required.',
                'longitude.numeric' => 'The longitude must be a number.',
                'longitude.between' => 'The longitude must be between -180 and 180 degrees.',
                'selfie.image' => 'The selfie must be an image file.',
                'selfie.max' => 'The selfie may not be larger than 2MB.',
                'device_id.required' => 'The device_id field is required.',
                'device_id.string' => 'The device_id must be a string.',
                'device_id.max' => 'The device_id may not be longer than 255 characters.',
                'punch_type.required' => 'The punch_type field is required.',
                'punch_type.in' => 'The punch_type must be either "manual" or inmunal".',
            ];

            // Validate the request
            $validator = Validator::make($request->all(), $rules, $messages);
            if ($validator->fails()) {
                return response()->json([
                    'message_type' => 'error',
                    'message_display' => 'flash',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Get authenticated user (employee)
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'message_type' => 'error',
                    'message_display' => 'flash',
                    'message' => 'Unauthenticated'
                ], 401);
            }

            $employee = $user->employee; // Assuming a relationship exists between User and Employee
            if (!$employee) {
                return response()->json([
                    'message_type' => 'error',
                    'message_display' => 'flash',
                    'message' => 'Employee not found'
                ], 404);
            }

            $firmId = $request->firm_id;
            $employeeId = $employee->id;

            // Fetch applicable attendance policy
            $policy = AttendancePolicy::where('firm_id', $firmId)
                ->where(function ($query) use ($employeeId) {
                    $query->where('employee_id', $employeeId)
                        ->orWhereNull('employee_id');
                })
                ->first();

            if (!$policy) {
                return response()->json([
                    'message_type' => 'error',
                    'message_display' => 'popup',
                    'message' => 'No attendance policy found'
                ], 400);
            }

            // Validate policy rules
            $this->validatePolicy($request, $policy);

            // Determine work date and punch datetime
            $workDate = Carbon::today()->toDateString();
            $punchDateTime = Carbon::now();

            // Detect if the employee is on leave for the day. If yes, we'll convert to POL on punch-in
            $isOnLeaveDay = EmpAttendance::where('firm_id', $firmId)
                ->where('employee_id', $employeeId)
                ->where('work_date', $workDate)
                ->where('attendance_status_main', 'L')
                ->exists();

            // Fetch the employee's assigned work shift for the day
            // If multiple assignments exist, use the most recent one by created_at field
            $empWorkShifts = DB::table('emp_work_shifts')
                ->where('firm_id', $firmId)
                ->where('employee_id', $employeeId)
                ->where('start_date', '<=', $workDate)
                ->where(function ($query) use ($workDate) {
                    $query->where('end_date', '>=', $workDate)
                        ->orWhereNull('end_date');
                })
                ->get();

            if ($empWorkShifts->count() === 0) {
                return response()->json([
                    'message_type' => 'error',
                    'message_display' => 'popup',
                    'message' => 'No work shift assigned for today!!'
                ], 400);
            }

            if ($empWorkShifts->count() > 1) {
                // Use the most recent assignment by created_at field
                $empWorkShift = $empWorkShifts->sortByDesc('created_at')->first();
                
                // Log the conflict for HR review
                \Log::warning("Multiple work shifts assigned for employee", [
                    'employee_id' => $employeeId,
                    'firm_id' => $firmId,
                    'work_date' => $workDate,
                    'total_assignments' => $empWorkShifts->count(),
                    'selected_assignment' => [
                        'id' => $empWorkShift->id,
                        'work_shift_id' => $empWorkShift->work_shift_id,
                        'start_date' => $empWorkShift->start_date,
                        'end_date' => $empWorkShift->end_date,
                        'created_at' => $empWorkShift->created_at
                    ],
                    'all_assignments' => $empWorkShifts->map(function($shift) {
                        return [
                            'id' => $shift->id,
                            'work_shift_id' => $shift->work_shift_id,
                            'start_date' => $shift->start_date,
                            'end_date' => $shift->end_date,
                            'created_at' => $shift->created_at
                        ];
                    })->toArray()
                ]);
            } else {
                $empWorkShift = $empWorkShifts->first();
            }

            $workShiftId = $empWorkShift->work_shift_id;

            // Fetch the work shift day for the current date to get start_time and end_time
            $workShiftDay = DB::table('work_shift_days')
                ->where('firm_id', $firmId)
                ->where('work_shift_id', $workShiftId)
                ->where('work_date', $workDate)
                ->first();

            if (!$workShiftDay) {
                return response()->json([
                    'message_type' => 'error',
                    'message_display' => 'popup',
                    "work_date" => $workDate,
                    "work_shift_id" => $workShiftId,
                    "emp_work_shift" => $empWorkShift,
                    'message' => 'No work shift day defined for today SIMRAN '.$workDate . ' for work shift id '.$workShiftId.' for employee id '.$employeeId.' and emp_work_shift id '.$empWorkShift->id,
                ], 400);
            }

            $workShiftDayId = $workShiftDay->id;

            // Check if this is the final punch for the day (e.g., 'out' after 'in')
            $lastPunch = EmpPunch::where('firm_id', $firmId)
                ->where('employee_id', $employeeId)
                ->where('work_date', $workDate)
                ->orderBy('punch_datetime', 'desc')
                ->first();

            $isFinal = ($request->in_out === 'out' && $lastPunch && $lastPunch->in_out === 'in');

            // Store selfie if provided
            $selfiePath = null;

            // Calculate ideal working hours from work_shift_days start_time and end_time
            $idealWorkingHours = $this->calculateShiftHours($workShiftDay->start_time, $workShiftDay->end_time);

            // Only create attendance on "in"
            if ($request->in_out === 'in') {
                // Check if there's a week off entry for this date BEFORE updating/creating attendance
                $weekOffAttendance = EmpAttendance::where([
                    'firm_id' => $firmId,
                    'employee_id' => $employeeId,
                    'work_date' => $workDate,
                    'attendance_status_main' => 'W' // Week off status
                ])->first();

                // Check if there's a holiday entry for this date BEFORE updating/creating attendance
                $holidayAttendance = EmpAttendance::where([
                    'firm_id' => $firmId,
                    'employee_id' => $employeeId,
                    'work_date' => $workDate,
                    'attendance_status_main' => 'H' // Holiday status
                ])->first();

                // Now create/update the punch-in attendance (which will set it to 'P')
                $attendance = EmpAttendance::updateOrCreate(
                    [
                        'firm_id' => $firmId,
                        'employee_id' => $employeeId,
                        'work_date' => $workDate,
                    ],
                    [
                        'firm_id' => $firmId,
                        'work_shift_day_id' => $workShiftDayId,
                        'ideal_working_hours' => $idealWorkingHours,
                        'actual_worked_hours' => 0,
                        'final_day_weightage' => 0,
                        'attend_remarks' => $isOnLeaveDay ? 'Punched in (Present on Leave request created)' : 'Punched in',
                        'attendance_status_main' => $isOnLeaveDay ? 'POL' : 'P',
                    ]
                );

                // If there was a week off or a holiday, create the FlexiWeekOff entry (credit as Week Off)
                if ($weekOffAttendance || $holidayAttendance) {
                    FlexiWeekOff::create([
                        'firm_id' => $firmId,
                        'employee_id' => $employeeId,
                        // Credit as Week Off regardless of source (W/H)
                        'attendance_status_main' => 'W',
                        'availed_emp_attendance_id' => $attendance->id, // Current punch in attendance
                        'consumed_emp_attendance_id' => null, // Will be filled when consumed
                        'week_off_Status' => 'A' // Available status
                    ]);
                }
            } else {
                // On "out", find existing attendance
                $attendance = EmpAttendance::where([
                    'firm_id' => $firmId,
                    'employee_id' => $employeeId,
                    'work_date' => $workDate,
                ])->first();

                if (!$attendance) {
                    return response()->json([
                        'message_type' => 'error',
                        'message_display' => 'popup',
                        'message' => 'Cannot punch out without punching in first',
                    ], 400);
                }

                // Optionally update attendance on "out"
                $attendance->update([
                    'attend_remarks' => 'Punched out',
                ]);
            }

            // Create punch record
            $punch = EmpPunch::create([
                'firm_id' => $firmId,
                'employee_id' => $employeeId,
                'emp_attendance_id'=>$attendance->id,
                'work_date' => $workDate,
                'punch_datetime' => $punchDateTime,
                'in_out' => $request->in_out,
                'punch_geo_location' => json_encode([
                    'latitude' => $request->latitude,
                    'longitude' => $request->longitude,
                ]),
                'source_ip_address' => $request->ip(),
                'punch_type' => $request->punch_type,
                'is_final' => $isFinal,
            ]);

            if ($request->hasFile('selfie')) {

                $punch->addMediaFromRequest('selfie')->toMediaCollection('selfie');
            }


            // Check if an attendance record already exists for the day
            $attendance = DB::table('emp_attendances')
                ->where('firm_id', $firmId)
                ->where('employee_id', $employeeId)
                ->where('work_date', $workDate)
                ->first();

            if (!$attendance) {
                // Create a new attendance record for an "in" punch
                if ($request->in_out === 'in') {
                    DB::table('emp_attendances')->insert([
                        'firm_id' => $firmId,
                        'employee_id' => $employeeId,
                        'work_date' => $workDate,
                        'work_shift_day_id' => $workShiftDayId,
                        'ideal_working_hours' => $idealWorkingHours,
                        'actual_worked_hours' => 0, // To be updated on "out" punch
                        'final_day_weightage' => 0, // To be calculated later
                        'attend_remarks' => 'Punched in',
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    ]);
                }
            } else {
                // Update attendance record for an "out" punch
                if ($request->in_out === 'out' && $lastPunch && $lastPunch->in_out === 'in') {
                    $actualWorkedHours = $this->calculateWorkedHours($lastPunch->punch_datetime, $punchDateTime);
                    $finalDayWeightage = $actualWorkedHours >= $idealWorkingHours ? 1 : $actualWorkedHours / $idealWorkingHours;

                    DB::table('emp_attendances')
                        ->where('id', $attendance->id)
                        ->update([
                            'actual_worked_hours' => $actualWorkedHours,
                            'final_day_weightage' => $finalDayWeightage,
                            'attend_remarks' => 'Punched out',
                            'updated_at' => Carbon::now(),
                        ]);
                }
            }

            return response()->json([
                'message_type' => 'success',
                'message_display' => 'popup',
                'message' => 'Attendance punched successfully',
                'punch' => $punch,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message_type' => 'error',
                'message_display' => 'popup',
                'message' => 'Failed to punch attendance',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function punchStatus(Request $request)
    {
        $rules = [
            'firm_id' => 'required',
        ];
        $messages = [
            'firm_id.required' => 'No Firm is Selected',

        ];



        // Validate the request
        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            return response()->json([
                'message_type' => 'error',
                'message_display' => 'flash',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }
        // Get authenticated user (employee)
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'message_type' => 'error',
                'message_display' => 'flash',
                'message' => 'Unauthenticated'
            ], 401);
        }

        $employee = $user->employee; // Assuming a relationship exists between User and Employee
        if (!$employee) {
            return response()->json([
                'message_type' => 'error',
                'message_display' => 'flash',
                'message' => 'Employee not found'
            ], 404);
        }
        $firmId = $request->firm_id;
        $employeeId = $employee->id;

        // Fetch applicable attendance policy
        $policy = AttendancePolicy::where('firm_id', $firmId)
            ->where(function ($query) use ($employeeId) {
                $query->where('employee_id', $employeeId)
                    ->orWhereNull('employee_id');
            })
            ->first();

        if (!$policy) {
            return response()->json([
                'message_type' => 'error',
                'message_display' => 'flash',
                'message' => 'No attendance policy found'
            ], 400);
        }

        $workDate = Carbon::today()->toDateString();

                  //        print_r($policy->camshot_label); exit;

                   //        print_r(Carbon::now());

        $todaysPunches = EmpPunch::where('firm_id', $firmId)
            ->where('employee_id', $employeeId)
            ->where('work_date', $workDate)
            ->orderBy('punch_datetime', 'desc')
            ->get();






            // Add 'selfie_url' to each punch using Spatie Media Library
        $todaysPunches = $todaysPunches->map(function ($punch) {
            // Get the selfie media (assuming 'selfie' is the media collection name)
            $media = $punch->getMedia('selfie')->first();
            $punch->selfie_url = $media ? $media->getUrl() : null; // Get the URL if media exists
            return $punch;
        });

            //        dd($todaysPunches->last());
              // Define flags based on policy and context
        $flags = [
            'camshot_status' => $policy->camshot_label,
            'geo_status' => $policy->geo_label,
            'manual_marking_status' => $policy->manual_marking_label,
            'nextpunch' => !is_null($todaysPunches->first()) && !is_null($todaysPunches->first()->in_out)
                ? ($todaysPunches->first()->in_out == 'out' ? 'in' : 'out')
                : 'in'
        ];





        return response()->json([
            'message_type' => 'info',
            'message_display' => 'none',
            'message' => 'Today punches fetched',
            'punch_flags' => $flags,
            'todaypunches' => $todaysPunches,
        ], 201);




    }

    public function attendanceWithPunches(Request $request)
    {
        // Validate request parameters
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'firm_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }
        $user = $request->user();
        $employee = $user->employee; // Assuming a relationship exists between User and Employee
        $employeeId = $employee->id;
        // Ensure only one attendance per date (the latest record for that date)
        $latestAttendanceIds = EmpAttendance::where('firm_id', $request->firm_id)
            ->whereBetween('work_date', [$request->start_date, $request->end_date])
            ->when($employeeId, function ($query) use ($employeeId) {
                return $query->where('employee_id', $employeeId);
            })
            ->select(DB::raw('MAX(id) as id'))
            ->groupBy('work_date')
            ->pluck('id');

        $attendances = EmpAttendance::with(['punches' => function ($query) {
            $query->orderBy('punch_datetime', 'desc');
        }])
            ->whereIn('id', $latestAttendanceIds)
            ->orderby('work_date')
            ->get();

        // Add 'selfie_url' to each punch using Spatie Media Library
        $attendances->map(function ($attendance) {
            $attendance->weekday = \Carbon\Carbon::parse($attendance->work_date)->format('D');
            $attendance->punches = $attendance->punches->map(function ($punch) {
                $media = $punch->getMedia('selfie')->first();
                $punch->selfie_url = $media ? $media->getUrl() : null; // Assign URL if media exists
                return $punch;
            });

            return $attendance;
        });
        
        // Calculate status counts for the period
        $statusCounts = [];
        
        // Define statuses that should always be included
        $alwaysIncludeStatuses = ['P', 'A', 'LM', 'NM'];
        
        // Initialize counts for statuses that should always be included
        foreach ($alwaysIncludeStatuses as $code) {
            if (isset(EmpAttendance::ATTENDANCE_STATUS_MAIN_SELECT[$code])) {
                $statusCounts[$code] = [
                    'code' => $code,
                    'label' => EmpAttendance::ATTENDANCE_STATUS_MAIN_SELECT[$code],
                    'count' => 0
                ];
            }
        }
        
        // Count occurrences of each status
        foreach ($attendances as $attendance) {
            $status = $attendance->attendance_status_main ?? 'A'; // Default to Absent if null
            
            // If this is the first occurrence of this status and it's not in alwaysIncludeStatuses, initialize it
            if (!isset($statusCounts[$status]) && isset(EmpAttendance::ATTENDANCE_STATUS_MAIN_SELECT[$status])) {
                $statusCounts[$status] = [
                    'code' => $status,
                    'label' => EmpAttendance::ATTENDANCE_STATUS_MAIN_SELECT[$status],
                    'count' => 0
                ];
            }
            
            // Increment the count if the status exists in our tracking array
            if (isset($statusCounts[$status])) {
                $statusCounts[$status]['count']++;
            }
        }
        
        // Filter status counts to only include those with count > 0 or in alwaysIncludeStatuses
        $filteredStatusCounts = array_filter($statusCounts, function($item) use ($alwaysIncludeStatuses) {
            return $item['count'] > 0 || in_array($item['code'], $alwaysIncludeStatuses);
        });
        
        // Convert to array for JSON response
        $statusCountsArray = array_values($filteredStatusCounts);
        
        // Inclusive total days in the requested period
        $totalDays = Carbon::parse($request->start_date)->diffInDays(Carbon::parse($request->end_date)) + 1;
        
        return response()->json([
            'message_type' => 'success',
            'message_display' => 'flash',
            'message' => 'Attendance List Fetched',
            'attednances' => $attendances,
            'status_counts' => $statusCountsArray,
            'total_days' => $totalDays, 
        ], 201);
    }

// Helper function to calculate shift hours
    private function calculateShiftHours($startTime, $endTime)
    {
        // Parse the full timestamps directly
        $start = Carbon::parse($startTime);
        $end = Carbon::parse($endTime);

        // If end time is earlier than start time, assume it's an overnight shift and add a day
        if ($end->lessThan($start)) {
            $end->addDay();
        }

        // Calculate the difference in hours (with decimal precision)
        $hours = $end->floatDiffInHours($start);

        // Ensure the result is non-negative
        return max(0, $hours);
    }

// Helper function to calculate worked hours between punches
    private function calculateWorkedHours($inTime, $outTime)
    {
        $in = Carbon::parse($inTime);
        $out = Carbon::parse($outTime);
        $hours = $out->diffInHours($in);
        return max(0, $hours); // Ensure non-negative
    }


    private function validatePolicy(Request $request, AttendancePolicy $policy)
    {
// Check manual marking
        if ($request->punch_type === 'manual' && !$policy->allow_manual_marking) {
            abort(403, 'Manual attendance marking is not allowed.');
        }

// Check backdated attendance
        if ($request->has('punch_datetime') && !$policy->allow_backdated) {
            abort(403, 'Backdated attendance is not allowed.');
        }

// Validate IP range
        $ip = $request->ip();
        if ($policy->allowed_ip_ranges) {
            $allowedIps = json_decode($policy->allowed_ip_ranges, true);
            if (!$this->isIpInRange($ip, $allowedIps)) {
                abort(403, 'Punch not allowed from this IP address.');
            }
        }

// Validate geo-location
        if ($policy->allowed_geo_locations) {
            $allowedGeo = json_decode($policy->allowed_geo_locations, true);
            if (!$this->isGeoInRange($request->latitude, $request->longitude, $allowedGeo)) {
                abort(403, 'Punch not allowed from this location.');
            }
        }

// Check selfie requirement
        if ($policy->require_selfie && !$request->hasFile('selfie')) {
            abort(400, 'Selfie is required for this punch.');
        }

// Check max punches per day
        if ($policy->max_punches_per_day) {
            $todayPunches = EmpPunch::where('firm_id', $policy->firm_id)
                ->where('employee_id', auth()->user()->employee->id)
                ->whereDate('work_date', Carbon::today())
                ->count();
            if ($todayPunches >= $policy->max_punches_per_day) {
                abort(403, 'Maximum punches per day exceeded.');
            }
        }
    }

    private function isIpInRange($ip, $ranges)
    {
        foreach ($ranges as $range) {
            [$subnet, $mask] = explode('/', $range);
            $subnet = ip2long($subnet);
            $ipLong = ip2long($ip);
            $mask = ~((1 << (32 - $mask)) - 1);
            if (($ipLong & $mask) === ($subnet & $mask)) {
                return true;
            }
        }
        return false;
    }

    private function isGeoInRange($lat, $long, $allowedGeo)
    {
        foreach ($allowedGeo as $geo) {
            $distance = $this->haversineDistance($lat, $long, $geo['lat'], $geo['long']);
            if ($distance <= 0.1) { // 100 meters radius, adjust as needed
                return true;
            }
        }
        return false;
    }

    private function haversineDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // km
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c; // Distance in km
    }

    /**
     * Fetch all week offs with status 'A' (Available) for an employee
     */
    public function availableWeekOffs(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user || !$user->employee) {
                return response()->json([
                    'message_type' => 'error',
                    'message_display' => 'flash',
                    'message' => 'Employee not found',
                ], 404);
            }
            $employeeId = $user->employee->id;
            $firmId = $user->employee->firm_id;
            
            // Include the availed attendance relationship
            $weekOffs = \App\Models\Hrms\FlexiWeekOff::with('availedAttendance')
                ->where('firm_id', $firmId)
                ->where('employee_id', $employeeId)
                ->where('week_off_Status', 'A')
                ->get()
                ->map(function($weekOff) {
                    // Get the work date from availed attendance
                    $workDate = optional($weekOff->availedAttendance)->work_date;
                    
                    return [
                        'id' => $weekOff->id,
                        'firm_id' => $weekOff->firm_id,
                        'employee_id' => $weekOff->employee_id,
                        'attendance_status_main' => $weekOff->attendance_status_main,
                        'availed_emp_attendance_id' => $weekOff->availed_emp_attendance_id,
                        'consumed_emp_attendance_id' => $weekOff->consumed_emp_attendance_id,
                        'week_off_Status' => $weekOff->week_off_Status,
                        'week_date' => $workDate ? $workDate->format('Y-m-d') : null,
                        'week_day' => $workDate ? $workDate->format('l') : null // Returns full day name (Monday, Tuesday, etc.)
                    ];
                });

            if ($weekOffs->isEmpty()) {
                return response()->json([
                    'message_type' => 'error',
                    'message_display' => 'flash',
                    'message' => 'No weeks off available',
                    'week_offs' => [],
                ], 200);
            }
            
            return response()->json([
                'message_type' => 'success',
                'message_display' => 'none',
                'message' => 'Available week offs fetched',
                'week_offs' => $weekOffs,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message_type' => 'error',
                'message_display' => 'flash',
                'message' => 'Failed to fetch week offs',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Apply a week off for the authenticated employee
     * Request: { "date": "YYYY-MM-DD" }
     */
    public function applyWeekOff(Request $request)
    {
        try {
            $request->validate([
                'date' => 'required|date',
            ]);
            $user = $request->user();
            if (!$user || !$user->employee) {
                return response()->json([
                    'message_type' => 'error',
                    'message_display' => 'flash',
                    'message' => 'Employee not found',
                ], 404);
            }
            $employee = $user->employee;
            $firmId = $employee->firm_id;
            $employeeId = $employee->id;
            $date = $request->date;

            // Prevent duplicate week off application for the same date
            $existingAttendance = \App\Models\Hrms\EmpAttendance::where([
                'firm_id' => $firmId,
                'employee_id' => $employeeId,
                'work_date' => $date,
                'attendance_status_main' => 'W'
            ])->first();

            if ($existingAttendance) {
                return response()->json([
                    'message_type' => 'error',
                    'message_display' => 'flash',
                    'message' => 'You have already applied for a week off on this date.',
                ], 400);
            }

            // Find the first available week off
            $weekOff = \App\Models\Hrms\FlexiWeekOff::where('firm_id', $firmId)
                ->where('employee_id', $employeeId)
                ->where('week_off_Status', 'A')
                ->orderBy('id')
                ->first();
            if (!$weekOff) {
                return response()->json([
                    'message_type' => 'error',
                    'message_display' => 'flash',
                    'message' => 'No available week off to consume',
                ], 400);
            }

            // Create a new EmpAttendance record for the requested date
            $attendance = \App\Models\Hrms\EmpAttendance::create([
                'firm_id' => $firmId,
                'employee_id' => $employeeId,
                'work_date' => $date,
                'attendance_status_main' => 'W',
                'attend_remarks' => 'Flexi Week Off Availed',
                'ideal_working_hours' => 0,
                'actual_worked_hours' => 0,
                'final_day_weightage' => 0,
            ]);

            // Update the FlexiWeekOff record
            $weekOff->consumed_emp_attendance_id = $attendance->id;
            $weekOff->week_off_Status = 'C';
            $weekOff->save();

            return response()->json([
                'message_type' => 'success',
                'message_display' => 'flash',
                'message' => 'Week off applied successfully',
                'attendance' => $attendance,
                'flexi_week_off' => $weekOff,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message_type' => 'error',
                'message_display' => 'flash',
                'message' => 'Failed to apply week off',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Fetch all attendance statuSes for the authenticated user's firm
     * This API is optimized for scalability with caching and efficient queries
     */
    public function getAttendanceStatuses(Request $request)
    {
        try {
            // Check for authenticated user
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'message_type' => 'error',
                    'message_display' => 'flash',
                    'message' => 'Unauthenticated: No user found in request. Please provide a valid Bearer token.',
                    'error_code' => 'NO_USER',
                ], 401);
            }

            // Check for employee relationship
            if (!method_exists($user, 'employee')) {
                return response()->json([
                    'message_type' => 'error',
                    'message_display' => 'flash',
                    'message' => 'User model does not have an employee relationship. Please check your user setup.',
                    'error_code' => 'NO_EMPLOYEE_RELATION',
                ], 500);
            }

            $employee = $user->employee;
            if (!$employee) {
                return response()->json([
                    'message_type' => 'error',
                    'message_display' => 'flash',
                    'message' => 'Authenticated user does not have an employee record. Please onboard the user as an employee.',
                    'error_code' => 'NO_EMPLOYEE',
                ], 404);
            }

            if (!isset($employee->firm_id)) {
                return response()->json([
                    'message_type' => 'error',
                    'message_display' => 'flash',
                    'message' => 'Employee record does not have a firm_id. Please check employee data integrity.',
                    'error_code' => 'NO_FIRM_ID',
                ], 422);
            }

            $firmId = $employee->firm_id;

            // Use caching for better performance - cache for 1 hour
            $cacheKey = "attendance_statuses_firm_{$firmId}";
            
            $attendanceStatuses = \Cache::remember($cacheKey, 3600, function () use ($firmId) {
                return \App\Models\Hrms\EmpAttendanceStatuses::select([
                    'id',
                    'attendance_status_code',
                    'attendance_status_label',
                    'attendance_status_desc',
                    'paid_percent',
                    'attendance_status_main',
                    'attribute_json',
                    'is_inactive',
                    'work_shift_id'
                ])
                ->where('firm_id', $firmId)
                ->where('is_inactive', false) // Only active statuses
                ->orderBy('attendance_status_label', 'asc')
                ->get()
                ->map(function ($status) {
                    // Add computed attributes
                    $status->attendance_status_main_label = $status->attendance_status_main_label;
                    
                    // Only include necessary fields in response
                    return [
                        'id' => $status->id,
                        'attendance_status_label' => $status->attendance_status_code,
                        'attendance_status_code' => $status->attendance_status_label,
                        'attendance_status_desc' => $status->attendance_status_desc,
                        'paid_percent' => $status->paid_percent,
                        'attendance_status_main' => $status->attendance_status_main,
                        'attendance_status_main_label' => $status->attendance_status_main_label,
                        'attribute_json' => $status->attribute_json,
                        'work_shift_id' => $status->work_shift_id,
                    ];
                });
            });

            if ($attendanceStatuses->count() === 0) {
                return response()->json([
                    'message_type' => 'warning',
                    'message_display' => 'flash',
                    'message' => 'No attendance statuses found for this firm. Please configure attendance statuses.',
                    'error_code' => 'NO_ATTENDANCE_STATUSES',
                    'data' => [
                        'firm_id' => $firmId
                    ]
                ], 200);
            }

            // Add metadata for better API response
            $response = [
                'message_type' => 'success',
                'message_display' => 'none',
                'message' => 'Attendance statuses fetched successfully',
                'data' => [
                    'attendance_statuses' => $attendanceStatuses,
                    'total_count' => $attendanceStatuses->count(),
                    'firm_id' => $firmId,
                    'cached_at' => now()->toISOString(),
                ]
            ];

            // Set cache headers for better performance
            return response()->json($response, 200)
                ->header('Cache-Control', 'public, max-age=3600')
                ->header('ETag', md5(json_encode($response)));

        } catch (\Exception $e) {
            \Log::error('Error fetching attendance statuses: ' . $e->getMessage(), [
                'user_id' => isset($user) ? ($user->id ?? null) : null,
                'firm_id' => isset($firmId) ? $firmId : null,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message_type' => 'error',
                'message_display' => 'flash',
                'message' => 'Failed to fetch attendance statuses. Internal server error.',
                'error_code' => 'INTERNAL_ERROR',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Mark attendance status for a specific date (manual marking)
     * Sets both attendance_status_main and emp_attendance_status_id
     * POST /api/attendance/mark-status
     * Required: date (Y-m-d), attendance_status_main, emp_attendance_status_id
     * Optional: remarks
     */
    public function markAttendanceStatus(Request $request)
    {
        try {
            // Validate input
            $validator = \Validator::make($request->all(), [
                'date' => 'required|date',
                'attendance_status_main' => 'required|string',
                'emp_attendance_status_id' => 'required|integer|exists:emp_attendance_statuses,id',
                'remarks' => 'nullable|string|max:255',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'message_type' => 'error',
                    'message_display' => 'flash',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                    'error_code' => 'VALIDATION_ERROR',
                ], 422);
            }

            // Authenticated user and employee
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'message_type' => 'error',
                    'message_display' => 'flash',
                    'message' => 'Unauthenticated: No user found in request. Please provide a valid Bearer token.',
                    'error_code' => 'NO_USER',
                ], 401);
            }
            $employee = $user->employee;
            if (!$employee) {
                return response()->json([
                    'message_type' => 'error',
                    'message_display' => 'flash',
                    'message' => 'Authenticated user does not have an employee record. Please onboard the user as an employee.',
                    'error_code' => 'NO_EMPLOYEE',
                ], 404);
            }
            $firmId = $employee->firm_id;
            $employeeId = $employee->id;
            $workDate = $request->date;

            // Find or create EmpAttendance for this date
            $attendance = \App\Models\Hrms\EmpAttendance::updateOrCreate(
                [
                    'firm_id' => $firmId,
                    'employee_id' => $employeeId,
                    'work_date' => $workDate,
                ],
                [
                    'attendance_status_main' => $request->attendance_status_main,
                    'emp_attendance_status_id' => $request->emp_attendance_status_id,
                    'attend_remarks' => $request->remarks,
                ]
            );

            return response()->json([
                'message_type' => 'success',
                'message_display' => 'flash',
                'message' => 'Attendance status marked successfully',
                'data' => [
                    'attendance' => $attendance
                ]
            ], 200);
        } catch (\Exception $e) {
            \Log::error('Error marking attendance status: ' . $e->getMessage(), [
                'user_id' => isset($user) ? ($user->id ?? null) : null,
                'firm_id' => isset($firmId) ? $firmId : null,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message_type' => 'error',
                'message_display' => 'flash',
                'message' => 'Failed to mark attendance status. Internal server error.',
                'error_code' => 'INTERNAL_ERROR',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Fetch the last 12 week offs (availed or consumed) for the authenticated employee
     * Returns week off date, day, status, and related attendance info
     */
    public function lastWeekOffs(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user || !$user->employee) {
                return response()->json([
                    'message_type' => 'error',
                    'message_display' => 'flash',
                    'message' => 'Employee not found',
                ], 404);
            }
            $employeeId = $user->employee->id;
            $firmId = $user->employee->firm_id;

            // Fetch last 12 week offs (availed or consumed)
            $weekOffs = \App\Models\Hrms\FlexiWeekOff::with(['availedAttendance', 'consumedAttendance'])
                ->where('firm_id', $firmId)
                ->where('employee_id', $employeeId)
                ->orderByDesc('id')
                ->limit(12)
                ->get()
                ->map(function($weekOff) {
                    // Prefer availed attendance for date, else consumed
                    $attendance = $weekOff->availedAttendance ?: $weekOff->consumedAttendance;
                    $workDate = optional($attendance)->work_date;
                    $statusLabel = \App\Models\Hrms\FlexiWeekOff::WEEK_OFF_STATUS_MAIN_SELECT[$weekOff->week_off_Status] ?? $weekOff->week_off_Status;
                    $attendanceStatusLabel = $attendance && isset(\App\Models\Hrms\EmpAttendance::ATTENDANCE_STATUS_MAIN_SELECT[$attendance->attendance_status_main])
                        ? \App\Models\Hrms\EmpAttendance::ATTENDANCE_STATUS_MAIN_SELECT[$attendance->attendance_status_main]
                        : null;
                    $availedAttendance = $weekOff->availedAttendance;
                    $consumedAttendance = $weekOff->consumedAttendance;
                    $availedDate = $availedAttendance && $availedAttendance->work_date
                        ? (is_string($availedAttendance->work_date) ? $availedAttendance->work_date : $availedAttendance->work_date->format('Y-m-d'))
                        : null;
                    $consumedDate = $consumedAttendance && $consumedAttendance->work_date
                        ? (is_string($consumedAttendance->work_date) ? $consumedAttendance->work_date : $consumedAttendance->work_date->format('Y-m-d'))
                        : null;
                    // Add day of week fields
                    $availedDay = $availedDate ? (\Carbon\Carbon::parse($availedDate)->format('l')) : null;
                    $consumedDay = $consumedDate ? (\Carbon\Carbon::parse($consumedDate)->format('l')) : null;
                    return [
                        'id' => $weekOff->id,
                        'firm_id' => $weekOff->firm_id,
                        'employee_id' => $weekOff->employee_id,
                        'availed_emp_attendance_id' => $weekOff->availed_emp_attendance_id,
                        'consumed_emp_attendance_id' => $weekOff->consumed_emp_attendance_id,
                        'week_off_Status' => $weekOff->week_off_Status,
                        'week_off_Status_label' => 
                            isset(\App\Models\Hrms\FlexiWeekOff::WEEK_OFF_STATUS_MAIN_SELECT[$weekOff->week_off_Status])
                                ? \App\Models\Hrms\FlexiWeekOff::WEEK_OFF_STATUS_MAIN_SELECT[$weekOff->week_off_Status]
                                : $weekOff->week_off_Status,
                        'availed_date' => $availedDate,
                        'availed_day' => $availedDay,
                        'consumed_date' => $consumedDate,
                        'consumed_day' => $consumedDay,

                        'consumed' => $weekOff->consumedAttendance ? true : false,
                    ];
                });

            if ($weekOffs->isEmpty()) {
                return response()->json([
                    'message_type' => 'info',
                    'message_display' => 'none',
                    'message' => 'No week offs found for the employee',
                    'week_offs' => [],
                ], 200);
            }

            return response()->json([
                'message_type' => 'success',
                'message_display' => 'none',
                'message' => 'Last 12 week offs fetched',
                'week_offs' => $weekOffs,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message_type' => 'error',
                'message_display' => 'flash',
                'message' => 'Failed to fetch week offs',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

}