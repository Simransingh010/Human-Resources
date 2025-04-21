<?php

namespace App\Http\Controllers\API\Hrms\Attendance;

use App\Http\Controllers\Controller;
use App\Models\Hrms\AttendancePolicy;

// Updated namespace
use App\Models\Hrms\EmpPunch;
use App\Models\Hrms\EmpAttendance;


// Updated namespace
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class AttendanceController extends Controller
{

    public function punch(Request $request)
    {
        try {
            // Define validation rules
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

            // Fetch the employee's assigned work shift for the day
            $empWorkShift = DB::table('emp_work_shifts')
                ->where('firm_id', $firmId)
                ->where('employee_id', $employeeId)
                ->where('start_date', '<=', $workDate)
                ->where(function ($query) use ($workDate) {
                    $query->where('end_date', '>=', $workDate)
                        ->orWhereNull('end_date');
                })
                ->first();

            if (!$empWorkShift) {
                return response()->json([
                    'message_type' => 'error',
                    'message_display' => 'popup',
                    'message' => 'No work shift assigned for today!!'
                ], 400);
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
                    'message' => 'No work shift day defined for today'
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

            // Make Entry in emp_attendances table first to get the running id to be linked with punches
            // Only create attendance on "in"
            if ($request->in_out === 'in') {
                $attendance = EmpAttendance::updateOrCreate(
                    [
                        'firm_id' => $firmId,
                        'employee_id' => $employeeId,
                        'work_date' => $workDate,
                    ],
                    [
                        'work_shift_day_id' => $workShiftDayId,
                        'ideal_working_hours' => $idealWorkingHours,
                        'actual_worked_hours' => 0,
                        'final_day_weightage' => 0,
                        'attend_remarks' => 'Punched in',
                        'attendance_status_main'=>'P',
                    ]
                );
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
                'punch_details' => json_encode(['user_agent' => $request->userAgent()]),
                'punch_type' => $request->punch_type,
                'is_final' => $isFinal,
            ]);

            if ($request->hasFile('selfie')) {
//                dd('hi');
                $punch->addMediaFromRequest('selfie')->toMediaCollection('selfie');
//                $selfiePath = $request->file('selfie')->store('selfies', 'public');
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



//        dd($todaysPunches);

// Return the response with the added 'selfie_url' field

//        print_r($todaysPunches);

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
        $attendances = EmpAttendance::with(['punches' => function ($query) {
            $query->orderBy('punch_datetime', 'desc');
        }])
            ->where('firm_id', $request->firm_id)
            ->whereBetween('work_date', [$request->start_date, $request->end_date])
            ->when($employeeId, function ($query) use ($employeeId) {
                return $query->where('employee_id', $employeeId);
            })
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
        return response()->json([
            'message_type' => 'success',
            'message_display' => 'flash',
            'message' => 'Attendance List Fetched',
            'attednances' => $attendances,
        ], 201);
    }

// Helper function to calculate shift hours
    private function calculateShiftHours($startTime, $endTime)
    {
        // Parse the full timestamps directly
        $start = Carbon::parse($startTime);
        $end = Carbon::parse($endTime);

        // If end time is earlier than start time, assume it’s an overnight shift and add a day
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


}
