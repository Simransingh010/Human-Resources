<?php

namespace App\Livewire\Hrms\Leave;

use App\Models\BatchItem;
use App\Models\Batch;
use App\Models\Hrms\Employee;
use App\Models\Hrms\LeaveType;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Session;
use Flux;

class BatchItems extends Component
{
    use WithPagination;

    public $batchId = null;
    public $batch = null;
    public $sortBy = 'id';
    public $sortDirection = 'asc';
    public $perPage = 10;

    // Add filter configuration
    public array $filterFields = [
        'employee_name' => ['label' => 'Employee', 'type' => 'text'],
        'leave_type' => ['label' => 'Leave Type', 'type' => 'select', 'listKey' => 'leaveTypeList'],
        'operation' => ['label' => 'Operation', 'type' => 'select', 'listKey' => 'operationList'],
        'period_start' => ['label' => 'From Date', 'type' => 'date'],
        'period_end' => ['label' => 'To Date', 'type' => 'date'],
    ];

    public array $visibleFilterFields = [];
    public array $filters = [];
    public array $listsForFields = [];

    public function mount($batchId = null)
    {
        $this->batchId = $batchId;
        if ($this->batchId) {
            $this->batch = Batch::with('user')->findOrFail($this->batchId);
        }

        // Initialize visible filters
        $this->visibleFilterFields = ['employee_name', 'leave_type', 'operation'];

        // Initialize filters with empty values
        $this->filters = array_fill_keys(array_keys($this->filterFields), '');

        // Initialize dropdown lists
        $this->listsForFields = [
            'leaveTypeList' => LeaveType::where('firm_id', session('firm_id'))->pluck('leave_title', 'id')->toArray(),
            'operationList' => ['insert' => 'Insert', 'update' => 'Update', 'delete' => 'Delete']
        ];
    }

    public function sort($column)
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
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

    public function clearFilters()
    {
        $this->filters = array_fill_keys(array_keys($this->filterFields), '');
        $this->resetPage();
    }

    protected function getLeaveData($newData)
    {
        if (!$newData)
            return null;

        $data = json_decode($newData, true);
        if (!$data)
            return null;

        $employee = !empty($data['employee_id']) ? Employee::find($data['employee_id']) : null;
        $leaveType = !empty($data['leave_type_id']) ? LeaveType::find($data['leave_type_id']) : null;

        return [
            'employee_name' => $employee ? $employee->fname : 'Unknown',
            'leave_type' => $leaveType ? $leaveType->leave_title : 'Unknown',
            'period_start' => !empty($data['period_start']) ? \Carbon\Carbon::parse($data['period_start'])->format('Y-m-d') : null,
            'period_end' => !empty($data['period_end']) ? \Carbon\Carbon::parse($data['period_end'])->format('Y-m-d') : null,
            'allocated_days' => $data['allocated_days'] ?? 0,
            'balance' => $data['balance'] ?? 0
        ];
    }

    #[\Livewire\Attributes\Computed]
    public function batchItems()
    {
        $query = BatchItem::query()
            ->when($this->batchId, fn($query) => $query->where('batch_id', $this->batchId))
            ->where('model_type', 'App\Models\Hrms\EmpLeaveBalance');

        // Apply filters
        if (!empty($this->filters['operation'])) {
            $query->where('operation', $this->filters['operation']);
        }

        // Get all batch items first
        $batchItems = $query->orderBy($this->sortBy, $this->sortDirection)->get();

        // Filter the collection based on decoded JSON data
        $filteredItems = $batchItems->filter(function ($item) {
            $leaveData = $this->getLeaveData($item->new_data);
            if (!$leaveData)
                return true;

            // Employee name filter
            if (!empty($this->filters['employee_name'])) {
                if (
                    !str_contains(
                        strtolower($leaveData['employee_name']),
                        strtolower($this->filters['employee_name'])
                    )
                ) {
                    return false;
                }
            }

            // Leave type filter
            if (!empty($this->filters['leave_type'])) {
                if ($leaveData['leave_type'] != $this->listsForFields['leaveTypeList'][$this->filters['leave_type']]) {
                    return false;
                }
            }

            // Date filters
            if (!empty($this->filters['period_start'])) {
                if ($leaveData['period_start'] < $this->filters['period_start']) {
                    return false;
                }
            }

            if (!empty($this->filters['period_end'])) {
                if ($leaveData['period_end'] > $this->filters['period_end']) {
                    return false;
                }
            }

            return true;
        });

        // Convert filtered collection back to paginator
        $page = request()->get('page', 1);
        $items = $filteredItems->forPage($page, $this->perPage);

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $filteredItems->count(),
            $this->perPage,
            $page,
            ['path' => request()->url()]
        );
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Leave/blades/batch-items.blade.php'));
    }
}