<?php

namespace App\Livewire\Hrms\Onboard;

use App\Models\Hrms\Employee;
use App\Models\Hrms\EmpWorkShift;
use App\Models\Hrms\WorkShift;
use App\Models\Hrms\WorkShiftDay;
use Carbon\Carbon;
use Flux;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class WorkShiftAllocations extends Component
{
    use WithPagination;

    public array $listsForFields = [];

    public array $filters = [
        'employees' => '',
        'work_shift_id' => '',
    ];

    public $sortBy = 'created_at';
    public $sortDirection = 'desc';

    public ?int $selectedEmpId = null;

    public array $allocateForm = [
        'employee_id' => null,
        'work_shift_id' => null,
        'start_date' => '',
        'end_date' => '',
    ];

    public ?int $selectedAllocationId = null; // for deallocation

    protected $listeners = [
        'close-modal' => 'closeModal',
    ];

    public function mount(): void
    {
        $this->initListsForFields();
        $this->allocateForm['start_date'] = Carbon::today()->format('Y-m-d');
    }

    protected function initListsForFields(): void
    {
        $this->listsForFields['work_shifts'] = WorkShift::where('firm_id', session('firm_id'))
            ->where(function ($q) {
                $q->whereNull('is_inactive')->orWhere('is_inactive', false);
            })
            ->orderBy('shift_title')
            ->pluck('shift_title', 'id')
            ->toArray();
    }

    public function sort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
    }

    public function applyFilters(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->filters = [
            'employees' => '',
            'work_shift_id' => '',
        ];
        $this->resetPage();
    }

    #[Computed]
    public function employeeslist()
    {
        return Employee::query()
            ->with([
                'emp_job_profile.department',
                'emp_job_profile.designation',
                'emp_work_shifts' => function ($q) {
                    $q->with(['work_shift.work_shift_days'])->orderByDesc('start_date');
                },
            ])
            ->when($this->sortBy, fn($query) => $query->orderBy($this->sortBy, $this->sortDirection))
            ->when($this->filters['employees'], function ($query, $value) {
                $query->where(function ($q) use ($value) {
                    $q->where('fname', 'like', "%{$value}%")
                        ->orWhere('mname', 'like', "%{$value}%")
                        ->orWhere('lname', 'like', "%{$value}%");
                });
            })
            ->when($this->filters['work_shift_id'], function ($query, $value) {
                $query->whereHas('emp_work_shifts', function ($q) use ($value) {
                    $q->where('work_shift_id', $value);
                });
            })
            ->where('firm_id', session('firm_id'))
            ->paginate(12);
    }

    public function showManageModal(int $employeeId): void
    {
        $this->selectedEmpId = $employeeId;
        $this->allocateForm['employee_id'] = $employeeId;
        $this->allocateForm['work_shift_id'] = null;
        $this->allocateForm['start_date'] = Carbon::today()->format('Y-m-d');
        $this->allocateForm['end_date'] = '';
        $this->modal('manage-work-shifts')->show();
    }

    public function allocateShift(): void
    {
        $data = $this->validate([
            'allocateForm.employee_id' => 'required|integer|exists:employees,id',
            'allocateForm.work_shift_id' => 'required|integer|exists:work_shifts,id',
            'allocateForm.start_date' => 'required|date',
            'allocateForm.end_date' => 'nullable|date|after_or_equal:allocateForm.start_date',
        ]);

        $employeeId = $data['allocateForm']['employee_id'];
        $workShiftId = $data['allocateForm']['work_shift_id'];
        $startDate = Carbon::parse($data['allocateForm']['start_date'])->startOfDay();
        $endDate = empty($data['allocateForm']['end_date']) ? null : Carbon::parse($data['allocateForm']['end_date'])->endOfDay();

        // Find existing allocations that overlap by date range first
        $existingAllocations = EmpWorkShift::where('employee_id', $employeeId)
            ->where('firm_id', session('firm_id'))
            ->where(function ($q) use ($startDate, $endDate) {
                $q->where(function ($qi) use ($startDate, $endDate) {
                    $qi->whereNull('end_date')->where('start_date', '<=', $endDate ?? Carbon::maxValue());
                })
                ->orWhere(function ($qi) use ($startDate, $endDate) {
                    $qi->whereNotNull('end_date')
                        ->where(function ($qj) use ($startDate, $endDate) {
                            $qj->whereBetween('start_date', [$startDate, $endDate ?? Carbon::maxValue()])
                               ->orWhereBetween('end_date', [$startDate, $endDate ?? Carbon::maxValue()])
                               ->orWhere(function ($qk) use ($startDate, $endDate) {
                                   $qk->where('start_date', '<=', $startDate)
                                      ->where('end_date', '>=', $endDate ?? $startDate);
                               });
                        });
                });
            })
            ->get();

        // Helper to detect time overlap between two windows
        $timeOverlap = function ($aStart, $aEnd, $bStart, $bEnd) {
            if (!$aStart || !$aEnd || !$bStart || !$bEnd) {
                // Be conservative if any window is missing
                return true;
            }
            return $aStart < $bEnd && $bStart < $aEnd;
        };

        $hasTrueOverlap = false;

        // Compare per-day time windows using WorkShiftDay for intersecting date ranges
        foreach ($existingAllocations as $allocation) {
            $allocationStart = $allocation->start_date->copy()->startOfDay();
            $allocationEnd = ($allocation->end_date ?? Carbon::maxValue())->copy()->endOfDay();

            // Determine the intersecting date range between new and existing allocations
            $newEndForRange = ($endDate ?? Carbon::maxValue())->copy()->endOfDay();
            $rangeStart = $startDate->greaterThan($allocationStart) ? $startDate->copy() : $allocationStart;
            $rangeEnd = $newEndForRange->lessThan($allocationEnd) ? $newEndForRange->copy() : $allocationEnd;

            if ($rangeStart->gt($rangeEnd)) {
                continue; // no date overlap
            }

            // Load WorkShiftDay windows for both shifts in the overlapped date span
            $newDays = WorkShiftDay::where('work_shift_id', $workShiftId)
                ->whereBetween('work_date', [$rangeStart->toDateString(), $rangeEnd->toDateString()])
                ->get()
                ->keyBy(function ($d) { return $d->work_date->toDateString(); });

            $oldDays = WorkShiftDay::where('work_shift_id', $allocation->work_shift_id)
                ->whereBetween('work_date', [$rangeStart->toDateString(), $rangeEnd->toDateString()])
                ->get()
                ->keyBy(function ($d) { return $d->work_date->toDateString(); });

            // Walk each date in the overlap and check time windows
            for ($date = $rangeStart->copy(); $date->lte($rangeEnd); $date->addDay()) {
                $key = $date->toDateString();
                if (!isset($newDays[$key]) || !isset($oldDays[$key])) {
                    // If either shift lacks a defined window for this day, treat as overlapping
                    $hasTrueOverlap = true;
                    break 2;
                }

                $newWindow = $newDays[$key];
                $oldWindow = $oldDays[$key];

                if ($timeOverlap($newWindow->start_time, $newWindow->end_time, $oldWindow->start_time, $oldWindow->end_time)) {
                    $hasTrueOverlap = true;
                    break 2;
                }
            }
        }

        if ($hasTrueOverlap) {
            Flux::toast(heading: 'Allocation failed', text: 'Shift times overlap with an existing allocation.');
            return;
        }

        EmpWorkShift::create([
            'firm_id' => session('firm_id'),
            'employee_id' => $employeeId,
            'work_shift_id' => $workShiftId,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        $this->modal('manage-work-shifts')->close();
        $this->dispatch('employee-updated');
        Flux::toast(heading: 'Shift allocated', text: 'Work shift assigned successfully.');
    }

    public function confirmDeallocate(int $allocationId): void
    {
        $this->selectedAllocationId = $allocationId;
        $this->modal('confirm-deallocate')->show();
    }

    public function deallocateShift(): void
    {
        if (!$this->selectedAllocationId) {
            return;
        }

        $allocation = EmpWorkShift::where('id', $this->selectedAllocationId)
            ->where('firm_id', session('firm_id'))
            ->first();

        if (!$allocation) {
            Flux::toast(heading: 'Not found', text: 'Allocation not found.');
            return;
        }

        $allocation->delete();
        $this->selectedAllocationId = null;
        $this->modal('confirm-deallocate')->close();
        Flux::toast(heading: 'Deallocated', text: 'Work shift deallocated successfully.');
    }

    public function closeModal(string $modalName): void
    {
        $this->modal($modalName)->close();
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Onboard/blades/work-shift-allocations.blade.php'));
    }
}


