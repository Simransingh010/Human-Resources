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

    public function mount($batchId = null)
    {
        $this->batchId = $batchId;
        if ($this->batchId) {
            $this->batch = Batch::with('user')->findOrFail($this->batchId);
        }
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

    protected function getLeaveData($newData)
    {
        if (!$newData)
            return null;

        $data = json_decode($newData, true);
        if (!$data)
            return null;

        $employee = Employee::find($data['employee_id']);
        $leaveType = LeaveType::find($data['leave_type_id']);

        return [
            'employee_name' => $employee ? $employee->fname : 'Unknown',
            'leave_type' => $leaveType ? $leaveType->leave_title : 'Unknown',
            'period_start' => \Carbon\Carbon::parse($data['period_start'])->format('Y-m-d'),
            'period_end' => \Carbon\Carbon::parse($data['period_end'])->format('Y-m-d'),
            'allocated_days' => $data['allocated_days'] ?? 0,
            'balance' => $data['balance'] ?? 0
        ];
    }

    #[\Livewire\Attributes\Computed]
    public function batchItems()
    {
        return BatchItem::query()
            ->when($this->batchId, fn($query) => $query->where('batch_id', $this->batchId))
            ->where('model_type', 'App\Models\Hrms\EmpLeaveBalance')
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Leave/blades/batch-items.blade.php'));
    }
}