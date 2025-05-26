<?php

namespace App\Livewire\Hrms\Payroll;

use App\Models\Hrms\SalaryExecutionGroup;
use App\Models\Hrms\SalaryCycle;
use App\Models\Hrms\PayrollSlot;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Session;
use Flux;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SalaryExecutionGroups extends Component
{
    use WithPagination;

    public $perPage = 10;
    public $sortBy = 'title';
    public $sortDirection = 'asc';
    public $isEditing = false;
    public $selectedGroupId = null;

    // Field configuration for form and table
    public array $fieldConfig = [
        'title' => ['label' => 'Title', 'type' => 'text'],
        'description' => ['label' => 'Description', 'type' => 'textarea'],
        'salary_cycle_id' => ['label' => 'Salary Cycle', 'type' => 'select', 'listKey' => 'salary_cycles'],
        'is_inactive' => ['label' => 'Inactive', 'type' => 'switch'],
    ];

    // Filter fields configuration
    public array $filterFields = [
        'title' => ['label' => 'Title', 'type' => 'text'],
        'salary_cycle_id' => ['label' => 'Salary Cycle', 'type' => 'select', 'listKey' => 'salary_cycles'],
        'is_inactive' => ['label' => 'Status', 'type' => 'select', 'listKey' => 'statuses'],
    ];

    public array $listsForFields = [];
    public array $filters = [];
    public array $visibleFields = [];
    public array $visibleFilterFields = [];

    public $formData = [
        'id' => null,
        'firm_id' => null,
        'title' => '',
        'description' => '',
        'salary_cycle_id' => null,
        'is_inactive' => false,
    ];

    public function mount()
    {
        $this->initListsForFields();

        // Set default visible fields
        $this->visibleFields = ['title', 'salary_cycle_id',];
        $this->visibleFilterFields = ['title', 'salary_cycle_id', 'is_inactive'];

        // Initialize filters
        $this->filters = array_fill_keys(array_keys($this->filterFields), '');

        // Listen for employeesAssigned event
        $this->listeners = [
            'employeesAssigned' => 'handleEmployeesAssigned'
        ];
    }

    protected function initListsForFields(): void
    {
        // Get salary cycles for dropdown
        $this->listsForFields['salary_cycles'] = SalaryCycle::where('firm_id', Session::get('firm_id'))
            ->where('is_inactive', false)
            ->pluck('title', 'id')
            ->toArray();

        // Status options
        $this->listsForFields['statuses'] = [
            '1' => 'Inactive',
            '0' => 'Active'
        ];
    }

    public function applyFilters()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->filters = array_fill_keys(array_keys($this->filterFields), '');
        $this->resetPage();
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

    #[Computed]
    public function list()
    {
        return SalaryExecutionGroup::query()
            ->where('firm_id', Session::get('firm_id'))
            ->when($this->filters['title'], fn($query, $value) =>
                $query->where('title', 'like', "%{$value}%"))
            ->when($this->filters['salary_cycle_id'], fn($query, $value) =>
                $query->where('salary_cycle_id', $value))
            ->when($this->filters['is_inactive'] !== '', fn($query) =>
                $query->where('is_inactive', $this->filters['is_inactive']))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);
    }

    protected function rules()
    {
        return [
            'formData.title' => 'required|string|max:255',
            'formData.description' => 'nullable|string',
            'formData.salary_cycle_id' => 'nullable|integer',
            'formData.is_inactive' => 'boolean'
        ];
    }

    public function store()
    {
        $validatedData = $this->validate();

        $validatedData['formData'] = collect($validatedData['formData'])
            ->map(function ($val, $key) {
                return $val === '' ? null : $val;
            })
            ->toArray();

        $validatedData['formData']['firm_id'] = session('firm_id');

        if ($this->isEditing) {
            $executionGroup = SalaryExecutionGroup::findOrFail($this->formData['id']);
            $executionGroup->update($validatedData['formData']);
            $toastMsg = 'Salary execution group updated successfully';
        } else {
            SalaryExecutionGroup::create($validatedData['formData']);
            $toastMsg = 'Salary execution group added successfully';
        }

        $this->resetForm();
        $this->modal('mdl-salary-execution-group')->close();
        Flux::toast(
            variant: 'success',
            heading: 'Changes saved.',
            text: $toastMsg,
        );
    }

    public function resetForm()
    {
        $this->reset(['formData']);
        $this->formData['is_inactive'] = false;
        $this->isEditing = false;
    }

    public function edit($id)
    {
        $this->isEditing = true;
        $executionGroup = SalaryExecutionGroup::findOrFail($id);
        $this->formData = array_merge($this->formData, $executionGroup->toArray());
        $this->modal('mdl-salary-execution-group')->show();
    }

    public function delete($id)
    {
        // Check if execution group has related records
        $executionGroup = SalaryExecutionGroup::findOrFail($id);
        if (
            $executionGroup->employees()->count() > 0 ||
            $executionGroup->payroll_slots()->count() > 0
        ) {
            Flux::toast(
                variant: 'error',
                heading: 'Cannot Delete',
                text: 'This salary execution group has related records and cannot be deleted.',
            );
            return;
        }

        $executionGroup->delete();
        Flux::toast(
            variant: 'success',
            heading: 'Record Deleted.',
            text: 'Salary execution group has been deleted successfully',
        );
    }

    public function showEmployeeAssignments($id)
    {
        $this->selectedGroupId = $id;
        $this->modal('employee-assignments')->show();
    }

    public function syncPayrollSlots($id)
    {
        $executionGroup = SalaryExecutionGroup::findOrFail($id);
        $salaryCycle = SalaryCycle::findOrFail($executionGroup->salary_cycle_id);

        // Define the date range
        $startDate = Carbon::create(2025, 4, 1); // April 1, 2025
        $endDate = Carbon::create(2026, 3, 31); // March 31, 2026
        $currentDate = Carbon::now();

        // Get the cycle unit and calculate slots
        $cycleUnit = $salaryCycle->cycle_unit;
        $currentPeriod = Carbon::now()->startOfMonth();

        // Create slots based on cycle unit
        $currentSlotDate = $startDate;
        while ($currentSlotDate <= $endDate) {
            // Calculate slot end date based on cycle unit
            $slotEndDate = match ($cycleUnit) {
                'month' => $currentSlotDate->copy()->endOfMonth(),
                'week' => $currentSlotDate->copy()->addDays(6),
                'day' => $currentSlotDate->copy(),
                'year' => $currentSlotDate->copy()->addYear()->subDay(),
                default => $currentSlotDate->copy()->endOfMonth(),
            };

            // Determine slot status
            $slotStatus = 'PN'; // Default to Pending

            // If this slot is for the current month
            if (
                $currentSlotDate->month === $currentDate->month &&
                $currentSlotDate->year === $currentDate->year
            ) {
                $slotStatus = 'NX'; // Next Due
            }

            // Create the payroll slot
            PayrollSlot::create([
                'firm_id' => session('firm_id'),
                'salary_cycle_id' => $executionGroup->salary_cycle_id,
                'salary_execution_group_id' => $id,
                'from_date' => $currentSlotDate,
                'to_date' => $slotEndDate,
                'payroll_slot_status' => $slotStatus,
                'title' => $currentSlotDate->format('F Y') // e.g., "April 2025"
            ]);

            // Move to next period based on cycle unit
            $currentSlotDate = match ($cycleUnit) {
                'month' => $currentSlotDate->addMonth(),
                'week' => $currentSlotDate->addWeek(),
                'day' => $currentSlotDate->addDay(),
                'year' => $currentSlotDate->addYear(),
                default => $currentSlotDate->addMonth(),
            };
        }

        Flux::toast(
            variant: 'success',
            heading: 'Success',
            text: 'Payroll slots have been created successfully',
        );
    }

    public function handleEmployeesAssigned($groupId)
    {
        // Refresh the list to show updated data
        $this->list();

        // Close the employee assignments modal
        $this->modal('employee-assignments')->close();
    }

    public function rollbackPayrollSlots($id)
    {
        try {
            DB::beginTransaction();

            // Find all payroll slots for this execution group
            $slots = PayrollSlot::where('salary_execution_group_id', $id)
                ->where('payroll_slot_status', '!=', 'ST') // Don't delete started slots
                ->where('payroll_slot_status', '!=', 'CM') // Don't delete completed slots
                ->get();

            if ($slots->isEmpty()) {
                Flux::toast(
                    variant: 'error',
                    heading: 'Cannot Rollback',
                    text: 'No eligible payroll slots found for rollback. Slots that are Started or Completed cannot be rolled back.',
                );
                return;
            }

            // Delete the slots
            foreach ($slots as $slot) {
                $slot->delete();
            }

            DB::commit();

            Flux::toast(
                variant: 'success',
                heading: 'Rollback Complete',
                text: 'Payroll slots have been successfully rolled back.',
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Flux::toast(
                variant: 'error',
                heading: 'Error',
                text: 'Failed to rollback payroll slots. ' . $e->getMessage(),
            );
        }
    }

    #[Computed]
    public function getGroupPayrollStatus($groupId)
    {
        $slots = PayrollSlot::where('salary_execution_group_id', $groupId)
            ->select('payroll_slot_status')
            ->get();

        if ($slots->isEmpty()) {
            return 'NO_SLOTS';
        }

        // If any slot is in Started or Completed status, don't allow rollback
        if (
            $slots->contains('payroll_slot_status', 'ST') ||
            $slots->contains('payroll_slot_status', 'CM')
        ) {
            return 'IN_PROGRESS';
        }

        return 'CAN_ROLLBACK';
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Payroll/blades/salary-execution-groups.blade.php'));
    }
}