<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EsslMonthlySyncService
{
    public function syncCurrentMonthForFirm(int $firmId = 29, int $limit = 10000): void
    {
        $month = date('n');
        $year = date('Y');
        $deviceLogsTable = "DeviceLogs_{$month}_{$year}";

        // Check if current month's DeviceLogs table exists; if not, abort gracefully
        if (!Schema::connection('iqwingl_essl')->hasTable($deviceLogsTable)) {
            // You can choose to log, throw exception, or just return
            // \Log::warning("Device logs table $deviceLogsTable not found for ESSL sync.");
            return; // aborts without error
        }

        // Fetch unsynced/pending rows from current month's table
        $rows = DB::connection('iqwingl_essl')
            ->table($deviceLogsTable)
            ->where(function ($q) {
                $q->whereNull('hrapp_syncstatus')->orWhere('hrapp_syncstatus', 0);
            })
            ->orderBy('UserId')
            ->orderBy('LogDate')    
            ->limit($limit)
            ->get();

        if ($rows->isEmpty()) {
            return;
        }

        // Group punches by UserId and work date
        $byUserDate = [];
        foreach ($rows as $row) {
            $date = date('Y-m-d', strtotime($row->LogDate));
            $byUserDate[$row->UserId][$date][] = $row;
        }

        foreach ($byUserDate as $userId => $dates) {
            // Map UserId to HRMS employee via biometric_emp_code
            $profile = DB::table('employee_job_profiles')
                ->where('firm_id', $firmId)
                ->where('biometric_emp_code', $userId)
                ->first();

            if (!$profile) {
                foreach ($dates as $punches) {
                    foreach ($punches as $row) {
                        $this->markEsslRow($deviceLogsTable, $row->DeviceLogId, 5, 'Employee not found');
                    }
                }
                continue;
            }
            $employeeId = $profile->employee_id;

            foreach ($dates as $date => $punches) {
                $deviceLogIds = array_map(fn($r) => $r->DeviceLogId, $punches);

                // Leave check
                $onLeave = DB::table('emp_attendances')
                    ->where('firm_id', $firmId)
                    ->where('employee_id', $employeeId)
                    ->where('work_date', $date)
                    ->where('attendance_status_main', 'L')
                    ->exists();
                if ($onLeave) {
                    DB::connection('iqwingl_essl')->table($deviceLogsTable)
                        ->whereIn('DeviceLogId', $deviceLogIds)
                        ->update(['hrapp_syncstatus' => 2]);
                    foreach ($deviceLogIds as $id) {
                        $this->trackSync($deviceLogsTable, $id, 2, 'On leave');
                    }
                    continue;
                }

                // Work shift for employee on date
                $empWorkShift = DB::table('emp_work_shifts')
                    ->where('firm_id', $firmId)
                    ->where('employee_id', $employeeId)
                    ->where('start_date', '<=', $date)
                    ->where(function ($query) use ($date) {
                        $query->where('end_date', '>=', $date)->orWhereNull('end_date');
                    })
                    ->first();
                if (!$empWorkShift) {
                    DB::connection('iqwingl_essl')->table($deviceLogsTable)
                        ->whereIn('DeviceLogId', $deviceLogIds)
                        ->update(['hrapp_syncstatus' => 3]);
                    foreach ($deviceLogIds as $id) {
                        $this->trackSync($deviceLogsTable, $id, 3, 'No work shift');
                    }
                    continue;
                }

                // Work shift day
                $workShiftDay = DB::table('work_shift_days')
                    ->where('firm_id', $firmId)
                    ->where('work_shift_id', $empWorkShift->work_shift_id)
                    ->where('work_date', $date)
                    ->first();
                if (!$workShiftDay) {
                    DB::connection('iqwingl_essl')->table($deviceLogsTable)
                        ->whereIn('DeviceLogId', $deviceLogIds)
                        ->update(['hrapp_syncstatus' => 4]);
                    foreach ($deviceLogIds as $id) {
                        $this->trackSync($deviceLogsTable, $id, 4, 'No work shift day');
                    }
                    continue;
                }

                $workShiftDayId = $workShiftDay->id;
                $idealWorkingHours = $this->calculateShiftHours($workShiftDay->start_time, $workShiftDay->end_time);

                // Sort by LogDate then filter rapid punches (<=60s)
                usort($punches, function ($a, $b) {
                    return strtotime($a->LogDate) <=> strtotime($b->LogDate);
                });
                $punches = $this->filterRapidPunches($punches);

                // Get last state from existing punches
                $existingPunches = DB::table('emp_punches')
                    ->where([
                        'firm_id' => $firmId,
                        'employee_id' => $employeeId,
                        'work_date' => $date,
                    ])
                    ->orderBy('punch_datetime', 'asc')
                    ->get();
                $lastInOut = $existingPunches->isNotEmpty() ? $existingPunches->last()->in_out : null;

                foreach ($punches as $row) {
                    $inOut = $lastInOut === 'in' ? 'out' : 'in';
                    $punchDateTime = $row->LogDate;

                    // Ensure attendance exists
                    $attendance = DB::table('emp_attendances')
                        ->where([
                            'firm_id' => $firmId,
                            'employee_id' => $employeeId,
                            'work_date' => $date,
                        ])->first();

                    if (!$attendance) {
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

                    if ($inOut === 'out') {
                        $attendance = DB::table('emp_attendances')
                            ->where([
                                'firm_id' => $firmId,
                                'employee_id' => $employeeId,
                                'work_date' => $date,
                            ])->first();

                        $lastInPunch = DB::table('emp_punches')
                            ->where([
                                'firm_id' => $firmId,
                                'employee_id' => $employeeId,
                                'work_date' => $date,
                                'in_out' => 'in',
                            ])
                            ->where('punch_datetime', '<', $punchDateTime)
                            ->orderBy('punch_datetime', 'desc')
                            ->first();

                        if ($attendance && $lastInPunch) {
                            $actualWorkedHours = $this->calculateWorkedHours($lastInPunch->punch_datetime, $punchDateTime);
                            $finalDayWeightage = $idealWorkingHours > 0 ? min(1, $actualWorkedHours / $idealWorkingHours) : 0;

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
                                'latitude' => $row->C1 ?: 0,
                                'longitude' => $row->C2 ?: 0,
                            ]),
                            'source_ip_address' => (string)($row->DeviceId ?? ''),
                            'punch_type' => 'auto',
                            'is_final' => 0,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );

                    // Success markers for this row
                    DB::connection('iqwingl_essl')->table($deviceLogsTable)
                        ->where('DeviceLogId', $row->DeviceLogId)
                        ->update(['hrapp_syncstatus' => 1]);
                    $this->trackSync($deviceLogsTable, $row->DeviceLogId, 1, 'Synced');

                    $lastInOut = $inOut;
                }
            }
        }
    }

    protected function trackSync(string $deviceLogsTable, $deviceLogsRecId, int $synced, string $remarks = null): void
    {
        DB::connection('iqwingl_essl')->table('iq_sync_track')->insert([
            'deviceLogsTable' => $deviceLogsTable,
            'deviceLogsRecId' => $deviceLogsRecId,
            'synced' => $synced,
        ]);
    }

    protected function markEsslRow(string $deviceLogsTable, $deviceLogsRecId, int $status, string $remarks): void
    {
        DB::connection('iqwingl_essl')->table($deviceLogsTable)
            ->where('DeviceLogId', $deviceLogsRecId)
            ->update(['hrapp_syncstatus' => $status]);
        $this->trackSync($deviceLogsTable, $deviceLogsRecId, $status, $remarks);
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
            $currentTs = strtotime($punches[$i]->LogDate);
            $lastTs = strtotime($currentGroup[count($currentGroup) - 1]->LogDate);

            if (($currentTs - $lastTs) <= 60) {
                $currentGroup[] = $punches[$i];
            } else {
                $filtered[] = $currentGroup[count($currentGroup) - 1];
                $currentGroup = [$punches[$i]];
            }
        }

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


