<?php

namespace App\Livewire\Hrms\Offboard;

use App\Models\Hrms\EmployeeExit;
use App\Models\Hrms\ExitApprovalAction;
use App\Models\Hrms\ExitApprovalActionsTrack;
use App\Models\Hrms\ExitApprovalsStepsTrack;
use App\Models\Hrms\ExitApprovalStep;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;
use Flux;

class ExitApprovals extends Component
{
    use WithPagination;

    public $perPage = 10;
    public $search = '';
    public $filter = 'all';
    public $selectedStepTrackId = null;

    // Checklist state
    public $checklistItems = [];

    #[Computed]
    public function steps()
    {
        $firmId = Session::get('firm_id');

        $q = ExitApprovalsStepsTrack::query()
            ->with(['employee.emp_job_profile', 'employee.emp_personal_detail', 'exit', 'exitApprovalActionsTracks', 'department'])
            ->where('firm_id', $firmId)
            ->whereIn('status', ['pending', 'in_progress', 'approved'])
            ->orderBy('flow_order')
            ->orderByDesc('id');

        if (trim($this->search) !== '') {
            $term = '%' . trim($this->search) . '%';
            $q->whereHas('employee', function ($qq) use ($term) {
                $qq->where('fname', 'like', $term)
                   ->orWhere('lname', 'like', $term);
            });
        }

        // Optional: restrict to approver's department unless user has global permission
        $approverDeptIds = $this->resolveApproverDepartments();
        if (!$this->userCanApproveAll() && !empty($approverDeptIds)) {
            $q->whereIn('department_id', $approverDeptIds);
        }

        return $q->paginate($this->perPage);
    }

    public function setFilter(string $filter): void
    {
        $this->filter = in_array($filter, ['all','pending','in_progress','completed','overdue']) ? $filter : 'all';
        $this->resetPage();
    }

    protected function resolveApproverDepartments(): array
    {
        $deptId = optional(optional(auth()->user())->employee)->emp_job_profile->department_id ?? null;
        return $deptId ? [$deptId] : [];
    }

    protected function userCanApproveAll(): bool
    {
        return (bool) (optional(auth()->user())->can('exit.approvals.manage') ?? false);
    }

    public function canActOn(int $stepTrackId): bool
    {
        $track = ExitApprovalsStepsTrack::select('id','exit_id','flow_order','status','department_id')
            ->find($stepTrackId);
        if (!$track) {
            return false;
        }

        // Must be pending or in_progress and not blocked
        if (!in_array($track->status, ['pending', 'in_progress'])) {
            return false;
        }

        // Department-based gate unless user has global permission
        if (!$this->userCanApproveAll()) {
            $allowed = $this->resolveApproverDepartments();
            if (empty($allowed) || !in_array($track->department_id, $allowed)) {
                return false;
            }
        }

        // Enforce serial order: there must be no earlier unapproved step for this exit
        $existsEarlier = ExitApprovalsStepsTrack::where('exit_id', $track->exit_id)
            ->where('flow_order', '<', $track->flow_order)
            ->where('status', '!=', 'approved')
            ->exists();
        if ($existsEarlier) {
            return false;
        }

        return true;
    }

    /**
     * Get employee profile image URL (from Spatie Media Library)
     */
    public function getEmployeeImageUrl($employee)
    {
        if ($employee && $employee->emp_personal_detail) {
            $media = $employee->emp_personal_detail->getMedia('employee_images')->first();
            if ($media) {
                return $media->getUrl();
            }
        }
        return null;
    }

    public function openChecklist(int $stepTrackId): void
    {
        $this->selectedStepTrackId = $stepTrackId;
        $this->checklistItems = ExitApprovalActionsTrack::where('exit_approvals_steps_track_id', $stepTrackId)
            ->orderBy('id')
            ->get()
            ->toArray();
        $this->modal('mdl-exit-checklist')->show();
    }

    public function markAllChecklistCleared(): void
    {
        if (!$this->selectedStepTrackId) {
            return;
        }

        DB::transaction(function () {
            ExitApprovalActionsTrack::where('exit_approvals_steps_track_id', $this->selectedStepTrackId)
                ->update([
                    'status' => 'cleared',
                    'clearance_by_user_id' => auth()->id(),
                ]);
        });

        $this->openChecklist($this->selectedStepTrackId);

        Flux::toast(
            variant: 'success',
            heading: 'Checklist updated',
            text: 'All items marked as cleared.'
        );
    }

    public function approveStep(int $stepTrackId, string $remarks = null): void
    {
        // Guard: only actionable steps can be approved
        if (!$this->canActOn($stepTrackId)) {
            Flux::toast(variant: 'error', heading: 'Not allowed', text: 'This step cannot be approved yet or you are not the approver.');
            return;
        }

        DB::transaction(function () use ($stepTrackId, $remarks) {
            $track = ExitApprovalsStepsTrack::with(['exitApprovalActionsTracks', 'exit'])
                ->lockForUpdate()
                ->findOrFail($stepTrackId);

            if (!in_array($track->status, ['pending', 'in_progress'])) {
                throw new \Exception('This step is not actionable.');
            }

            // Ensure all checklist items are cleared; if not, auto-clear them.
            $hasPendingChecklist = $track->exitApprovalActionsTracks()
                ->where('status', '!=', 'cleared')
                ->exists();

            if ($hasPendingChecklist) {
                $track->exitApprovalActionsTracks()
                    ->update([
                        'status' => 'cleared',
                        'clearance_by_user_id' => auth()->id(),
                    ]);
            }

            // Approve current step
            $track->status = 'approved';
            if ($remarks !== null) {
                $track->remarks = $remarks;
            }
            $track->save();

            // Serial flow: unlock next flow_order only when all tracks at current flow_order approved
            $currentOrder = $track->flow_order;
            $exitId = $track->exit_id;

            $allApprovedAtThisOrder = ExitApprovalsStepsTrack::where('exit_id', $exitId)
                ->where('flow_order', $currentOrder)
                ->where('status', '!=', 'approved')
                ->doesntExist();

            if ($allApprovedAtThisOrder) {
                $nextOrder = ExitApprovalsStepsTrack::where('exit_id', $exitId)
                    ->where('flow_order', '>', $currentOrder)
                    ->min('flow_order');

                if ($nextOrder) {
                    ExitApprovalsStepsTrack::where('exit_id', $exitId)
                        ->where('flow_order', $nextOrder)
                        ->where('status', 'blocked')
                        ->update(['status' => 'pending']);
                } else {
                    // No next step: mark EmployeeExit in_progress -> completed
                    if (in_array($track->exit->status, ['initiated', 'pending', 'in_progress'])) {
                        $track->exit->status = 'completed';
                        $track->exit->save();
                    }
                }
            }
        });

        Flux::toast(
            variant: 'success',
            heading: 'Step approved',
            text: 'Step approved successfully.'
        );
    }

    public function rejectStep(int $stepTrackId, string $remarks = null): void
    {
        // Guard: only actionable steps can be rejected
        if (!$this->canActOn($stepTrackId)) {
            Flux::toast(variant: 'error', heading: 'Not allowed', text: 'This step cannot be rejected yet or you are not the approver.');
            return;
        }

        DB::transaction(function () use ($stepTrackId, $remarks) {
            $track = ExitApprovalsStepsTrack::with('exit')
                ->lockForUpdate()
                ->findOrFail($stepTrackId);

            if (!in_array($track->status, ['pending', 'in_progress'])) {
                throw new \Exception('This step is not actionable.');
            }

            $track->status = 'rejected';
            if ($remarks !== null) {
                $track->remarks = $remarks;
            }
            $track->save();

            // Put the whole exit on hold (business rule)
            if (in_array($track->exit->status, ['initiated', 'pending', 'in_progress'])) {
                $track->exit->status = 'on_hold';
                $track->exit->save();
            }
        });

        Flux::toast(
            variant: 'success',
            heading: 'Step rejected',
            text: 'Step rejected and exit placed on hold.'
        );
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Offboard/blades/exit-approvals.blade.php'));
    }
}
