<?php

namespace App\Livewire\Hrms\Workshifts;

use App\Models\Hrms\WorkShift;
use App\Models\Hrms\WorkShiftsAlgo;
use Livewire\Component;
use Livewire\WithPagination;
use Carbon\Carbon;
use Flux;
use Illuminate\Support\Facades\DB;
use App\Models\Hrms\EmpWorkShift;
use App\Models\Settings\Department;

class WorkShifts extends Component
{
    use WithPagination;

    public $selectedShiftId = null;
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $statuses;
    public $formData = [
        'id' => null,
        'shift_title' => '',
        'shift_desc' => '',
        'is_inactive' => false,
    ];

    public $isEditing = false;
    public $modal = false;
    public $perPage = 10;
    public $workShiftsAlgo = [];
    public $listsForFields = [];
    // Allocation state
    public $allocation = [
        'wef' => null,
        'wet' => null,
        'algo_id' => null,
        'employee_ids' => [],
    ];
    public array $allocationAlgos = [];
    public array $eligibleEmployees = [];
    public array $eligibleDepartments = [];
    // Field configuration for form and table
    public array $fieldConfig = [
        'shift_title' => ['label' => 'Title', 'type' => 'text'],
        'shift_desc' => ['label' => 'Description', 'type' => 'textarea'],
        'is_inactive' => ['label' => 'isInactive', 'type' => 'boolean'],
    ];

    public array $filterFields = [
        'search_title' => ['label' => 'Title', 'type' => 'text'],
        'is_inactive' => ['label' => 'isInactive', 'type' => 'boolean'],
    ];

    // Add filter properties
    public $filters = [
        'search_title' => '',
        'is_inactive' => '',
    ];

    // Lists for Work Shifts Algo

    public array $fieldConfigAlgo = [
        'work_shift_id' => ['label' => 'Work Shift', 'type' => 'select', 'listKey' => 'work_shifts'],
        'start_date' => ['label' => 'Start Date', 'type' => 'date'],
        'end_date' => ['label' => 'End Date', 'type' => 'date'],
        'start_time' => ['label' => 'Start Time', 'type' => 'time'],
        'week_off_pattern' => ['label' => 'Week Off Pattern', 'type' => 'text'],
        'end_time' => ['label' => 'End Time', 'type' => 'time'],

        'work_breaks' => ['label' => 'Work Breaks', 'type' => 'multiselect', 'listKey' => 'work_breaks'],
        'holiday_calendar_id' => ['label' => 'Holiday Calendar', 'type' => 'select', 'listKey' => 'holiday_calendars'],
        'allow_wfh' => ['label' => 'Allow WFH', 'type' => 'boolean'],
        'is_inactive' => ['label' => 'Status', 'type' => 'boolean'],
    ];

    public array $visibleFields = [];
    public array $visibleFilterFields = [];

    public function mount()
    {
        $this->resetPage();
        $this->refreshStatuses();
        $this->workShiftsAlgo = collect([]);
        
        // Initialize lists for fields
        $this->listsForFields['work_breaks'] = [
            '7' => '7 Minutes',
            '11' => '11 Minutes',
            // Add more break options as needed
        ];

        // Set default visible fields and filters
        $this->visibleFields = ['shift_title', 'shift_desc'];
        $this->visibleFilterFields = ['search_title', 'is_inactive'];
    }

    #[\Livewire\Attributes\Computed]
    public function list()
    {
        return WorkShift::query()
            ->where('firm_id', session('firm_id'))
            ->when($this->filters['search_title'], function ($query) {
                $query->where('shift_title', 'like', '%' . $this->filters['search_title'] . '%');
            })
            ->when($this->filters['is_inactive'] !== '', function ($query) {
                $query->where('is_inactive', $this->filters['is_inactive']);
            })
            ->when($this->sortBy, fn($query) => $query->orderBy($this->sortBy, $this->sortDirection))
            ->paginate($this->perPage);
    }

    public function store()
    {
        $validatedData = $this->validate([
            'formData.shift_title' => 'required|string|max:255',
            'formData.shift_desc' => 'nullable|string',
            'formData.is_inactive' => 'boolean',
        ]);

        $validatedData['formData'] = collect($validatedData['formData'])
            ->map(fn($val) => $val === '' ? null : $val)
            ->toArray();

        $validatedData['formData']['firm_id'] = session('firm_id');

        if ($this->isEditing) {
            $shift = WorkShift::findOrFail($this->formData['id']);
            $shift->update($validatedData['formData']);
            $toastMsg = 'Work Shift updated successfully';
        } else {
            WorkShift::create($validatedData['formData']);
            $toastMsg = 'Work Shift added successfully';
        }

        $this->resetForm();
        $this->refreshStatuses();
        $this->modal('mdl-shift')->close();
        Flux::toast(
            variant: 'success',
            heading: 'Changes saved.',
            text: $toastMsg,
        );
    }

    public function clearFilters()
    {
        $this->reset('filters');
        $this->resetPage();
    }

    public function edit($id)
    {
        $this->formData = WorkShift::findOrFail($id)->toArray();
        $this->isEditing = true;
        $this->modal('mdl-shift')->show();
    }

    public function delete($id)
    {
        $shift = WorkShift::findOrFail($id);

        // Check if shift has related records
        if (
            $shift->work_shift_days()->count() > 0 ||
            $shift->emp_work_shifts()->count() > 0 ||
            $shift->work_shifts_algos()->count() > 0
        ) {
            Flux::toast(
                variant: 'error',
                heading: 'Cannot Delete',
                text: 'This work shift has related records and cannot be deleted.',
            );
            return;
        }

        $shift->delete();

        Flux::toast(
            variant: 'success',
            heading: 'Record Deleted.',
            text: 'Work Shift has been deleted successfully',
        );
    }

    public function resetForm()
    {
        $this->reset(['formData']);
        $this->formData['is_inactive'] = false;
        $this->isEditing = false;
    }

    public function refreshStatuses()
    {
        $this->statuses = WorkShift::where('firm_id', session('firm_id'))
            ->pluck('is_inactive', 'id')
            ->mapWithKeys(fn($val, $key) => [$key => (bool) $val])
            ->toArray();
    }

    public function toggleStatus($shiftId)
    {
        $shift = WorkShift::find($shiftId);
        $shift->is_inactive = !$shift->is_inactive;
        $shift->save();

        $this->statuses[$shiftId] = $shift->is_inactive;
        $this->refreshStatuses();
    }

    public function showWorkShiftDays($id)
    {
        $this->selectedShiftId = $id;
        $this->modal('work-shift-days-modal')->show();
    }

    public function showEmpWorkShifts($id)
    {
        $this->selectedShiftId = $id;
        $this->modal('emp-work-shifts-modal')->show();
    }

    public function showWorkShiftSetup($id)
    {
        $this->selectedShiftId = $id;
//        $this->loadWorkShiftsAlgos();
        $this->modal('work-shifts-algos-modal')->show();
    }

    public function showAllocation($id)
    {
        $this->selectedShiftId = $id;
        $this->resetAllocation();
        $this->loadAllocationAlgos();
        $this->loadEligibleEmployees();
        $this->modal('allocation-modal')->show();
    }

    private function resetAllocation(): void
    {
        $this->allocation = [
            'wef' => null,
            'wet' => null,
            'algo_id' => null,
            'employee_ids' => [],
        ];
        $this->allocationAlgos = [];
        $this->eligibleEmployees = [];
        $this->eligibleDepartments = [];
    }

    private function loadAllocationAlgos(): void
    {
        if (!$this->selectedShiftId) { return; }
        $query = \App\Models\Hrms\WorkShiftsAlgo::query()
            ->where('firm_id', session('firm_id'))
            ->where('work_shift_id', $this->selectedShiftId)
            ->where('is_inactive', false);

        // If dates are provided, show only algos overlapping [wef, wet]
        if (!empty($this->allocation['wef']) && !empty($this->allocation['wet'])) {
            $wef = Carbon::parse($this->allocation['wef'])->startOfDay();
            $wet = Carbon::parse($this->allocation['wet'])->endOfDay();
            $query->where(function($q) use ($wef, $wet) {
                $q->whereBetween('start_date', [$wef, $wet])
                  ->orWhereBetween('end_date', [$wef, $wet])
                  ->orWhere(function($qq) use ($wef, $wet) {
                      $qq->where('start_date', '<=', $wef)
                         ->where('end_date', '>=', $wet);
                  });
            });
        }

        $this->allocationAlgos = $query
            ->orderBy('start_date')
            ->get()
            ->map(fn($a) => $a->toArray())
            ->all();
    }

    public function updatedAllocationWef()
    {
        $this->loadAllocationAlgos();
        $this->loadEligibleEmployees();
    }

    public function updatedAllocationWet()
    {
        $this->loadAllocationAlgos();
        $this->loadEligibleEmployees();
    }

    public function updatedAllocationAlgoId()
    {
        $this->loadEligibleEmployees();
    }

    private function loadEligibleEmployees(): void
    {
        $this->eligibleEmployees = [];
        $this->eligibleDepartments = [];
        if (!$this->selectedShiftId || empty($this->allocation['wef']) || empty($this->allocation['wet'])) {
            return;
        }

        // Employees not already assigned to this work shift/algo for any overlapping dates in range
        $wef = Carbon::parse($this->allocation['wef'])->startOfDay();
        $wet = Carbon::parse($this->allocation['wet'])->endOfDay();

        // Get employees of this firm that do NOT have overlapping EmpWorkShift for this shift
        $assignedEmployeeIds = EmpWorkShift::where('firm_id', session('firm_id'))
            ->where('work_shift_id', $this->selectedShiftId)
            ->where(function($q) use ($wef, $wet) {
                $q->where(function($sq) use ($wef) {
                    $sq->whereNull('end_date')->orWhere('end_date', '>=', $wef);
                })->where(function($sq) use ($wet) {
                    $sq->whereNull('start_date')->orWhere('start_date', '<=', $wet);
                });
            })
            ->pluck('employee_id')
            ->unique()
            ->values()
            ->all();

        $employees = \App\Models\Hrms\Employee::where('firm_id', session('firm_id'))
            ->whereNotIn('id', $assignedEmployeeIds)
            ->select('id', DB::raw("TRIM(CONCAT_WS(' ', COALESCE(fname, ''), COALESCE(mname, ''), COALESCE(lname, ''))) as name"))
            ->orderBy('name')
            ->get()
            ->toArray();

        $this->eligibleEmployees = $employees;

        // Department-wise grouping similar to LeaveAllocations
        $departments = Department::with(['employees' => function ($query) use ($assignedEmployeeIds) {
                $query->where('employees.firm_id', session('firm_id'))
                    ->whereNull('employees.deleted_at')
                    ->whereNotIn('employees.id', $assignedEmployeeIds)
                    ->select('employees.id', 'employees.fname', 'employees.mname', 'employees.lname');
            }])
            ->where('firm_id', session('firm_id'))
            ->where('is_inactive', false)
            ->get();

        $this->eligibleDepartments = $departments->map(function ($department) {
            return [
                'id' => (int) $department->id,
                'title' => $department->title,
                'employees' => $department->employees->map(function ($emp) {
                    $name = trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([$emp->fname, $emp->mname, $emp->lname]))));
                    return [
                        'id' => (int) $emp->id,
                        'name' => $name ?: ('Emp #' . $emp->id),
                    ];
                })->toArray(),
            ];
        })->filter(function ($dept) {
            return !empty($dept['employees']);
        })->values()->all();
    }

    public function assignSelectedEmployees()
    {
        // Validate inputs
        $this->validate([
            'allocation.wef' => 'required|date',
            'allocation.wet' => 'required|date|after_or_equal:allocation.wef',
            'allocation.algo_id' => 'required|exists:work_shifts_algos,id',
            'allocation.employee_ids' => 'required|array|min:1',
        ]);

        $wef = Carbon::parse($this->allocation['wef'])->startOfDay();
        $wet = Carbon::parse($this->allocation['wet'])->endOfDay();

        $algo = \App\Models\Hrms\WorkShiftsAlgo::findOrFail($this->allocation['algo_id']);
        if ($algo->work_shift_id !== $this->selectedShiftId) {
            Flux::toast(variant: 'error', heading: 'Invalid', text: 'Algorithm does not belong to selected work shift.');
            return;
        }

        try {
            DB::transaction(function () use ($wef, $wet, $algo) {
                foreach ($this->allocation['employee_ids'] as $employeeId) {
                    $this->assignEmployeeToShiftWithSplitting((int)$employeeId, $this->selectedShiftId, $algo->id, $wef, $wet);
                }
            });

            Flux::toast(variant: 'success', heading: 'Assigned', text: 'Employees allocated successfully.');
            $this->modal('allocation-modal')->close();
            $this->resetAllocation();
        } catch (\Throwable $e) {
            Flux::toast(variant: 'error', heading: 'Allocation Failed', text: $e->getMessage());
        }
    }

    private function assignEmployeeToShiftWithSplitting(int $employeeId, int $workShiftId, int $algoId, Carbon $wef, Carbon $wet): void
    {
        // Find overlapping EmpWorkShift records for this employee (any shift)
        $overlaps = EmpWorkShift::where('firm_id', session('firm_id'))
            ->where('employee_id', $employeeId)
            ->where(function($q) use ($wef, $wet) {
                $q->where(function($sq) use ($wef) { $sq->whereNull('end_date')->orWhere('end_date', '>=', $wef); })
                  ->where(function($sq) use ($wet) { $sq->whereNull('start_date')->orWhere('start_date', '<=', $wet); });
            })
            ->orderBy('start_date')
            ->lockForUpdate()
            ->get();

        foreach ($overlaps as $ows) {
            $currStart = $ows->start_date ? Carbon::parse($ows->start_date) : null;
            $currEnd = $ows->end_date ? Carbon::parse($ows->end_date) : null;

            $overlapStart = $currStart ? max($currStart, $wef) : $wef;
            $overlapEnd = $currEnd ? min($currEnd, $wet) : $wet;

            if ($overlapStart->lte($overlapEnd)) {
                // We have overlap with existing assignment -> split/trim
                // Case A: Trim tail before overlap
                if ($currStart && $currStart->lt($wef)) {
                    // Keep existing record from currStart to day before wef
                    $ows->end_date = $wef->copy()->subDay();
                    $ows->save();
                } else {
                    // Remove overlap from the beginning by shifting start after wet
                    if ($currEnd && $currEnd->gt($wet)) {
                        // Create a new segment after wet
                        EmpWorkShift::create([
                            'firm_id' => $ows->firm_id,
                            'work_shift_id' => $ows->work_shift_id,
                            'work_shifts_algo_id' => $ows->work_shifts_algo_id,
                            'employee_id' => $ows->employee_id,
                            'start_date' => $wet->copy()->addDay(),
                            'end_date' => $currEnd,
                        ]);
                        // Adjust current to end before allocation
                        $ows->end_date = $wef->copy()->subDay();
                        $ows->save();
                    } else {
                        // Full overlap, delete the existing assignment
                        $ows->delete();
                    }
                }
            }
        }

        // Finally, create the target assignment segment for requested period
        EmpWorkShift::create([
            'firm_id' => session('firm_id'),
            'work_shift_id' => $workShiftId,
            'work_shifts_algo_id' => $algoId,
            'employee_id' => $employeeId,
            'start_date' => $wef,
            'end_date' => $wet,
        ]);
    }

    public function toggleColumn(string $field)
    {
        if (in_array($field, $this->visibleFields)) {
            $this->visibleFields = array_filter(
                $this->visibleFields,
                fn($f) => $f !== $field
            );
        } else {
            $this->visibleFields[] = $field;
        }
    }

    public function toggleFilterColumn(string $field)
    {
        if (in_array($field, $this->visibleFilterFields)) {
            $this->visibleFilterFields = array_filter(
                $this->visibleFilterFields,
                fn($f) => $f !== $field
            );
        } else {
            $this->visibleFilterFields[] = $field;
        }
    }

    public function handleAccordionClick($workShiftId)
    {
        $this->selectedShiftId = $workShiftId;
//        $this->loadWorkShiftsAlgos();
    }

    public function configureWeekOffPattern($algoId)
    {
        // Add your week off pattern configuration logic here
        $this->dispatch('openWeekOffPatternConfig', algoId: $algoId);
    }

    public function toggleAlgoStatus($algoId)
    {
        $algo = WorkShiftsAlgo::find($algoId);
        if ($algo) {
            $algo->is_inactive = !$algo->is_inactive;
            $algo->save();
        }
    }

    protected function refreshAlgoStatuses()
    {
        if ($this->selectedShiftId) {
            $algos = WorkShiftsAlgo::query()
                ->where('firm_id', session('firm_id'))
                ->where('work_shift_id', $this->selectedShiftId)
                ->when($this->sortBy, fn($query) => $query->orderBy($this->sortBy, $this->sortDirection))
                ->get();
                
            $this->workShiftsAlgo = $algos->toArray();
        }
    }

    #[\Livewire\Attributes\Computed]
    public function algo()
    {
        if (!$this->selectedShiftId) {
            return null;
        }

        return WorkShift::with(['work_shifts_algos' => function ($query) {
            $query->where('firm_id', session('firm_id'))
                ->when($this->sortBy, fn($q) => $q->orderBy($this->sortBy, $this->sortDirection));
        }])->find($this->selectedShiftId);
    }

    public function editAlgo($id)
    {
        // Add your algorithm edit logic here
    }

    public function deleteAlgo($id)
    {
        $algo = WorkShiftsAlgo::findOrFail($id);
        $algo->delete();

        Flux::toast(
            variant: 'success',
            heading: 'Algorithm Deleted.',
            text: 'Work Shift Algorithm has been deleted successfully',
        );
    }

    public function getBatchStatus($algoId)
    {
        // Add your batch status logic here
        return null;
    }

    public function syncWorkShiftDays($algoId)
    {
        // Add your sync logic here
    }

    public function rollbackSync($algoId)
    {
        // Add your rollback logic here
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/WorkShifts/blades/work-shifts.blade.php'));
    }
}