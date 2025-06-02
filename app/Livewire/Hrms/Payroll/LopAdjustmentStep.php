<?php

namespace App\Livewire\Hrms\Payroll;

use App\Models\Hrms\EmployeesSalaryDay;
use App\Models\Hrms\Employee;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Session;
use Flux;

class LopAdjustmentStep extends Component
{
    use WithPagination;

    public $perPage = 10;
    public $sortBy = 'id';
    public $sortDirection = 'desc';
    public $slotId = null;

    // Edit Modal Properties
    public $showEditModal = false;
    public $selectedRecord = null;
    public $editForm = [
        'void_days_count' => 0,
        'lop_days_count' => 0
    ];

    // Field configuration for form and table
    public array $fieldConfig = [
        'employee_name' => ['label' => 'Employee', 'type' => 'text'],
        'cycle_days' => ['label' => 'Cycle Days', 'type' => 'number'],
        'void_days_count' => ['label' => 'Void Days', 'type' => 'number'],
        'lop_days_count' => ['label' => 'LOP Days', 'type' => 'number']
    ];

    // Filter fields configuration
    public array $filterFields = [
        'employee_id' => ['label' => 'Employee', 'type' => 'select', 'listKey' => 'employees']
    ];

    public array $listsForFields = [];
    public array $filters = [];
    public array $visibleFields = [];
    public array $visibleFilterFields = [];

    public function mount($slotId)
    {
        $this->slotId = $slotId;
        $this->initListsForFields();

        // Set default visible fields
        $this->visibleFields = [
            'employee_name',
            'cycle_days',
            'void_days_count',
            'lop_days_count'
        ];

        $this->visibleFilterFields = [
            'employee_id'
        ];

        // Initialize filters
        $this->filters = array_fill_keys(array_keys($this->filterFields), '');
    }

    protected function initListsForFields(): void
    {
        // Get employees for dropdown
        $this->listsForFields['employees'] = Employee::where('firm_id', Session::get('firm_id'))
            ->where('is_inactive', false)
            ->get()
            ->mapWithKeys(function ($employee) {
                return [$employee->id => $employee->fname . ' ' . $employee->lname];
            })
            ->toArray();
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
        $query = EmployeesSalaryDay::query()
            ->where('firm_id', Session::get('firm_id'))
            ->when($this->slotId, fn($query) =>
                $query->where('payroll_slot_id', $this->slotId))
            ->when($this->filters['employee_id'], fn($query, $value) =>
                $query->where('employee_id', $value))
            ->with(['employee']);
        $paginated = $query->paginate(10);
        $paginated->getCollection()->transform(function ($record) {
            return [
                'id' => $record->id,
                'employee_id' => $record->employee_id,
                'employee_name' => $record->employee->fname . ' ' . $record->employee->lname,
                'cycle_days' => $record->cycle_days,
                'void_days_count' => $record->void_days_count,
                'lop_days_count' => $record->lop_days_count
            ];
        });
        return $paginated;
        // Convert to paginator
//        $page = $this->page ?? 1;
//        $perPage = $this->perPage;
//        $items = $records->slice(($page - 1) * $perPage, $perPage);
//
//        return new \Illuminate\Pagination\LengthAwarePaginator(
//            $items,
//            $records->count(),
//            $perPage,
//            $page,
//            ['path' => request()->url()]
//        );
    }

    public function editLopDays($id)
    {
        $record = EmployeesSalaryDay::with('employee')->find($id);
        if ($record) {
            $this->selectedRecord = [
                'id' => $record->id,
                'employee_name' => $record->employee->fname . ' ' . $record->employee->lname,
                'cycle_days' => $record->cycle_days,
                'void_days_count' => $record->void_days_count,
                'lop_days_count' => $record->lop_days_count
            ];
            $this->editForm = [
                'void_days_count' => $record->void_days_count,
                'lop_days_count' => $record->lop_days_count
            ];
            $this->showEditModal = true;
        }
    }

    public function updateLopDays()
    {
        $this->validate([
            'editForm.void_days_count' => 'required|integer|min:0|max:' . $this->selectedRecord['cycle_days'],
            'editForm.lop_days_count' => 'required|integer|min:0|max:' . $this->selectedRecord['cycle_days'],
        ]);

        try {
            $record = EmployeesSalaryDay::find($this->selectedRecord['id']);
            if ($record) {
                $record->update([
                    'void_days_count' => $this->editForm['void_days_count'],
                    'lop_days_count' => $this->editForm['lop_days_count']
                ]);

                Flux::toast(
                    variant: 'success',
                    heading: 'Success',
                    text: 'LOP days updated successfully.',
                );
            }
        } catch (\Exception $e) {
            Flux::toast(
                variant: 'error',
                heading: 'Error',
                text: 'Failed to update LOP days: ' . $e->getMessage(),
            );
        }

        $this->closeEditModal();
    }

    public function closeEditModal()
    {
        $this->showEditModal = false;
        $this->selectedRecord = null;
        $this->editForm = [
            'void_days_count' => 0,
            'lop_days_count' => 0
        ];
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Payroll/blades/lop-adjustment-step.blade.php'));
    }
} 