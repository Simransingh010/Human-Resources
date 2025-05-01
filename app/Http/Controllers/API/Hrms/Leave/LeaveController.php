<?php

namespace App\Http\Controllers\API\Hrms\Leave;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Hrms\EmpLeaveBalance;  // ← make sure this path matches your model
use App\Models\Hrms\EmpLeaveRequest;
use App\Models\Hrms\EmpLeaveRequestApproval;
use App\Models\Hrms\LeaveRequestEvent;
use App\Models\Hrms\LeaveApprovalRule;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LeaveController extends Controller
{
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
            return response()->json([
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
        return response()->json([
            'message_type' => 'success',
            'message_display' => 'none',
            'message' => 'Leave balances fetched',
            'leavesbalnces' => $data,
        ], 200);
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

        $from = Carbon::parse($validated['apply_from']);
        $to   = Carbon::parse($validated['apply_to']);
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
                ->whereDate('period_start', '<=', now())
                ->whereDate('period_end',   '>=', now())
                ->where(function($q) use ($validated) {
                    $q->whereNull('leave_type_id')
                        ->orWhere('leave_type_id', $validated['leave_type_id']);
                })
                ->orderBy('approval_level')
                ->get();

            // 2.a) If no rule applies, bail out
            if ($rules->isEmpty()) {
                throw new \Exception('No applicable leave approval rule found for this request');
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

            // 4) Log the “created” event
            LeaveRequestEvent::create([
                'emp_leave_request_id' => $lr->id,
                'user_id'              => $employee->id,
                'event_type'           => 'status_changed', // status_changed, clarification_requested, clarification_provided
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

        return response()->json([
            'message_type'    => 'success',
            'message_display' => 'popup',
            'message'         => 'Leave request submitted',
            'data'            => $lr->load('leave_type'),
        ], 201);
    }

    public function leaveRequests(Request $request)
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

        return response()->json([
            'message_type'    => 'success',
            'message_display' => 'none',
            'message'         => 'Leave requests list',
            'data'            => $leaves
        ], 200);

    }

}
