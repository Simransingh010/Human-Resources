<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class EsslDirectSync extends Command
{
    protected $signature = 'attendance:direct-sync';
    protected $description = 'Sync ESSL biometric data to HRMS tables (firm_id=28, IN/OUT logic, robust error handling, attendance linking)';

    public function handle()
    {
        $firmId = 28;

        // Fetch unsynced biometric rows (adjust limit for batch size)
        $rows = DB::connection('essl_db')
            ->table('biometric')
            ->where('synced', 0)
            ->orderBy('EmpCode')
            ->orderBy('LogDateTime')
            ->limit(10000)
            ->get();

        if ($rows->isEmpty()) {
            $this->info("No unsynced biometric records found.");
            return;
        }

        // Group punches by EmpCode + Date
        $byEmpDate = [];
        foreach ($rows as $row) {
            $date = date('Y-m-d', strtotime($row->LogDateTime));
            $byEmpDate[$row->EmpCode][$date][] = $row;
        }

        foreach ($byEmpDate as $empCode => $dates) {
            // 1. Get HRMS profile for this biometric empCode
            $profile = DB::table('employee_job_profiles')
                ->where('firm_id', $firmId)
                ->where('biometric_emp_code', $empCode)
                ->first();

            if (!$profile) {
                $this->error("No HRMS employee for ESSL EmpCode: {$empCode}");
                foreach ($dates as $punches) {
                    foreach ($punches as $row) {
                        DB::connection('essl_db')->table('biometric')
                            ->where('id', $row->id)
                            ->update(['synced' => 5]); // Employee Not Found
                    }
                }
                continue;
            }
            $employeeId = $profile->employee_id;

            foreach ($dates as $date => $punches) {
                // Gather all punch IDs for this employee/date
                $biometricIds = array_map(fn($r) => $r->id, $punches);

                // 2. Leave check
                $onLeave = DB::table('emp_attendances')
                    ->where('firm_id', $firmId)
                    ->where('employee_id', $employeeId)
                    ->where('work_date', $date)
                    ->where('attendance_status_main', 'L')
                    ->exists();
                if ($onLeave) {
                    $this->warn("Employee $employeeId on leave for $date, skipping all punches for the day.");
                    DB::connection('essl_db')->table('biometric')
                        ->whereIn('id', $biometricIds)
                        ->update(['synced' => 2]);
                    continue;
                }

                // 3. Work shift check
                $empWorkShift = DB::table('emp_work_shifts')
                    ->where('firm_id', $firmId)
                    ->where('employee_id', $employeeId)
                    ->where('start_date', '<=', $date)
                    ->where(function ($query) use ($date) {
                        $query->where('end_date', '>=', $date)
                            ->orWhereNull('end_date');
                    })->first();
                if (!$empWorkShift) {
                    $this->warn("No work shift for employee $employeeId ($empCode) on $date, skipping.");
                    DB::connection('essl_db')->table('biometric')
                        ->whereIn('id', $biometricIds)
                        ->update(['synced' => 3]);
                    continue;
                }

                // 4. Work shift day check
                $workShiftDay = DB::table('work_shift_days')
                    ->where('firm_id', $firmId)
                    ->where('work_shift_id', $empWorkShift->work_shift_id)
                    ->where('work_date', $date)
                    ->first();
                if (!$workShiftDay) {
                    $this->warn("No work shift day for employee $employeeId ($empCode) on $date, skipping.");
                    DB::connection('essl_db')->table('biometric')
                        ->whereIn('id', $biometricIds)
                        ->update(['synced' => 4]);
                    continue;
                }

                $workShiftDayId = $workShiftDay->id;
                $idealWorkingHours = $this->calculateShiftHours($workShiftDay->start_time, $workShiftDay->end_time);

                // 1. Sort punches just in case (already ordered in outer query, but for safety)
                usort($punches, function ($a, $b) {
                    return strtotime($a->LogDateTime) <=> strtotime($b->LogDateTime);
                });

// 2. Filter out rapid (duplicate) punches
                $punches = $this->filterRapidPunches($punches);

// 3. Now assign in_out and process as before
                // Determine initial state from existing punches to avoid consecutive 'in' entries
                $existingPunches = DB::table('emp_punches')
                    ->where([
                        'firm_id' => $firmId,
                        'employee_id' => $employeeId,
                        'work_date' => $date,
                    ])
                    ->orderBy('punch_datetime', 'asc')
                    ->get();

                $lastInOut = $existingPunches->isNotEmpty() ? $existingPunches->last()->in_out : null;

                foreach ($punches as $i => $row) {
                    // Toggle based on last seen punch (persisted or from this loop)
                    $inOut = $lastInOut === 'in' ? 'out' : 'in';
                    $punchDateTime = $row->LogDateTime;

                    // Always get or create attendance, and get its id
                    $attendance = DB::table('emp_attendances')
                        ->where([
                            'firm_id' => $firmId,
                            'employee_id' => $employeeId,
                            'work_date' => $date,
                        ])->first();

                    if (!$attendance) {
                        // Create with max info if "in", minimal otherwise
                        $attendanceId = DB::table('emp_attendances')->insertGetId([
                            'firm_id' => $firmId,
                            'employee_id' => $employeeId,
                            'work_date' => $date,
                            'work_shift_day_id' => $workShiftDayId,
                            'ideal_working_hours' => $idealWorkingHours,
                            'actual_worked_hours' => 0,
                            'final_day_weightage' => 0,
                            'attend_remarks' => $inOut === 'in' ? 'Punched in (auto-import)' : null,
                            'attendance_status_main' => $inOut === 'in' ? 'P' : null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    } else {
                        $attendanceId = $attendance->id;
                    }

                    // On OUT punch, update attendance worked hours/weightage if possible
                    if ($inOut === 'out') {
                        $attendance = DB::table('emp_attendances')
                            ->where([
                                'firm_id' => $firmId,
                                'employee_id' => $employeeId,
                                'work_date' => $date,
                            ])->first();

                        // Find the last IN punch for this OUT
                        $lastInPunch = DB::table('emp_punches')
                            ->where([
                                'firm_id' => $firmId,
                                'employee_id' => $employeeId,
                                'work_date' => $date,
                                'in_out' => 'in'
                            ])
                            ->where('punch_datetime', '<', $punchDateTime)
                            ->orderBy('punch_datetime', 'desc')
                            ->first();

                        if ($attendance && $lastInPunch) {
                            $actualWorkedHours = $this->calculateWorkedHours($lastInPunch->punch_datetime, $punchDateTime);
                            $finalDayWeightage = $idealWorkingHours > 0
                                ? min(1, $actualWorkedHours / $idealWorkingHours)
                                : 0;

                            DB::table('emp_attendances')
                                ->where('id', $attendance->id)
                                ->update([
                                    'actual_worked_hours' => $actualWorkedHours,
                                    'final_day_weightage' => $finalDayWeightage,
                                    'attend_remarks' => 'Punched out',
                                    'updated_at' => now(),
                                ]);
                        }
                    }

                    // Always upsert punch with emp_attendance_id
                    DB::table('emp_punches')->updateOrInsert(
                        [
                            'firm_id' => $firmId,
                            'employee_id' => $employeeId,
                            'work_date' => $date,
                            'punch_datetime' => $punchDateTime,
                            'in_out' => $inOut,
                        ],
                        [
                            'emp_attendance_id' => $attendanceId,
                            'punch_geo_location' => json_encode([
                                'latitude' => $row->udf_1 ?: 0,
                                'longitude' => $row->udf_2 ?: 0,
                            ]),
                            'source_ip_address' => $row->DeviceSerialNo,
                            'punch_type' => 'auto',
                            'is_final' => 0,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );

                    // Update last state to reflect the punch we just recorded
                    $lastInOut = $inOut;

                    // Mark this biometric row as synced = 1 (success)
                    DB::connection('essl_db')->table('biometric')
                        ->where('id', $row->id)
                        ->update(['synced' => 1]);
                }
            }
        }

        $this->info("ESSL biometric sync completed.");
    }

    protected function calculateShiftHours($start, $end)
    {
        if (!$start || !$end) return 0;
        $startTs = strtotime($start);
        $endTs = strtotime($end);
        if ($endTs < $startTs) $endTs += 86400;
        return round(($endTs - $startTs) / 3600, 2);
    }

    protected function filterRapidPunches($punches)
    {
        if (empty($punches)) return [];

        $filtered = [];
        $currentGroup = [$punches[0]];

        for ($i = 1; $i < count($punches); $i++) {
            $currentTs = strtotime($punches[$i]->LogDateTime);
            $lastTs = strtotime($currentGroup[count($currentGroup) - 1]->LogDateTime);

            if (($currentTs - $lastTs) <= 60) {
                // Within 60 seconds - add to current group
                $currentGroup[] = $punches[$i];
            } else {
                // More than 60 seconds - finalize current group and start new one
                // Take the LAST punch from the group (most recent)
                $filtered[] = $currentGroup[count($currentGroup) - 1];
                $currentGroup = [$punches[$i]];
            }
        }

        // Don't forget the last group
        if (!empty($currentGroup)) {
            $filtered[] = $currentGroup[count($currentGroup) - 1];
        }

        return $filtered;
    }

    protected function calculateWorkedHours($in, $out)
    {
        if (!$in || !$out) return 0;
        $inTs = strtotime($in);
        $outTs = strtotime($out);
        if ($outTs < $inTs) return 0;
        return round(($outTs - $inTs) / 3600, 2);
    }

}
