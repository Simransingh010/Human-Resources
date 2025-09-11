<?php

namespace App\Livewire\Hrms\Leave;

use App\Models\Hrms\EmpAttendance;
use App\Models\Hrms\EmpLeaveBalance;
use App\Models\Hrms\EmpLeaveRequest;
use App\Models\Hrms\EmpLeaveTransaction;
use App\Models\Hrms\Employee;
use App\Models\Hrms\LeaveRequestEvent;
use App\Models\Hrms\LeaveType;
use App\Models\Saas\FirmUser;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;
use Flux;

class LeaveRejections extends Component
{
    use WithPagination;

    public $perPage = 10;
    public $sortBy = 'work_date';
    public $sortDirection = 'desc';

    public array $listsForFields = [];
    public array $filters = [
        'employee_id' => '',
        'from_date' => '',
        'to_date' => '',
    ];

    public bool $isActionModalOpen = false;
    public ?int $selectedAttendanceId = null;

    public array $formData = [
        'remarks' => null,
    ];

    protected function rules(): array
    {
        return [
            'formData.remarks' => 'nullable|string|min:3',
        ];
    }

    public function mount(): void
    {
        $this->initListsForFields();
    }

    protected function initListsForFields(): void
    {
        $this->listsForFields['employees'] = Employee::where('firm_id', Session::get('firm_id'))
            ->pluck('fname', 'id')
            ->toArray();
    }

    #[Computed]
    public function list()
    {
        $query = EmpAttendance::query()
            ->with(['employee'])
            ->where('firm_id', Session::get('firm_id'))
            ->where('attendance_status_main', 'POL');

        if (!empty($this->filters['employee_id'])) {
            $query->where('employee_id', $this->filters['employee_id']);
        }
        if (!empty($this->filters['from_date'])) {
            $query->whereDate('work_date', '>=', $this->filters['from_date']);
        }
        if (!empty($this->filters['to_date'])) {
            $query->whereDate('work_date', '<=', $this->filters['to_date']);
        }

        return $query->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);
    }

    public function applyFilters(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->filters = [
            'employee_id' => '',
            'from_date' => '',
            'to_date' => '',
        ];
        $this->resetPage();
    }

    public function showActionModal(int $attendanceId): void
    {
        $this->resetErrorBag();
        $this->resetValidation();
        $attendance = EmpAttendance::where('id', $attendanceId)
            ->where('firm_id', Session::get('firm_id'))
            ->first();

        if (! $attendance) {
            Flux::toast(
                variant: 'error',
                heading: 'Error',
                text: 'Attendance not found.'
            );
            return;
        }

        $this->selectedAttendanceId = $attendance->id;
        $this->formData['remarks'] = null;
        $this->isActionModalOpen = true;
    }

    public function closeModal(): void
    {
        $this->isActionModalOpen = false;
        $this->selectedAttendanceId = null;
        $this->formData['remarks'] = null;
        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function handleAction(string $action, int $attendanceId): void
    {
        $this->validate();
        if (! in_array($action, ['accept', 'reject'])) {
            Flux::toast(variant: 'error', heading: 'Invalid action', text: 'Unknown action.');
            return;
        }

        $attendance = EmpAttendance::with('employee')
            ->where('id', $attendanceId)
            ->where('firm_id', Session::get('firm_id'))
            ->first();
        if (! $attendance) {
            Flux::toast(variant: 'error', heading: 'Error', text: 'Attendance not found.');
            return;
        }

        if ($attendance->attendance_status_main !== 'POL') {
            Flux::toast(variant: 'error', heading: 'Not applicable', text: 'Attendance is not in POL state.');
            return;
        }

        if (! $this->canApprovePol($attendance)) {
            Flux::toast(variant: 'error', heading: 'Unauthorized', text: 'You are not authorized to perform this action.');
            return;
        }

        try {
            DB::beginTransaction();

            if ($action === 'accept') {
                // 1) Flip attendance to Present. final_day_weightage will be recalculated at punch-out; keep current values
                $attendance->attendance_status_main = 'P';
                $attendance->attend_remarks = trim(($attendance->attend_remarks ? $attendance->attend_remarks . ' | ' : '') . 'POL approved');
                $attendance->save();

                // 2) Credit back leave balance for this date
                $this->creditBackLeaveForDate($attendance);

                // 3) Log an event on the covering leave request if any
                if ($leaveRequest = $this->findCoveringLeaveRequest($attendance->employee_id, $attendance->work_date)) {
                    LeaveRequestEvent::create([
                        'emp_leave_request_id' => $leaveRequest->id,
                        'user_id' => Auth::id(),
                        'event_type' => 'status_changed',
                        'from_status' => $leaveRequest->status,
                        'to_status' => $leaveRequest->status, // status unchanged; informational event
                        'remarks' => 'POL approved for ' . Carbon::parse($attendance->work_date)->format('Y-m-d') . '. ' . ($this->formData['remarks'] ?? ''),
                        'firm_id' => Session::get('firm_id'),
                        'created_at' => now(),
                    ]);
                }

            } else {
                // reject: keep POL, just append remark and event if any leave exists
                $attendance->attend_remarks = trim(($attendance->attend_remarks ? $attendance->attend_remarks . ' | ' : '') . 'POL rejected');
                $attendance->save();

                if ($leaveRequest = $this->findCoveringLeaveRequest($attendance->employee_id, $attendance->work_date)) {
                    LeaveRequestEvent::create([
                        'emp_leave_request_id' => $leaveRequest->id,
                        'user_id' => Auth::id(),
                        'event_type' => 'status_changed',
                        'from_status' => $leaveRequest->status,
                        'to_status' => $leaveRequest->status,
                        'remarks' => 'POL rejected for ' . Carbon::parse($attendance->work_date)->format('Y-m-d') . '. ' . ($this->formData['remarks'] ?? ''),
                        'firm_id' => Session::get('firm_id'),
                        'created_at' => now(),
                    ]);
                }
            }

            DB::commit();

            $this->closeModal();
            $this->resetPage();
            $this->dispatch('leave-status-updated');

            Flux::toast(
                variant: 'success',
                heading: $action === 'accept' ? 'POL Approved' : 'POL Rejected',
                text: $action === 'accept' ? 'Attendance set to Present and leave balance credited.' : 'Attendance remains POL.'
            );
        } catch (\Throwable $e) {
            DB::rollBack();
            Flux::toast(variant: 'error', heading: 'Error', text: $e->getMessage());
        }
    }

    public function canApprovePol(EmpAttendance $attendance): bool
    {
        // A user can approve if they are an approver of a covering leave for this employee
        $leaveRequest = $this->findCoveringLeaveRequest($attendance->employee_id, $attendance->work_date);
        if (! $leaveRequest) {
            return false;
        }

        $rules = \App\Models\Hrms\LeaveApprovalRule::where('firm_id', Session::get('firm_id'))
            ->where('approver_id', Auth::id())
            ->where(function ($query) use ($leaveRequest) {
                $query->where('leave_type_id', $leaveRequest->leave_type_id)
                    ->orWhereNull('leave_type_id');
            })
            ->where('is_inactive', false)
            ->whereHas('employees', function($q) use ($attendance) {
                $q->where('employee_id', $attendance->employee_id);
            })
            ->exists();

        return $rules;
    }

    protected function findCoveringLeaveRequest(int $employeeId, $date): ?EmpLeaveRequest
    {
        $workDate = Carbon::parse($date)->toDateString();
        return EmpLeaveRequest::with('leave_type')
            ->where('employee_id', $employeeId)
            ->where('firm_id', Session::get('firm_id'))
            ->whereDate('apply_from', '<=', $workDate)
            ->whereDate('apply_to', '>=', $workDate)
            ->whereIn('status', ['approved', 'approved_further'])
            ->orderByDesc('id')
            ->first();
    }

    protected function creditBackLeaveForDate(EmpAttendance $attendance): void
    {
        $leaveRequest = $this->findCoveringLeaveRequest($attendance->employee_id, $attendance->work_date);
        if (! $leaveRequest) {
            return; // No covering leave -> nothing to credit
        }

        $creditAmount = 1.0;
        // If the covering leave is explicitly a half-day request
        if (floatval($leaveRequest->apply_days) === 0.5) {
            $creditAmount = 0.5;
        }

        // Find a matching leave balance row that covers this date
        $leaveBalance = EmpLeaveBalance::where('firm_id', $leaveRequest->firm_id)
            ->where('employee_id', $leaveRequest->employee_id)
            ->where('leave_type_id', $leaveRequest->leave_type_id)
            ->where('period_start', '<=', Carbon::parse($attendance->work_date))
            ->where('period_end', '>=', Carbon::parse($attendance->work_date))
            ->first();

        if ($leaveBalance) {
            $leaveBalance->consumed_days = max(0, ($leaveBalance->consumed_days - $creditAmount));
            $leaveBalance->balance = $leaveBalance->allocated_days + $leaveBalance->carry_forwarded_days - $leaveBalance->consumed_days - $leaveBalance->lapsed_days;
            $leaveBalance->save();

            EmpLeaveTransaction::create([
                'leave_balance_id' => $leaveBalance->id,
                'emp_leave_request_id' => $leaveRequest->id,
                'transaction_type' => 'credit',
                'transaction_date' => now(),
                'amount' => $creditAmount,
                'reference_id' => $attendance->id,
                'created_by' => Auth::id(),
                'firm_id' => $leaveRequest->firm_id,
                'remarks' => 'POL approved: credited back',
            ]);
        }
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Leave/blades/leave-rejections.blade.php'));
    }
}


