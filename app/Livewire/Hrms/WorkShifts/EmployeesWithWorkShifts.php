<?php

namespace App\Livewire\Hrms\WorkShifts;

use App\Models\Hrms\EmpWorkShift;
use App\Models\Hrms\WorkShift;
use Livewire\Component;
use Livewire\WithPagination;
use Carbon\Carbon;
use Flux;

class EmployeesWithWorkShifts extends Component
{
    use WithPagination;

    public $sortBy = 'start_date';
    public $sortDirection = 'desc';
    public $perPage = 10;

    // Field configuration for filters and table
    public array $fieldConfig = [
        'employee_name' => ['label' => 'Employee Name', 'type' => 'text'],
        'shift_title' => ['label' => 'Work Shift', 'type' => 'text'],
        'start_date' => ['label' => 'Start Date', 'type' => 'date'],
        'end_date' => ['label' => 'End Date', 'type' => 'date'],
    ];

    public array $filterFields = [
        'search_employee' => ['label' => 'Employee', 'type' => 'text'],
        'search_shift' => ['label' => 'Work Shift', 'type' => 'text'],
        'search_date' => ['label' => 'Date', 'type' => 'date'],
        'show_active' => ['label' => 'Active Only', 'type' => 'boolean'],
    ];

    // Add filter properties
    public $filters = [
        'search_employee' => '',
        'search_shift' => '',
        'search_date' => '',
        'show_active' => true,
    ];

    public array $visibleFields = [];
    public array $visibleFilterFields = [];

    public function mount()
    {
        $this->resetPage();
        
        // Set default visible fields and filters
        $this->visibleFields = ['employee_name', 'shift_title', 'start_date', 'end_date'];
        $this->visibleFilterFields = ['search_employee', 'search_shift', 'search_date', 'show_active'];
    }

    #[\Livewire\Attributes\Computed]
    public function list()
    {
        return EmpWorkShift::query()
            ->where('firm_id', session('firm_id'))
            ->when($this->filters['search_employee'], function ($query) {
                $query->whereHas('employee', function ($q) {
                    $search = strtolower($this->filters['search_employee']);
                    $q->whereRaw('LOWER(CONCAT(fname, " ", COALESCE(mname, ""), " ", lname)) LIKE ?', ['%' . $search . '%']);
                });
            })
            ->when($this->filters['search_shift'], function ($query) {
                $query->whereHas('work_shift', function ($q) {
                    $q->where('shift_title', 'like', '%' . $this->filters['search_shift'] . '%');
                });
            })
            ->when($this->filters['search_date'], function ($query) {
                $query->where(function ($q) {
                    $q->whereDate('start_date', '<=', $this->filters['search_date'])
                      ->where(function($sq) {
                          $sq->whereDate('end_date', '>=', $this->filters['search_date'])
                             ->orWhereNull('end_date');
                      });
                });
            })
            ->when($this->filters['show_active'], function ($query) {
                $query->where(function ($q) {
                    $q->whereNull('end_date')
                      ->orWhereDate('end_date', '>=', Carbon::today());
                });
            })
            ->with(['work_shift:id,shift_title,is_inactive', 'employee:id,fname,mname,lname'])
            ->when($this->sortBy, fn($query) => $query->orderBy($this->sortBy, $this->sortDirection))
            ->paginate($this->perPage);
    }

    public function clearFilters()
    {
        $this->filters = [
            'search_employee' => '',
            'search_shift' => '',
            'search_date' => '',
            'show_active' => true,
        ];
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

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/WorkShifts/blades/employees-with-work-shifts.blade.php'));
    }
} 