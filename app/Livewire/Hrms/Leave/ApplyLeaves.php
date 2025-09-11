<?php

namespace App\Livewire\Hrms\Leave;

use Livewire\Component;
use App\Models\Hrms\EmpLeaveRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Models\Hrms\LeaveType;
use App\Models\Hrms\EmpLeaveBalance;
use Illuminate\Support\Facades\DB;
use Flux\Flux;
class ApplyLeaves extends Component
{
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $search = '';
    public $perPage = 10;

    // For apply leave modal
    public $leaveTypes = [];
    public $leaveBalances = [];
    public $applyForm = [
        'leave_type_id' => '',
        'leave_age' => 'single', // default to single day
        'apply_from' => '',
        'apply_to' => '',
        'half_day_type' => '',
        'reason' => '',
    ];
    public $applyError = '';
    public $applySuccess = '';

    public function openApplyModal()
    {
        $employee = Auth::user()->employee ?? null;
        if (!$employee) {
            $this->leaveTypes = [];
            $this->leaveBalances = [];
            return;
        }
        $this->leaveBalances = EmpLeaveBalance::with('leave_type')
            ->where('employee_id', $employee->id)
            ->where('balance', '>', 0)
            ->get();
           
        $leaveTypeIds = $this->leaveBalances->pluck('leave_type_id')->unique();
        $this->leaveTypes = LeaveType::where('firm_id', $employee->firm_id)
            ->where('is_inactive', false)
            ->whereIn('id', $leaveTypeIds)
            ->get();
        $this->applyForm = [
            'leave_type_id' => '',
            'leave_age' => 'single',
            'apply_from' => '',
            'apply_to' => '',
            'half_day_type' => '',
            'reason' => '',
        ];
        $this->applyError = '';
        $this->applySuccess = '';
        $this->modal('mdl-apply-leave')->show();
    }

    public function applyLeave()
    {
        $employee = Auth::user()->employee ?? null;
        if (!$employee) {
            $this->applyError = 'Employee not found.';
            return;
        }

        // Dynamic validation rules based on leave_age
        $rules = [
            'applyForm.leave_type_id' => 'required|integer|exists:leave_types,id',
            'applyForm.leave_age' => 'required|in:single,multi,half',
            'applyForm.reason' => 'nullable|string|max:1000',
        ];
        if ($this->applyForm['leave_age'] === 'single') {
            $rules['applyForm.apply_from'] = 'required|date';
        } elseif ($this->applyForm['leave_age'] === 'multi') {
            $rules['applyForm.apply_from'] = 'required|date';
            $rules['applyForm.apply_to'] = 'required|date|after_or_equal:applyForm.apply_from';
        } elseif ($this->applyForm['leave_age'] === 'half') {
            $rules['applyForm.apply_from'] = 'required|date';
            $rules['applyForm.half_day_type'] = 'required|in:first_half,second_half';
        }

        $validated = $this->validate($rules);

        $leaveAge = $validated['applyForm']['leave_age'];
        $from = $validated['applyForm']['apply_from'];
        $to = $leaveAge === 'multi' ? $validated['applyForm']['apply_to'] : $from;
        $halfDayType = $leaveAge === 'half' ? $validated['applyForm']['half_day_type'] : null;

        // Calculate days
        if ($leaveAge === 'half') {
            $days = 0.5;
        } elseif ($leaveAge === 'single') {
            $days = 1;
        } else { // multi
            $days = (strtotime($to) - strtotime($from)) / 86400 + 1;
        }

        // Check balance
        $balance = EmpLeaveBalance::where('employee_id', $employee->id)
            ->where('leave_type_id', $validated['applyForm']['leave_type_id'])
            ->where('period_start', '<=', $from)
            ->where('period_end', '>=', $to)
            ->where('balance', '>', 0)
            ->first();

        if (!$balance || $balance->balance < $days) {
            $this->applyError = 'Insufficient leave balance.';
            return;
        }

        DB::beginTransaction();
        try {
            $leaveRequestData = [
                'firm_id' => $employee->firm_id,
                'employee_id' => $employee->id,
                'leave_type_id' => $validated['applyForm']['leave_type_id'],
                'apply_from' => $from,
                'apply_to' => $to,
                'apply_days' => $days,
                'reason' => $validated['applyForm']['reason'],
                'status' => 'applied',
            ];
            if ($leaveAge === 'half') {
                $leaveRequestData['half_day_type'] = $halfDayType;
            }
            $leaveRequest = EmpLeaveRequest::create($leaveRequestData);

            // Email notifications (existing logic)
            $now = now();
            $fromFormatted = date('j-M-Y', strtotime($from));
            $toFormatted = date('j-M-Y', strtotime($to));
            $applicantPayload = [
                'firm_id' => $employee->firm_id,
                'subject' => 'Your leave request is submitted',
                'message' => "You have applied for leave from {$fromFormatted} to {$toFormatted}.",
                'company_name' => $employee->firm->name ?? '',
            ];
            \App\Models\NotificationQueue::create([
                'firm_id'         => $employee->firm_id,
                'notifiable_type' => \App\Models\User::class,
                'notifiable_id'   => $employee->user_id ?? $employee->user->id ?? null,
                'channel'         => 'mail',
                'data'            => json_encode($applicantPayload),
                'status'          => 'pending',
                'created_at'      => $now,
                'updated_at'      => $now,
            ]);
            $rules = \App\Models\Hrms\LeaveApprovalRule::where('firm_id', $employee->firm_id)
                ->where('approval_mode', '!=', 'view_only')
                ->where('is_inactive', false)
                ->whereDate('period_start', '<=', $from)
                ->whereDate('period_end', '>=', $to)
                ->where(function($q) use ($validated) {
                    $q->whereNull('leave_type_id')
                        ->orWhere('leave_type_id', $validated['applyForm']['leave_type_id']);
                })
                ->whereHas('employees', function($q) use ($employee) {
                    $q->where('employee_id', $employee->id);
                })
                ->get();
            $approverIds = $rules->pluck('approver_id')->filter()->unique()->values()->all();
            $approverUsers = \App\Models\User::whereIn('id', $approverIds)->get();
            $approverPayload = [
                'firm_id'      => $employee->firm_id,
                'subject'      => 'New leave request pending your approval',
                'message'      => $employee->fname . ' ' . $employee->lname . " has requested leave ({$fromFormatted} → {$toFormatted}).",
                'company_name' => $employee->firm->name ?? '',
            ];
            foreach ($approverUsers as $approver) {
                \App\Models\NotificationQueue::create([
                    'firm_id'         => $employee->firm_id,
                    'notifiable_type' => \App\Models\User::class,
                    'notifiable_id'   => $approver->id,
                    'channel'         => 'mail',
                    'data'            => json_encode($approverPayload),
                    'status'          => 'pending',
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ]);
            }
            DB::commit();
            \Flux\Flux::toast('Your Leave has been applied Successfully.');
            $this->modal('mdl-apply-leave')->close();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->applyError = 'Failed to submit leave request: ' . $e->getMessage();
        }
    }

    public function render()
    {
        $employee = Auth::user()->employee ?? null;
        $leaveRequests = collect();
        if ($employee) {
            $query = EmpLeaveRequest::with('leave_type')
                ->where('employee_id', $employee->id);

            if ($this->search) {
                $query->where(function($q) {
                    $q->whereHas('leave_type', function($q2) {
                        $q2->where('leave_title', 'like', '%'.$this->search.'%');
                    })
                    ->orWhere('reason', 'like', '%'.$this->search.'%');
                });
            }

            $leaveRequests = $query
                ->orderBy($this->sortBy, $this->sortDirection)
                ->paginate($this->perPage);
        }

        return view()->file(app_path('Livewire/Hrms/Leave/blades/apply-leaves.blade.php'), [
            'leaveRequests' => $leaveRequests,
            'sortBy' => $this->sortBy,
            'sortDirection' => $this->sortDirection,
            'leaveTypes' => $this->leaveTypes,
            'leaveBalances' => $this->leaveBalances,
            'applyForm' => $this->applyForm,
            'applyError' => $this->applyError,
            'applySuccess' => $this->applySuccess,
        ]);
    }
}
