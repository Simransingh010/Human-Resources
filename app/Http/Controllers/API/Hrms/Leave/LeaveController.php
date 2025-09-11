<?php

namespace App\Http\Controllers\API\Hrms\Leave;

use App\Http\Controllers\Controller;
use App\Models\NotificationQueue;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Hrms\EmpLeaveBalance;  // ← make sure this path matches your model
use App\Models\Hrms\EmpAttendance;
use App\Models\Hrms\EmpLeaveRequest;
use App\Models\Hrms\EmpLeaveRequestApproval;
use App\Models\Hrms\LeaveRequestEvent;
use App\Models\Hrms\LeaveApprovalRule;
use App\Models\Hrms\LeaveType;
use App\Models\Hrms\Employee;
use App\Models\Hrms\EmpLeaveTransaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\App;

class LeaveController extends Controller
{
    /**
     * GET /api/hrms/pol-attendances
     * Return ALL POL attendances for the authenticated user's firm (no approver filtering).
     */
    public function getPolAttendancesForApprover(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user || !$user->employee) {
                return Response::json([
                    'message_type' => 'error',
                    'message_display' => 'flash',
                    'message' => 'Unauthenticated'
                ], 401);
            }

            $employee = $user->employee;

            // Fetch ALL POL attendances for this firm (raw query to avoid any model-level scopes/soft-delete side effects)
            $attendances = DB::table('emp_attendances as ea')
                ->leftJoin('employees as e', 'e.id', '=', 'ea.employee_id')
                ->where('ea.firm_id', $employee->firm_id)
                ->where('ea.attendance_status_main', 'POL')
                ->orderBy('ea.work_date', 'desc')
                ->select([
                    'ea.id',
                    'ea.employee_id',
                    'ea.work_date',
                    'ea.attendance_status_main',
                    'ea.attend_remarks',
                    DB::raw("TRIM(CONCAT(COALESCE(e.fname,''),' ',COALESCE(NULLIF(e.mname,''),'') ,CASE WHEN e.mname IS NULL OR e.mname='' THEN '' ELSE ' ' END, COALESCE(e.lname,''))) as employee_name")
                ])
                ->get();

            

            // Shape minimal payload without permission filtering
            $data = $attendances->map(function ($attendance) {
                return [
                    'id' => $attendance->id,
                    'employee_id' => $attendance->employee_id,
                    'employee_name' => $attendance->employee_name ?: 'N/A',
                    'work_date' => Carbon::parse($attendance->work_date)->toDateString(),
                    'attendance_status_main' => $attendance->attendance_status_main,
                    'attend_remarks' => $attendance->attend_remarks,
                ];
            })->values();

            return Response::json([
                'message_type' => 'success',
                'firm_id' => $employee->firm_id,
                'message_display' => 'none',
                'message' => $data->count() . ' POL attendances found',
                'data' => $data,
            ], 200);

        } catch (\Throwable $e) {
            Log::error('Failed to fetch POL attendances: ' . $e->getMessage());
            return Response::json([
                'message_type' => 'error',
                'message_display' => 'popup',
                'message' => 'Server error: ' . $e->getMessage(),
                'data' => [],
            ], 500);
        }
    }

    /**
     * Find an approved/approved_further leave that covers the given date for the employee.
     */
    private function findCoveringLeaveRequestForDate(int $employeeId, int $firmId, $date)
    {
        $workDate = Carbon::parse($date)->toDateString();
        return EmpLeaveRequest::with('leave_type')
            ->where('employee_id', $employeeId)
            ->where('firm_id', $firmId)
            ->whereDate('apply_from', '<=', $workDate)
            ->whereDate('apply_to', '>=', $workDate)
            ->whereIn('status', ['approved', 'approved_further'])
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Check if the authenticated user can approve POL for a specific attendance.
     */
    private function canUserApprovePolForAttendance($user, EmpAttendance $attendance, ?EmpLeaveRequest $coveringLeave): bool
    {
        $result = $this->canUserApprovePolForAttendanceDetailed($user, $attendance, $coveringLeave);
        return $result['can_approve'] === true;
    }

    /**
     * Detailed permission check with reason codes for POL approval.
     */
    private function canUserApprovePolForAttendanceDetailed($user, EmpAttendance $attendance, ?EmpLeaveRequest $coveringLeave): array
    {
        if (!$user) {
            return ['can_approve' => false, 'reason' => 'unauthenticated'];
        }
        if (!$user->id) {
            return ['can_approve' => false, 'reason' => 'invalid_user'];
        }
        if (!$coveringLeave) {
            return ['can_approve' => false, 'reason' => 'no_covering_leave'];
        }

        $rules = LeaveApprovalRule::with(['employees'])
            ->where('firm_id', $attendance->firm_id)
            ->where('approver_id', $user->id)
            ->where(function ($query) use ($coveringLeave) {
                $query->where('leave_type_id', $coveringLeave->leave_type_id)
                    ->orWhereNull('leave_type_id');
            })
            ->where('is_inactive', false)
            ->get();

        if ($rules->isEmpty()) {
            return ['can_approve' => false, 'reason' => 'no_matching_rule'];
        }

        // Filter out view_only and out-of-scope rules
        $eligible = $rules->first(function ($rule) use ($attendance) {
            if (($rule->approval_mode ?? null) === 'view_only') {
                return false;
            }
            // Must include this employee in rule scope
            return $rule->employees->contains('id', $attendance->employee_id)
                || ($rule->employee_scope === 'all' || $rule->department_scope === 'all');
        });

        if (!$eligible) {
            return ['can_approve' => false, 'reason' => 'not_in_rule_scope_or_view_only'];
        }

        return ['can_approve' => true, 'reason' => null];
    }

    /**
     * POST /api/hrms/leave/pol-attendance-action
     * Approve or reject a single POL attendance with optional remarks.
     */
    public function handlePolAttendanceAction(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'attendance_id' => 'required|integer|exists:emp_attendances,id',
                'action' => 'required|in:accept,reject',
                'remarks' => 'nullable|string|min:3',
            ]);

            if ($validator->fails()) {
                return Response::json([
                    'message_type' => 'error',
                    'message_display' => 'popup',
                    'message' => 'Invalid parameters',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $user = $request->user();
            if (!$user || !$user->employee) {
                return Response::json([
                    'message_type' => 'error',
                    'message_display' => 'flash',
                    'message' => 'Unauthenticated'
                ], 401);
            }

            $employee = $user->employee;
            $attendanceId = (int) $request->input('attendance_id');
            $action = $request->input('action');
            $remarks = $request->input('remarks');

            $attendance = EmpAttendance::with('employee')
                ->where('id', $attendanceId)
                ->where('firm_id', $employee->firm_id)
                ->first();

            if (!$attendance) {
                return Response::json([
                    'message_type' => 'error',
                    'message_display' => 'popup',
                    'message' => 'Attendance not found'
                ], 404);
            }

            if ($attendance->attendance_status_main !== 'POL') {
                return Response::json([
                    'message_type' => 'error',
                    'message_display' => 'popup',
                    'message' => 'Attendance is not in POL state',
                    'code' => 'invalid_state',
                ], 409);
            }

            $coveringLeave = $this->findCoveringLeaveRequestForDate($attendance->employee_id, $attendance->firm_id, $attendance->work_date);
            $perm = $this->canUserApprovePolForAttendanceDetailed($user, $attendance, $coveringLeave);
            if (!$perm['can_approve']) {
                return Response::json([
                    'message_type' => 'error',
                    'message_display' => 'popup',
                    'message' => 'You are not authorized to perform this action',
                    'code' => $perm['reason'],
                ], 403);
            }

            DB::beginTransaction();
            try {
                if ($action === 'accept') {
                    // Flip to Present and append remarks
                    $attendance->attendance_status_main = 'P';
                    $attendance->attend_remarks = trim(($attendance->attend_remarks ? $attendance->attend_remarks . ' | ' : '') . 'POL approved' . ($remarks ? ('. ' . $remarks) : ''));
                    $attendance->save();

                    // Credit back leave balance if applicable
                    $this->creditBackLeaveForDateApi($attendance, $coveringLeave, $user->id);

                    // Informational event on covering leave (status unchanged)
                    if ($coveringLeave) {
                        LeaveRequestEvent::create([
                            'emp_leave_request_id' => $coveringLeave->id,
                            'user_id' => $user->id,
                            'event_type' => 'status_changed',
                            'from_status' => $coveringLeave->status,
                            'to_status' => $coveringLeave->status,
                            'remarks' => 'POL approved for ' . Carbon::parse($attendance->work_date)->format('Y-m-d') . '. ' . ($remarks ?? ''),
                            'firm_id' => $attendance->firm_id,
                            'created_at' => now(),
                        ]);
                    }
                } else {
                    // reject: keep POL, append remark
                    $attendance->attend_remarks = trim(($attendance->attend_remarks ? $attendance->attend_remarks . ' | ' : '') . 'POL rejected' . ($remarks ? ('. ' . $remarks) : ''));
                    $attendance->save();

                    if ($coveringLeave) {
                        LeaveRequestEvent::create([
                            'emp_leave_request_id' => $coveringLeave->id,
                            'user_id' => $user->id,
                            'event_type' => 'status_changed',
                            'from_status' => $coveringLeave->status,
                            'to_status' => $coveringLeave->status,
                            'remarks' => 'POL rejected for ' . Carbon::parse($attendance->work_date)->format('Y-m-d') . '. ' . ($remarks ?? ''),
                            'firm_id' => $attendance->firm_id,
                            'created_at' => now(),
                        ]);
                    }
                }

                DB::commit();

                return Response::json([
                    'message_type' => 'success',
                    'message_display' => 'popup',
                    'message' => $action === 'accept' ? 'Attendance set to Present and leave balance credited.' : 'Attendance remains POL.',
                    'data' => [
                        'attendance_id' => $attendance->id,
                        'action' => $action,
                        'work_date' => Carbon::parse($attendance->work_date)->toDateString(),
                    ],
                ], 200);
            } catch (\Throwable $e) {
                DB::rollBack();
                return Response::json([
                    'message_type' => 'error',
                    'message_display' => 'popup',
                    'message' => 'Server error: ' . $e->getMessage(),
                ], 500);
            }
        } catch (\Throwable $e) {
            return Response::json([
                'message_type' => 'error',
                'message_display' => 'popup',
                'message' => 'Server error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Credit back leave balance for a POL-approved attendance, if a covering leave exists.
     */
    private function creditBackLeaveForDateApi(EmpAttendance $attendance, ?EmpLeaveRequest $coveringLeave, int $actorUserId): void
    {
        if (! $coveringLeave) {
            return;
        }

        $creditAmount = 1.0;
        if (floatval($coveringLeave->apply_days) === 0.5) {
            $creditAmount = 0.5;
        }

        $leaveBalance = EmpLeaveBalance::where('firm_id', $coveringLeave->firm_id)
            ->where('employee_id', $coveringLeave->employee_id)
            ->where('leave_type_id', $coveringLeave->leave_type_id)
            ->where('period_start', '<=', Carbon::parse($attendance->work_date))
            ->where('period_end', '>=', Carbon::parse($attendance->work_date))
            ->first();

        if ($leaveBalance) {
            $leaveBalance->consumed_days = max(0, ($leaveBalance->consumed_days - $creditAmount));
            $leaveBalance->save(); // Model observer will recalculate balance

            EmpLeaveTransaction::create([
                'leave_balance_id' => $leaveBalance->id,
                'emp_leave_request_id' => $coveringLeave->id,
                'transaction_type' => 'credit',
                'transaction_date' => now(),
                'amount' => $creditAmount,
                'reference_id' => $attendance->id,
                'created_by' => $actorUserId,
                'firm_id' => $coveringLeave->firm_id,
                'remarks' => 'POL approved: credited back',
            ]);
        }
    }

    /**
     * GET /api/hrms/employees/{employee}/leave-balances
     */
    public function leavesBalances(Request $request)
    {
        // 1. Get the authenticated employee.
        //    If your User model *is* the Employee, this is fine:
        // Get authenticated user (employee)
        $user = $request->user();
        if (!$user) {
            return Response::json([
                'message_type' => 'error',
                'message_display' => 'flash',
                'message' => 'Unauthenticated'
            ], 401);
        }

        $employee = $user->employee; // Assuming a relationship exists between User and Employee


        //    Otherwise, if you have a relation:
        // $employee = $request->user()->employee;

        // 2. Fetch leave balances with their types
        $balances = EmpLeaveBalance::with('leave_type')
            ->where('employee_id', $employee->id)
            ->get();

        // 3. Shape the output
        $data = $balances->map(function ($b) {
            return [
                'leave_type_id'        => $b->leave_type_id,
                'leave_type_name'      => $b->leave_type->leave_title,
                'period_start'         => $b->period_start->toDateString(),
                'period_end'           => $b->period_end->toDateString(),
                'allocated_days'       => $b->allocated_days,
                'consumed_days'        => $b->consumed_days,
                'carry_forwarded_days' => $b->carry_forwarded_days,
                'lapsed_days'          => $b->lapsed_days,
                'balance'              => $b->balance,
            ];
        });

        // 4. Return as JSON
        return Response::json([
            'message_type' => 'success',
            'message_display' => 'none',
            'message' => 'Leave balances fetched',
            'leavesbalnces' => $data,
            'allow_hourly_leave' => 'no',
            'allow_half_day_leave' => 'yes',
            'leave_age' => [
                [
                    'id' => 1,
                    'title' => 'Single Day',
                    'code' => 'single'
                ],
                [
                    'id' => 2, 
                    'title' => 'Multi Day',
                    'code' => 'multi'
                ],
                [
                    'id' => 3,
                    'title' => 'Half Day',
                    'code' => 'half',
                    'options' => [
                        [
                            'id' => 1,
                            'title' => 'First Half',
                            'code' => 'first_half'
                        ],
                        [
                            'id' => 2,
                            'title' => 'Second Half', 
                            'code' => 'second_half'
                        ]
                    ]
                ],
                [
                    'id' => 4,
                    'title' => 'Hourly',
                    'code' => 'hourly'
                ]
            ]
        ], 200);
    }

    public function submitLeaveRequest_old(Request $request)
    {
        $user     = $request->user();
        $employee = $user->employee;
        $firmId   = $employee->firm_id;

        $validated = $request->validate([
            'leave_type_id' => 'required|integer|exists:leave_types,id',
            'apply_from'    => 'required|date',
            'apply_to'      => 'required|date|after_or_equal:apply_from',
            'reason'        => 'nullable|string|max:1000',
        ]);

        $from = Carbon::parse($validated['apply_from'])->startOfDay();
        $to   = Carbon::parse($validated['apply_to'])->startOfDay();
        $days = $from->diffInDays($to) + 1;

        DB::beginTransaction();
        try {
            // 1) Create the leave request
            $lr = EmpLeaveRequest::create([
                'firm_id'       => $firmId,
                'employee_id'   => $employee->id,
                'leave_type_id' => $validated['leave_type_id'],
                'apply_from'    => $from,
                'apply_to'      => $to,
                'apply_days'    => $days,
                'reason'        => $validated['reason'] ?? null,
                'status'        => 'applied',
            ]);

            // 2) Fetch all relevant, active rules
            $rules = LeaveApprovalRule::with(['employees', 'departments'])
                ->where('firm_id', $firmId)
                ->where('is_inactive', false)
                ->whereDate('period_start', '<=', Carbon::now())
                ->whereDate('period_end',   '>=', Carbon::now())
                ->where(function($q) use ($validated) {
                    $q->whereNull('leave_type_id')
                        ->orWhere('leave_type_id', $validated['leave_type_id']);
                })
                ->orderBy('approval_level')
                ->get();

            // 2.a) If no rule applies, bail out
            if ($rules->isEmpty()) {
                return Response::json([
                ]);
                // /throw new \Exception('No applicable leave approval rule found for this request');
            }

            // 3) For each rule, create an approval record
            foreach ($rules as $rule) {
                EmpLeaveRequestApproval::create([
                    'emp_leave_request_id' => $lr->id,
                    'approval_level'       => $rule->approval_level,
                    'approver_id'          => $rule->approver_id ?? 0,
                    'status'               => 'applied',
                    'remarks'              => null,
                    'acted_at'             => null,
                    'firm_id'              => $firmId,
                ]);
            }

            // 4) Log the "created" event
            LeaveRequestEvent::create([
                'emp_leave_request_id' => $lr->id,
                'user_id'              => $employee->id,
                'event_type'           => 'status_changed', // status_changed, clarification_requested, clarification_provided
                'from_status'          => null,
                'to_status'            => 'applied',
                'remarks'              => $validated['reason'] ?? null,
                'firm_id'              => $firmId,
                'created_at'           => Carbon::now(),
            ]);

            DB::commit();

        } catch (\Throwable $e) {
            DB::rollBack();
            return Response::json([
                'message_type'    => 'error',
                'message_display' => 'popup',
                'message'         => $e->getMessage(),
            ], 422);
        }

        return Response::json([
            'message_type'    => 'success',
            'message_display' => 'popup',
            'message'         => 'Leave request submitted',
            'data'            => $lr->load('leave_type'),
        ], 201);
    }
    public function submitLeaveRequest(Request $request)
    {
        $user     = $request->user();
        $employee = $user->employee;
        $firmId   = $employee->firm_id;

        $validated = $request->validate([
            'leave_type_id' => 'required|integer|exists:leave_types,id',
            'apply_from'    => 'required|date',
            'apply_to'      => 'required|date|after_or_equal:apply_from',
            'reason'        => 'nullable|string|max:1000',
        ]);

        $from = Carbon::parse($validated['apply_from'])->startOfDay();
        $to   = Carbon::parse($validated['apply_to'])->startOfDay();

        DB::beginTransaction();
        try {
            // 1) Create the leave request
            $lr = EmpLeaveRequest::create([
                'firm_id'       => $firmId,
                'employee_id'   => $employee->id,
                'leave_type_id' => $validated['leave_type_id'],
                'apply_from'    => $from,
                'apply_to'      => $to,
                'apply_days'    => $from->diffInDays($to) + 1,
                'reason'        => $validated['reason'] ?? null,
                'status'        => 'applied',
            ]);

            // 2) Fetch the *latest* active rule for THIS employee
            $rules = LeaveApprovalRule::with('employees')
                ->where('firm_id', $firmId)
                ->where('approval_mode','!=','view_only')
                ->where('is_inactive', false)
                ->whereDate('period_start', '<=', now())
                ->whereDate('period_end',   '>=', now())
                ->where(function($q) use ($validated) {
                    $q->whereNull('leave_type_id')
                        ->orWhere('leave_type_id', $validated['leave_type_id']);
                })
                ->whereHas('employees', function($q) use ($employee) {
                    $q->where('employee_id', $employee->id);
                })
                ->get();

            if (! $rules) {
                throw new \Exception('No applicable leave approval rule found for this employee');
            }

            // 3) Create a single approval record from that rule
//            EmpLeaveRequestApproval::create([
//                'emp_leave_request_id' => $lr->id,
//                'approval_level'       => $rule->approval_level,
//                'approver_id'          => $rule->approver_id ?? 0,
//                'status'               => 'applied',
//                'remarks'              => null,
//                'acted_at'             => null,
//                'firm_id'              => $firmId,
//            ]);

            // 4) Log the "created" event
            LeaveRequestEvent::create([
                'emp_leave_request_id' => $lr->id,
                'user_id'              => $user->id,
                'event_type'           => 'status_changed',
                'from_status'          => null,
                'to_status'            => 'applied',
                'remarks'              => $validated['reason'] ?? null,
                'firm_id'              => $firmId,
                'created_at'           => now(),
            ]);

            DB::commit();

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message_type'    => 'error',
                'message_display' => 'popup',
                'message'         => $e->getMessage(),
            ], 422);
        }

        //
        // ──────────────── STAGE NOTIFICATIONS ─────────────────
        //

        $now = now();

        $fromFormatted = $from->format('j-M-Y');
        $toFormatted   = $to->format('j-M-Y');

        // Payload for applicant
        $applicantPayload = [
            'firm_id' => $firmId,
            'subject' => 'Your leave request is submitted',
            'message' => "You have applied for leave from {$fromFormatted} to {$toFormatted}.",
            'company_name' => "{$employee->firm->name}",
        ];

        NotificationQueue::create([
            'firm_id'         => $firmId,
            'notifiable_type'=> User::class,
            'notifiable_id'  => $user->id,
            'channel'        => 'mail',
            'data'           => json_encode($applicantPayload),
            'status'         => 'pending',
            'created_at'     => $now,
            'updated_at'     => $now,
        ]);

        // Payload for approver



        $approverPayload = [
            'firm_id'      => $firmId,
            'subject'      => 'New leave request pending your approval',
            'message'      => "{$employee->fname} {$employee->lname} has requested leave ({$fromFormatted} → {$toFormatted}).",
            'company_name' => "{$employee->firm->name}",
        ];

        // 1) Pull out all non‐empty approver IDs and make them unique
        $approverIds = $rules
            ->pluck('approver_id')   // get a Collection of [ approver_id, … ]
            ->filter()               // remove any null/empty values
            ->unique()               // in case multiple rules share the same approver
            ->values()               // re‐index numerically (optional)
            ->all();                 // toArray()

// 2) Fetch all User models with those IDs in one shot
        $approverUsers = User::whereIn('id', $approverIds)->get();

// 3) Loop over the resulting Collection of Users to queue notifications
        foreach ($approverUsers as $approver) {

            NotificationQueue::create([
                'firm_id'         => $firmId,
                'notifiable_type' => User::class,
                'notifiable_id'   => $approver->id,
                'channel'         => 'mail',
                'data'            => json_encode($approverPayload),
                'status'          => 'pending',
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }

        return response()->json([
            'message_type'    => 'success',
            'message_display' => 'popup',
            'message'         => 'Leave request submitted',
            'data'            => $lr->load('leave_type'),
        ], 201);
    }

    public function submitLeaveRequestv2(Request $request)
    {
        try {
            $user     = $request->user();
            $employee = $user->employee;
            $firmId   = $employee->firm_id;

            try {
                $validated = $request->validate([
                    'leave_type_id' => 'required|integer|exists:leave_types,id',
                    'apply_from'    => 'required|date',
                    'apply_to'      => 'required|date|after_or_equal:apply_from',
                    'reason'        => 'nullable|string|max:1000',
                    'leave_age'     => 'required|string|in:single,multi,half,hourly',
                    'time_from'     => 'nullable|date_format:H:i',
                    'time_to'       => 'nullable|date_format:H:i|after:time_from',
                    'half_day_type' => 'nullable|string|in:first_half,second_half',
                ]);
            } catch (\Illuminate\Validation\ValidationException $e) {
                return Response::json([
                    'message_type'    => 'error',
                    'message_display' => 'popup',
                    'message'         => 'Validation Error: ' . $e->getMessage(),
                    'errors'          => $e->errors(),
                ], 422);
            }
            
            $from = Carbon::parse($validated['apply_from'])->startOfDay();
            $to   = Carbon::parse($validated['apply_to'])->startOfDay();
            
            // Validate no overlapping leave requests (only block if status is applied/approved, allow if rejected)
            $this->validateNoOverlappingLeaves($employee->id, $firmId, $from, $to, $validated['leave_age']);
            
            // Calculate days based on leave type
            if ($validated['leave_age'] === 'half') {
                $days = 0.5; // Half day is always 0.5 days
                
                // Set default time values for half day if not provided
                if (!$validated['time_from'] || !$validated['time_to']) {
                    if ($validated['half_day_type'] === 'first_half') {
                        $validated['time_from'] = '09:00';
                        $validated['time_to'] = '13:00';
                    } else {
                        $validated['time_from'] = '13:00';
                        $validated['time_to'] = '17:00';
                    }
                }
            } else {
                $days = $from->diffInDays($to) + 1;
            }

            DB::beginTransaction();
            try {
                // 1) Create the leave request
                $lr = EmpLeaveRequest::create([
                    'firm_id'       => $firmId,
                    'employee_id'   => $employee->id,
                    'leave_type_id' => $validated['leave_type_id'],
                    'apply_from'    => $from,
                    'apply_to'      => $to,
                    'apply_days'    => $days,
                    'time_from'     => $validated['time_from'] ? Carbon::parse($validated['time_from']) : null,
                    'time_to'       => $validated['time_to'] ? Carbon::parse($validated['time_to']) : null,
                    'reason'        => $validated['reason'] ?? null,
                    'status'        => 'applied',
                ]);

                // 2) Fetch the *latest* active rule for THIS employee
                $rules = LeaveApprovalRule::with('employees')
                    ->where('firm_id', $firmId)
                    ->where('approval_mode','!=','view_only')
                    ->where('is_inactive', false)
                    ->whereDate('period_start', '<=', Carbon::now())
                    ->whereDate('period_end',   '>=', Carbon::now())
                    ->where(function($q) use ($validated) {
                        $q->whereNull('leave_type_id')
                            ->orWhere('leave_type_id', $validated['leave_type_id']);
                    })
                    ->whereHas('employees', function($q) use ($employee) {
                        $q->where('employee_id', $employee->id);
                    })
                    ->get();

                if ($rules->isEmpty()) {
                    $debugInfo = [
                        'firm_id' => $firmId,
                        'leave_type_id' => $validated['leave_type_id'],
                        'employee_id' => $employee->id,
                        'period_now' => Carbon::now()->toDateString(),
                        'rule_count' => $rules->count(),
                        'rule_query' => [
                            'firm_id' => $firmId,
                            'approval_mode !=' => 'view_only',
                            'is_inactive' => false,
                            'period_start <=' => Carbon::now()->toDateString(),
                            'period_end >=' => Carbon::now()->toDateString(),
                            'leave_type_id' => $validated['leave_type_id'],
                            'employee_id' => $employee->id,
                        ],
                    ];
                    \Log::error('No applicable leave approval rule found for this request', $debugInfo);
                    return Response::json([
                        'message_type' => 'error',
                        'message_display' => 'popup',
                        'message' => 'No applicable leave approval rule found for this employee',
                        'debug' => $debugInfo,
                    ], 500);
                }

                // 4) Log the "created" event
                LeaveRequestEvent::create([
                    'emp_leave_request_id' => $lr->id,
                    'user_id'              => $user->id,
                    'event_type'           => 'status_changed',
                    'from_status'          => null,
                    'to_status'            => 'applied',
                    'remarks'              => $validated['reason'] ?? null,
                    'firm_id'              => $firmId,
                    'created_at'           => Carbon::now(),
                ]);

                DB::commit();

            } catch (\Throwable $e) {
                DB::rollBack();
                Log::error('Leave request DB error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
                return Response::json([
                    'message_type'    => 'error',
                    'message_display' => 'popup',
                    'message'         => 'Server Error: ' . $e->getMessage(),
                    'trace'           => app()->environment('production') ? null : $e->getTraceAsString(),
                ], 500);
            }

            // ──────────────── STAGE NOTIFICATIONS ─────────────────

            $now = Carbon::now();

            $fromFormatted = $from->format('j-M-Y');
            $toFormatted   = $to->format('j-M-Y');

            // Add time information for half day leaves
            $timeInfo = '';
            if ($validated['leave_age'] === 'half' && $validated['time_from'] && $validated['time_to']) {
                $timeInfo = " ({$validated['time_from']} - {$validated['time_to']})";
            }

            // Payload for applicant
            $applicantPayload = [
                'firm_id' => $firmId,
                'subject' => 'Your leave request is submitted',
                'message' => "You have applied for leave from {$fromFormatted} to {$toFormatted}{$timeInfo}.",
                'company_name' => "{$employee->firm->name}",
            ];

            NotificationQueue::create([
                'firm_id'         => $firmId,
                'notifiable_type'=> User::class,
                'notifiable_id'  => $user->id,
                'channel'        => 'mail',
                'data'           => json_encode($applicantPayload),
                'status'         => 'pending',
                'created_at'     => $now,
                'updated_at'     => $now,
            ]);

            // Payload for approver
            $approverPayload = [
                'firm_id'      => $firmId,
                'subject'      => 'New leave request pending your approval',
                'message'      => "{$employee->fname} {$employee->lname} has requested leave ({$fromFormatted} → {$toFormatted}){$timeInfo}.",
                'company_name' => "{$employee->firm->name}",
            ];

            // 1) Pull out all non‐empty approver IDs and make them unique
            $approverIds = $rules
                ->pluck('approver_id')   // get a Collection of [ approver_id, … ]
                ->filter()               // remove any null/empty values
                ->unique()               // in case multiple rules share the same approver
                ->values()               // re‐index numerically (optional)
                ->all();                 // toArray()

            // 2) Fetch all User models with those IDs in one shot
            $approverUsers = User::whereIn('id', $approverIds)->get();

            // 3) Loop over the resulting Collection of Users to queue notifications
            foreach ($approverUsers as $approver) {
                NotificationQueue::create([
                    'firm_id'         => $firmId,
                    'notifiable_type' => User::class,
                    'notifiable_id'   => $approver->id,
                    'channel'         => 'mail',
                    'data'            => json_encode($approverPayload),
                    'status'          => 'pending',
                    'created_at'      => Carbon::now(),
                    'updated_at'      => Carbon::now(),
                ]);
            }

            return Response::json([
                'message_type'    => 'success',
                'message_display' => 'popup',
                'message'         => 'Leave request submitted',
                'data'            => $lr->load('leave_type'),

            ], 201);
        } catch (\Throwable $e) {
            Log::error('Leave request fatal error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return Response::json([
                'message_type'    => 'error',
                'message_display' => 'popup',
                'message'         => 'Fatal Server Error: ' . $e->getMessage(),
                'trace'           => app()->environment('production') ? null : $e->getTraceAsString(),
            ], 500);
        }
    }

    public function leaveRequests(Request $request)
    {
        $employee = $request->user()->employee;

        $leaves = EmpLeaveRequest::with([
            'leave_type',
            'leave_request_events' => function($q) {
                $q->orderBy('created_at', 'desc')
                    ->with('user:id,name');
            },
        ])
            ->where('employee_id', $employee->id)
            ->get()
            ->map(function($leave) {
                // Transform events
                $leave->leave_request_events->transform(function($evt) {
                    $evt->user_name = $evt->user->name ?? null;
                    $evt->created_at = Carbon::parse($evt->created_at)->format('Y-m-d H:i:s');
                    unset($evt->user);
                    return $evt;
                });
                
                return $leave;
            });

        return Response::json([
            'message_type'    => 'success',
            'message_display' => 'none',
            'message'         => 'Leave requests list',
            'data'            => $leaves,
        ], 200);
    }


    public function leaveRequests_old(Request $request)
    {
        $employee = $request->user()->employee;

        // 1) Load all leave‐requests for this employee, most recent first
        // 2) Eager load leave_type and events (events also in DESC order)
        $leaves = EmpLeaveRequest::with([
            'leave_type',
            'leave_request_events' => function($q) {
                $q->orderBy('created_at', 'desc');
            }
        ])
            ->where('employee_id', $employee->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return Response::json([
            'message_type'    => 'success',
            'message_display' => 'none',
            'message'         => 'Leave requests list',
            'data'            => $leaves
        ], 200);

    }

    /**
     * Get team leave requests for the authenticated manager/approver
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTeamLeaves(Request $request)
    {
        try {
            // Get authenticated user
            $user = $request->user();
            if (!$user) {
                return Response::json([
                    'message_type' => 'error',
                    'message_display' => 'flash',
                    'message' => 'Unauthenticated'
                ], 401);
            }

            // Get employee from authenticated user
            $employee = $user->employee;
            if (!$employee) {
                return Response::json([
                    'message_type' => 'error',
                    'message_display' => 'flash',
                    'message' => 'Employee profile not found'
                ], 404);
            }

            // Get the rules where the current user is an approver
            $userRules = LeaveApprovalRule::where('firm_id', $employee->firm_id)
                ->where('approver_id', $user->id)
                ->where('is_inactive', false)
                ->with(['employees', 'departments.employees'])
                ->get();

            // If user has no approval rules, return empty result
            if ($userRules->isEmpty()) {
                return Response::json([
                    'message_type' => 'info',
                    'message_display' => 'popup',
                    'message' => 'No approval rules found for this user',
                    'data' => [
                        'leave_requests' => [],
                        'filters' => []
                    ]
                ], 200);
            }

            // Build query for leave requests
            $query = EmpLeaveRequest::query()
                ->with([
                    'employee',
                    'leave_type' => function ($query) {
                        $query->select('id', 'leave_title');
                    },
                    'leave_request_events' => function ($query) {
                        $query->select('id', 'emp_leave_request_id', 'user_id', 'event_type', 'from_status', 'to_status', 'remarks', 'created_at')
                            ->with(['user:id,name'])
                            ->orderBy('created_at', 'desc');
                    },
                    'emp_leave_request_approvals' => function ($query) {
                        $query->select('id', 'emp_leave_request_id', 'approval_level', 'approver_id', 'status', 'remarks', 'acted_at')
                            ->with(['approver:id,name'])
                            ->orderBy('approval_level', 'asc');
                    }
                ])
                ->where('firm_id', $employee->firm_id)
                ->where('status', '!=', 'cancelled_employee')
                ->where('status', '!=', 'cancelled_hr');

            // Build query based on user's rules
            $query->where(function ($q) use ($userRules) {
                foreach ($userRules as $rule) {
                    $q->orWhere(function ($subQ) use ($rule) {
                        // Match leave type if specified
                        if ($rule->leave_type_id) {
                            $subQ->where('leave_type_id', $rule->leave_type_id);
                        }

                        // Match employees based on rule scope
                        if ($rule->employees->isNotEmpty()) {
                            $subQ->whereIn('employee_id', $rule->employees->pluck('id'));
                        }

                        // If rule has departments, include their employees
                        if ($rule->departments->isNotEmpty()) {
                            $employeeIds = $rule->departments->flatMap(function ($dept) {
                                return $dept->employees->pluck('id');
                            })->unique();
                            $subQ->orWhereIn('employee_id', $employeeIds);
                        }

                        // If rule is for all employees
                        if ($rule->employee_scope === 'all' || $rule->department_scope === 'all') {
                            $subQ->orWhereNotNull('id');
                        }
                    });
                }
            });

            // Apply filters from request
            if ($request->filled('employee_id')) {
                $query->where('employee_id', $request->input('employee_id'));
            }
            
            if ($request->filled('leave_type_id')) {
                $query->where('leave_type_id', $request->input('leave_type_id'));
            }
            
            if ($request->filled('status')) {
                $query->where('status', $request->input('status'));
            }
            
            if ($request->filled('apply_from')) {
                $query->whereDate('apply_from', '>=', $request->input('apply_from'));
            }
            
            if ($request->filled('apply_to')) {
                $query->whereDate('apply_to', '<=', $request->input('apply_to'));
            }

            // Apply sorting
            $sortBy = $request->input('sort_by', 'created_at');
            $sortDirection = $request->input('sort_direction', 'desc');
            $query->orderBy($sortBy, $sortDirection);

            // Get all results without pagination for mobile API
            $leaveRequests = $query->get();

            // Filter: Only show leaves where user can approve or has approved
            $filteredLeaveRequests = $leaveRequests->filter(function($leaveRequest) use ($user) {
                $canApprove = $this->canUserApproveLeave($user, $leaveRequest);
                $hasApproved = $leaveRequest->emp_leave_request_approvals
                    ->where('approver_id', $user->id)
                    ->where('status', 'approved')
                    ->isNotEmpty();
                return $canApprove || $hasApproved;
            })->values();

            // Format the response data - only what's shown in the table
            $formattedRequests = $filteredLeaveRequests->map(function($leaveRequest) use ($user) {
                // Check if current user can approve this request
                $canApprove = $this->canUserApproveLeave($user, $leaveRequest);
                
                // Determine if action button should be shown
                $showActionButton = $canApprove && !in_array($leaveRequest->status, ['approved', 'rejected', 'cancelled_employee', 'cancelled_hr']);

                // Add half-day information
                $isHalfDay = floatval($leaveRequest->apply_days) === 0.5;
                $timeInfo = '';
                $halfDayType = '';
                $leaveAge = '';

                if ($isHalfDay) {
                    $halfDayType = $leaveRequest->time_from && $leaveRequest->time_to ? 
                        (Carbon::parse($leaveRequest->time_from)->format('H:i') <= '12:00' ? 'First Half' : 'Second Half') : '';
                    $timeInfo = $leaveRequest->time_from && $leaveRequest->time_to ? 
                        " (" . Carbon::parse($leaveRequest->time_from)->format('H:i') . " - " . Carbon::parse($leaveRequest->time_to)->format('H:i') . ")" : '';
                    $leaveAge = 'half';
                } elseif ($leaveRequest->time_from && $leaveRequest->time_to) {
                    // This implies hourly if apply_days is a full day but times are specified
                    $leaveAge = 'hourly';
                    $timeInfo = " (" . Carbon::parse($leaveRequest->time_from)->format('H:i') . " - " . Carbon::parse($leaveRequest->time_to)->format('H:i') . ")";
                } elseif (floatval($leaveRequest->apply_days) == 1.0) {
                    $leaveAge = 'single';
                } elseif (floatval($leaveRequest->apply_days) > 1.0) {
                    $leaveAge = 'multi';
                }

                return [
                    'id' => $leaveRequest->id,
                    'employee_name' => trim($leaveRequest->employee->fname . ' ' . 
                                         ($leaveRequest->employee->mname ? $leaveRequest->employee->mname . ' ' : '') . 
                                         $leaveRequest->employee->lname),
                    'leave_type_title' => $leaveRequest->leave_type->leave_title ?? 'N/A',
                    'apply_from' => Carbon::parse($leaveRequest->apply_from)->format('jS F Y'),
                    'apply_to' => Carbon::parse($leaveRequest->apply_to)->format('jS F Y'),
                    'apply_days' => $leaveRequest->apply_days,
                    'is_half_day' => $isHalfDay,
                    'half_day_type' => $halfDayType,
                    'time_info' => $timeInfo,
                    'leave_age' => $leaveAge,
                    'reason' => $leaveRequest->reason,
                    'status' => $leaveRequest->status,
                    'status_label' => EmpLeaveRequest::STATUS_SELECT[$leaveRequest->status] ?? 'Unknown',
                    'can_approve' => $canApprove,
                    'show_action_button' => $showActionButton,
                    'created_at' => Carbon::parse($leaveRequest->created_at)->format('Y-m-d H:i:s'),
                    'approvals' => $leaveRequest->emp_leave_request_approvals->map(function($approval) {
                        return [
                            'id' => $approval->id,
                            'approval_level' => $approval->approval_level,
                            'status' => $approval->status,
                            'remarks' => $approval->remarks,
                            'acted_at' => $approval->acted_at ? Carbon::parse($approval->acted_at)->format('Y-m-d H:i:s') : null,
                            'approver_name' => $approval->approver ? $approval->approver->name : 'N/A'
                        ];
                    }),
                    'events' => $leaveRequest->leave_request_events->map(function($event) {
                        return [
                            'id' => $event->id,
                            'event_type' => $event->event_type,
                            'from_status' => $event->from_status,
                            'to_status' => $event->to_status,
                            'remarks' => $event->remarks,
                            'created_at' => Carbon::parse($event->created_at)->format('Y-m-d H:i:s'),
                            'user_name' => $event->user ? $event->user->name : 'N/A',
                        ];
                    })
                ];
            });

            // Get available filters for frontend
            $availableFilters = [
                'employees' => Employee::where('firm_id', $employee->firm_id)
                    ->pluck('fname', 'id')
                    ->toArray(),
                'leave_types' => LeaveType::where('firm_id', $employee->firm_id)
                    ->pluck('leave_title', 'id')
                    ->toArray(),
                'statuses' => EmpLeaveRequest::STATUS_SELECT,
            ];

            return Response::json([
                'message_type' => 'success',
                'message_display' => 'none',
                'message' => count($formattedRequests) . ' team leave requests found',
                'data' => [
                    'leave_requests' => $formattedRequests->toArray(),
                    'filters' => $availableFilters
                ]
            ], 200);

        } catch (\Throwable $e) {
            return Response::json([
                'message_type' => 'error',
                'message_display' => 'popup',
                'message' => 'Server error: ' . $e->getMessage(),
                'data' => [
                    'leave_requests' => [],
                    'filters' => []
                ]
            ], 500);
        }
    }

    /**
     * Handle leave request approval/rejection
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handleLeaveAction(Request $request)
    {
        try {
            // Validate request parameters
            $validator = Validator::make($request->all(), [
                'leave_request_id' => 'required|integer|exists:emp_leave_requests,id',
                'action' => 'required|in:approve,reject',
                'remarks' => 'nullable|string|min:3',
            ]);

            if ($validator->fails()) {
                return Response::json([
                    'message_type' => 'error',
                    'message_display' => 'popup',
                    'message' => 'Invalid parameters',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Get authenticated user
            $user = $request->user();
            if (!$user) {
                return Response::json([
                    'message_type' => 'error',
                    'message_display' => 'flash',
                    'message' => 'Unauthenticated'
                ], 401);
            }

            // Get employee from authenticated user
            $employee = $user->employee;
            if (!$employee) {
                return Response::json([
                    'message_type' => 'error',
                    'message_display' => 'flash',
                    'message' => 'Employee profile not found'
                ], 404);
            }

            $leaveRequestId = $request->input('leave_request_id');
            $action = $request->input('action');
            $remarks = $request->input('remarks');

            // Get the leave request
            $leaveRequest = EmpLeaveRequest::where('id', $leaveRequestId)
                ->where('firm_id', $employee->firm_id)
                ->with(['employee', 'leave_type'])
                ->first();

            if (!$leaveRequest) {
                return Response::json([
                    'message_type' => 'error',
                    'message_display' => 'popup',
                    'message' => 'Leave request not found'
                ], 404);
            }

            // Check if user can approve this leave request
            if (!$this->canUserApproveLeave($user, $leaveRequest)) {
                return Response::json([
                    'message_type' => 'error',
                    'message_display' => 'popup',
                    'message' => 'You are not authorized to perform this action'
                ], 403);
            }

            // Get approval level for current user
            $approvalLevel = $this->getApprovalLevelForUser($user, $leaveRequest);
            $oldStatus = $leaveRequest->status;
            $maxApprovalLevel = $this->getMaxApprovalLevel($leaveRequest);

            // Count current approvals
            $approvalCount = EmpLeaveRequestApproval::where('emp_leave_request_id', $leaveRequestId)
                ->where('status', 'approved')
                ->count();

            // Determine new status
            if ($action === 'approve') {
                $approvalCount++;
                $newStatus = ($approvalCount >= $maxApprovalLevel) ? 'approved' : 'approved_further';
            } else {
                $newStatus = 'rejected';
            }

            // Update leave request status
            $leaveRequest->update(['status' => $newStatus]);

            // Create approval record
            EmpLeaveRequestApproval::create([
                'emp_leave_request_id' => $leaveRequestId,
                'approval_level' => $approvalLevel,
                'approver_id' => $user->id,
                'status' => ($action === 'approve') ? 'approved' : 'rejected',
                'remarks' => $remarks,
                'acted_at' => Carbon::now(),
                'firm_id' => $employee->firm_id
            ]);

            // Update leave balance if approved
            if ($action === 'approve') {
                $this->updateLeaveBalance($leaveRequest);
            }

            // Log the event
            LeaveRequestEvent::create([
                'emp_leave_request_id' => $leaveRequestId,
                'user_id' => $user->id,
                'event_type' => 'status_change',
                'from_status' => $oldStatus,
                'to_status' => $newStatus,
                'remarks' => $remarks,
                'firm_id' => $employee->firm_id
            ]);

            // Send notifications (similar to TeamLeaves component)
            $this->sendLeaveActionNotifications($leaveRequest, $newStatus, $user);

            $successMessage = ($action === 'approve')
                ? ($newStatus === 'approved'
                    ? 'Leave request has been fully approved'
                    : "Leave request approved ({$approvalCount}/{$maxApprovalLevel} approvals)")
                : 'Leave request has been rejected';

            return Response::json([
                'message_type' => 'success',
                'message_display' => 'popup',
                'message' => $successMessage,
                'data' => [
                    'leave_request' => [
                        'id' => $leaveRequest->id,
                        'employee_name' => trim($leaveRequest->employee->fname . ' ' . 
                                             ($leaveRequest->employee->mname ? $leaveRequest->employee->mname . ' ' : '') . 
                                             $leaveRequest->employee->lname),
                        'leave_type' => $leaveRequest->leave_type->leave_title ?? 'N/A',
                        'apply_from' => Carbon::parse($leaveRequest->apply_from)->format('jS F Y'),
                        'apply_to' => Carbon::parse($leaveRequest->apply_to)->format('jS F Y'),
                        'apply_days' => $leaveRequest->apply_days,
                        'reason' => $leaveRequest->reason,
                        'old_status' => $oldStatus,
                        'new_status' => $newStatus,
                        'status_label' => EmpLeaveRequest::STATUS_SELECT[$newStatus] ?? 'Unknown',
                        'action_taken' => $action,
                        'remarks' => $remarks,
                        'approval_level' => $approvalLevel,
                        'max_approval_level' => $maxApprovalLevel,
                        'current_approval_count' => $approvalCount
                    ]
                ]
            ], 200);

        } catch (\Throwable $e) {
            return Response::json([
                'message_type' => 'error',
                'message_display' => 'popup',
                'message' => 'Server error: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    /**
     * Check if the current user can approve a specific leave request
     * 
     * @param User $user
     * @param EmpLeaveRequest $leaveRequest
     * @return bool
     */
    private function canUserApproveLeave($user, $leaveRequest)
    {
        // Get approval rules for the current user
        $approvalRules = LeaveApprovalRule::where('firm_id', $leaveRequest->firm_id)
            ->where('approver_id', $user->id)
            ->where(function ($query) use ($leaveRequest) {
                $query->where('leave_type_id', $leaveRequest->leave_type_id)
                    ->orWhereNull('leave_type_id');
            })
            ->where('is_inactive', false)
            ->get();

        // If no rules found, user cannot approve
        if ($approvalRules->isEmpty()) {
            return false;
        }

        foreach ($approvalRules as $rule) {
            // Skip if view-only mode
            if ($rule->approval_mode === 'view_only') {
                continue;
            }

            // Skip if auto_approve is true
            if ($rule->auto_approve) {
                continue;
            }

            // Check leave duration constraints
            $leaveDays = $leaveRequest->apply_days;
            if ($rule->min_days && $leaveDays < $rule->min_days) {
                continue;
            }
            if ($rule->max_days && $leaveDays > $rule->max_days) {
                continue;
            }

            // Check if this approval level is currently needed
            $currentApprovals = EmpLeaveRequestApproval::where('emp_leave_request_id', $leaveRequest->id)
                ->where('status', 'approved')
                ->count();

            // For sequential approval, check if it's this approver's turn
            if ($rule->approval_mode === 'sequential' && $currentApprovals + 1 !== $rule->approval_level) {
                continue;
            }

            // For parallel approval, check if this approver hasn't already approved
            if ($rule->approval_mode === 'parallel') {
                $hasApproved = EmpLeaveRequestApproval::where('emp_leave_request_id', $leaveRequest->id)
                    ->where('approver_id', $user->id)
                    ->where('status', 'approved')
                    ->exists();
                if ($hasApproved) {
                    continue;
                }
            }

            // Check if employee is in rule's scope
            if ($this->isEmployeeInRuleScope($leaveRequest->employee_id, $rule)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if an employee falls under a rule's scope
     * 
     * @param int $employeeId
     * @param LeaveApprovalRule $rule
     * @return bool
     */
    private function isEmployeeInRuleScope($employeeId, $rule)
    {
        // If scope is all, employee is in scope
        if ($rule->department_scope === 'all' || $rule->employee_scope === 'all') {
            return true;
        }

        // Check department-based rules
        if ($rule->departments->contains(function ($department) use ($employeeId) {
            return $department->employees->contains('id', $employeeId);
        })) {
            return true;
        }

        // Check direct employee assignments
        return $rule->employees->contains('id', $employeeId);
    }

    /**
     * Get the approval level for the current user from the matching rule
     *
     * @param User $user
     * @param EmpLeaveRequest $leaveRequest
     * @return int
     */
    private function getApprovalLevelForUser($user, $leaveRequest): int
    {
        $approvalRule = LeaveApprovalRule::where('firm_id', $leaveRequest->firm_id)
            ->where('approver_id', $user->id)
            ->where(function ($query) use ($leaveRequest) {
                $query->where('leave_type_id', $leaveRequest->leave_type_id)
                    ->orWhereNull('leave_type_id');
            })
            ->where('is_inactive', false)
            ->first();

        return $approvalRule ? $approvalRule->approval_level : 1; // Default to 1 if no rule found
    }

    /**
     * Get the highest approval level needed for this leave request
     *
     * @param EmpLeaveRequest $leaveRequest
     * @return int
     */
    private function getMaxApprovalLevel($leaveRequest): int
    {
        return LeaveApprovalRule::where('firm_id', $leaveRequest->firm_id)
            ->where(function ($query) use ($leaveRequest) {
                $query->where('leave_type_id', $leaveRequest->leave_type_id)
                    ->orWhereNull('leave_type_id');
            })
            ->where('is_inactive', false)
            ->max('approval_level') ?? 1;
    }

    /**
     * Update employee leave balance when leave is approved
     *
     * @param EmpLeaveRequest $leaveRequest
     * @return void
     */
    private function updateLeaveBalance($leaveRequest): void
    {
        // Only update balance if this is the final approval
        $maxApprovalLevel = $this->getMaxApprovalLevel($leaveRequest);
        $approvalCount = EmpLeaveRequestApproval::where('emp_leave_request_id', $leaveRequest->id)
            ->where('status', 'approved')
            ->count();

        if ($approvalCount >= $maxApprovalLevel) {
            // Find current leave balance
            $leaveBalance = EmpLeaveBalance::where('firm_id', $leaveRequest->firm_id)
                ->where('employee_id', $leaveRequest->employee_id)
                ->where('leave_type_id', $leaveRequest->leave_type_id)
                ->where('period_start', '<=', $leaveRequest->apply_from)
                ->where('period_end', '>=', $leaveRequest->apply_to)
                ->first();

            if ($leaveBalance) {
                // Update consumed_days - the balance will be automatically recalculated
                $leaveBalance->consumed_days += $leaveRequest->apply_days;
                $leaveBalance->save(); // Model observer will recalculate balance

                // Create a leave transaction record
                EmpLeaveTransaction::create([
                    'leave_balance_id' => $leaveBalance->id,
                    'emp_leave_request_id' => $leaveRequest->id,
                    'transaction_type' => 'debit',
                    'transaction_date' => Carbon::now(),
                    'amount' => $leaveRequest->apply_days,
                    'reference_id' => $leaveRequest->id, // Using leave request id as reference
                    'created_by' => Auth::id(), // Current authenticated user
                    'firm_id' => $leaveRequest->firm_id,
                    'remarks' => 'Leave approved'
                ]);
            }
            $this->updateAttendanceForLeave($leaveRequest);
        }
    }

    /**
     * Update attendance records for an approved leave request.
     *
     * @param EmpLeaveRequest $leaveRequest
     * @return void
     */
    private function updateAttendanceForLeave(EmpLeaveRequest $leaveRequest): void
    {
        $startDate = Carbon::parse($leaveRequest->apply_from);
        $endDate = Carbon::parse($leaveRequest->apply_to);

        $isHalfDay = floatval($leaveRequest->apply_days) === 0.5;
        $attendanceStatus = $isHalfDay ? 'HD' : 'L';
        $finalDayWeightage = $isHalfDay ? 0.5 : 0; // 0 for full day leave, 0.5 for half day

        for ($date = $startDate; $date->lte($endDate); $date->addDay()) {
            EmpAttendance::updateOrCreate(
                [
                    'firm_id' => $leaveRequest->firm_id,
                    'employee_id' => $leaveRequest->employee_id,
                    'work_date' => $date->toDateString(),
                ],
                [
                    'attendance_status_main' => $attendanceStatus,
                    'final_day_weightage' => $finalDayWeightage,
                    'attend_remarks' => 'Approved Leave: ' . ($leaveRequest->leave_type->leave_title ?? 'N/A'),
                ]
            );
        }
    }

    /**
     * Send notifications for leave action
     *
     * @param EmpLeaveRequest $leaveRequest
     * @param string $newStatus
     * @param User $user
     * @return void
     */
    private function sendLeaveActionNotifications($leaveRequest, $newStatus, $user): void
    {
        try {
            // Format the dates
            $fromDT = Carbon::parse($leaveRequest->apply_from)->format('j-M-Y');
            $toDT = Carbon::parse($leaveRequest->apply_to)->format('j-M-Y');

            // Send notification to employee
            $employeeUser = $leaveRequest->employee->user;
            if ($employeeUser) {
                $employeePayload = [
                    'firm_id' => $leaveRequest->firm_id,
                    'subject' => "Your leave request has been {$newStatus}",
                    'message' => "Your leave request from {$fromDT} to {$toDT} has been <strong>{$newStatus}</strong>.",
                    'company_name' => $leaveRequest->employee->firm->name ?? 'Company',
                ];

                NotificationQueue::create([
                    'firm_id' => $leaveRequest->firm_id,
                    'notifiable_type' => User::class,
                    'notifiable_id' => $employeeUser->id,
                    'channel' => 'mail',
                    'data' => json_encode($employeePayload),
                    'status' => 'pending',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
            }

            // Send notification to all approvers
            $rules = LeaveApprovalRule::with('employees')
                ->where('firm_id', $leaveRequest->firm_id)
//                ->where('approval_mode', '!=', 'view_only')
                ->where('is_inactive', false)
                ->whereDate('period_start', '<=', Carbon::now())
                ->whereDate('period_end', '>=', Carbon::now())
                ->where(function($q) use ($leaveRequest) {
                    $q->whereNull('leave_type_id')
                        ->orWhere('leave_type_id', $leaveRequest->leave_type_id);
                })
                ->whereHas('employees', function($q) use ($leaveRequest) {
                    $q->where('employee_id', $leaveRequest->employee->id);
                })
                ->get();

            $approverIds = $rules
                ->pluck('approver_id')
                ->filter()
                ->unique()
                ->values()
                ->all();

            $approverUsers = User::whereIn('id', $approverIds)->get();

            $approverPayload = [
                'firm_id' => $leaveRequest->firm_id,
                'subject' => "Leave #{$leaveRequest->id} status updated to {$newStatus}",
                'message' => "{$leaveRequest->employee->fname} {$leaveRequest->employee->lname}'s leave from {$fromDT} to {$toDT} has been <strong>{$newStatus}</strong> by " . $user->name . ".",
                'company_name' => $leaveRequest->employee->firm->name ?? 'Company',
            ];

            foreach ($approverUsers as $approverUser) {
                NotificationQueue::create([
                    'firm_id' => $leaveRequest->firm_id,
                    'notifiable_type' => User::class,
                    'notifiable_id' => $approverUser->id,
                    'channel' => 'mail',
                    'data' => json_encode($approverPayload),
                    'status' => 'pending',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
            }
        } catch (\Exception $e) {
            // Log error but don't fail the main operation
            Log::error('Failed to send leave action notifications: ' . $e->getMessage());
        }
    }

    /**
     * Process bulk leave actions (approve/reject) for multiple leave requests
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handleBulkLeaveAction(Request $request)
    {
        try {
            // 1. Validate request
            $validated = $request->validate([
                'leave_request_ids' => 'required|array|min:1',
                'leave_request_ids.*' => 'required|integer|exists:emp_leave_requests,id',
                'action' => 'required|in:approve,reject',
                'remarks' => 'required|string|min:3',
            ]);

            $user = $request->user();
            if (!$user || !$user->employee) {
                return Response::json([
                    'message_type' => 'error',
                    'message_display' => 'flash',
                    'message' => 'Unauthorized access'
                ], 401);
            }

            // 2. Get all leave requests with necessary relations
            $leaveRequests = EmpLeaveRequest::whereIn('id', $validated['leave_request_ids'])
                ->where('firm_id', $user->employee->firm_id)
                ->with(['employee', 'leave_type'])
                ->get();

            // 3. Validate permissions and state for all leaves
            $validationResult = $this->validateBulkLeaveAction($user, $leaveRequests);
            if (!$validationResult['valid']) {
                return Response::json([
                    'message_type' => 'error',
                    'message_display' => 'popup',
                    'message' => $validationResult['message'],
                    'invalid_leaves' => $validationResult['invalid_leaves']
                ], 403);
            }

            // 4. Process leaves in transaction
            DB::beginTransaction();
            try {
                $processedLeaves = $this->processBulkLeaveAction(
                    $leaveRequests,
                    $user,
                    $validated['action'],
                    $validated['remarks']
                );

                DB::commit();

                return Response::json([
                    'message_type' => 'success',
                    'message_display' => 'popup',
                    'message' => count($processedLeaves) . ' leaves processed successfully',
                    'processed_leaves' => $processedLeaves
                ], 200);

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Bulk leave action failed: ' . $e->getMessage(), [
                    'trace' => $e->getTraceAsString(),
                    'leave_ids' => $validated['leave_request_ids']
                ]);

                return Response::json([
                    'message_type' => 'error',
                    'message_display' => 'popup',
                    'message' => 'Failed to process leaves: ' . $e->getMessage()
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('Bulk leave action validation failed: ' . $e->getMessage());
            return Response::json([
                'message_type' => 'error',
                'message_display' => 'popup',
                'message' => 'Invalid request: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Validate bulk leave action permissions and state
     * 
     * @param User $user
     * @param Collection $leaveRequests
     * @return array
     */
    private function validateBulkLeaveAction($user, $leaveRequests)
    {
        $invalidLeaves = [];

        foreach ($leaveRequests as $leave) {
            // Check if user can approve this leave
            if (!$this->canUserApproveLeave($user, $leave)) {
                $invalidLeaves[] = [
                    'id' => $leave->id,
                    'reason' => 'Unauthorized to approve this leave'
                ];
                continue;
            }

            // Check if leave is in approvable state
            if (in_array($leave->status, ['approved', 'rejected', 'cancelled_employee', 'cancelled_hr'])) {
                $invalidLeaves[] = [
                    'id' => $leave->id,
                    'reason' => 'Leave is already ' . $leave->status
                ];
            }
        }

        return [
            'valid' => empty($invalidLeaves),
            'message' => empty($invalidLeaves) ? 'All leaves are valid' : 'Some leaves cannot be processed',
            'invalid_leaves' => $invalidLeaves
        ];
    }

    /**
     * Process bulk leave actions
     * 
     * @param Collection $leaveRequests
     * @param User $user
     * @param string $action
     * @param string $remarks
     * @return array
     */
    private function processBulkLeaveAction($leaveRequests, $user, $action, $remarks)
    {
        $processedLeaves = [];

        foreach ($leaveRequests->chunk(100) as $chunk) {
            foreach ($chunk as $leave) {
                $oldStatus = $leave->status;
                $approvalLevel = $this->getApprovalLevelForUser($user, $leave);
                $maxApprovalLevel = $this->getMaxApprovalLevel($leave);
                
                // Get current approval count
                $approvalCount = EmpLeaveRequestApproval::where('emp_leave_request_id', $leave->id)
                    ->where('status', 'approved')
                    ->count();

                // Determine new status
                $newStatus = $this->determineNewLeaveStatus($action, $approvalCount + 1, $maxApprovalLevel);

                // Update leave request
                $leave->update(['status' => $newStatus]);

                // Create approval record
                $this->createLeaveApproval($leave, $user, $action, $approvalLevel, $remarks);

                // Update balance if needed
                if ($action === 'approve') {
                    $this->updateLeaveBalance($leave);
                }

                // Log event
                $this->createLeaveEvent($leave, $user, $oldStatus, $newStatus, $remarks);

                // Send notifications
                $this->sendLeaveActionNotifications($leave, $newStatus, $user);

                $processedLeaves[] = [
                    'id' => $leave->id,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus
                ];
            }
        }

        return $processedLeaves;
    }

    /**
     * Determine new leave status based on action and approval counts
     * 
     * @param string $action
     * @param int $currentApprovalCount
     * @param int $maxApprovalLevel
     * @return string
     */
    private function determineNewLeaveStatus($action, $currentApprovalCount, $maxApprovalLevel)
    {
        if ($action === 'approve') {
            return ($currentApprovalCount >= $maxApprovalLevel) ? 'approved' : 'approved_further';
        }
        return 'rejected';
    }

    /**
     * Create leave approval record
     * 
     * @param EmpLeaveRequest $leave
     * @param User $user
     * @param string $action
     * @param int $approvalLevel
     * @param string $remarks
     * @return void
     */
    private function createLeaveApproval($leave, $user, $action, $approvalLevel, $remarks)
    {
        EmpLeaveRequestApproval::create([
            'emp_leave_request_id' => $leave->id,
            'approval_level' => $approvalLevel,
            'approver_id' => $user->id,
            'status' => $action === 'approve' ? 'approved' : 'rejected',
            'remarks' => $remarks,
            'acted_at' => now(),
            'firm_id' => $leave->firm_id
        ]);
    }

    /**
     * Create leave event record
     * 
     * @param EmpLeaveRequest $leave
     * @param User $user
     * @param string $oldStatus
     * @param string $newStatus
     * @param string $remarks
     * @return void
     */
    private function createLeaveEvent($leave, $user, $oldStatus, $newStatus, $remarks)
    {
        LeaveRequestEvent::create([
            'emp_leave_request_id' => $leave->id,
            'user_id' => $user->id,
            'event_type' => 'status_changed',
            'from_status' => $oldStatus,
            'to_status' => $newStatus,
            'remarks' => $remarks,
            'firm_id' => $leave->firm_id,
            'created_at' => now()
        ]);
    }

    /**
     * Validate that there are no overlapping leave requests
     * Only blocks if status is 'applied', 'approved', or 'approved_further'
     * Allows if status is 'rejected' or 'cancelled'
     */
    private function validateNoOverlappingLeaves($employeeId, $firmId, $from, $to, $leaveAge)
    {
        $overlappingLeaves = EmpLeaveRequest::where('employee_id', $employeeId)
            ->where('firm_id', $firmId)
            ->whereIn('status', ['applied', 'approved', 'approved_further'])
            ->where(function ($q) use ($from, $to) {
                $q->whereBetween('apply_from', [$from, $to])
                    ->orWhereBetween('apply_to', [$from, $to])
                    ->orWhere(function ($subQ) use ($from, $to) {
                        $subQ->where('apply_from', '<=', $from)
                            ->where('apply_to', '>=', $to);
                    });
            })
            ->with('leave_type')
            ->get();

        if ($overlappingLeaves->isNotEmpty()) {
            $conflicts = $overlappingLeaves->map(function ($leave) {
                $status = $leave->status;
                $leaveType = $leave->leave_type->leave_title ?? 'Unknown';
                $fromDate = Carbon::parse($leave->apply_from)->format('jS F Y');
                $toDate = Carbon::parse($leave->apply_to)->format('jS F Y');
                $days = $leave->apply_days;
                $isHalfDay = floatval($days) === 0.5;
                $dayInfo = $isHalfDay ? 'Half day' : $days . ' day(s)';
                
                return "• {$leaveType} ({$fromDate} to {$toDate}) - {$dayInfo} - Status: " . ucfirst(str_replace('_', ' ', $status));
            })->implode("\n");

            throw new \Exception("You have conflicting leave requests:\n\n{$conflicts}\n\nPlease cancel or modify existing requests before applying for new leave.");
        }
    }
}
