<?php

namespace App\Livewire\Hrms\Attendance;

use App\Models\Hrms\EmpAttendanceStatuses;
use App\Models\Hrms\WorkShift;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Session;
use Flux;

class EmpAttendanceStatus extends Component
{
    use WithPagination;

    public $perPage = 10;
    public $sortBy = 'attendance_status_label';
    public $sortDirection = 'asc';
    public $isEditing = false;

    // Field configuration for form and table
    public array $fieldConfig = [
        'attendance_status_code' => ['label' => 'Status Code', 'type' => 'text'],
        'attendance_status_label' => ['label' => 'Status Label', 'type' => 'text'],
        'attendance_status_desc' => ['label' => 'Description', 'type' => 'textarea'],
        'paid_percent' => ['label' => 'Paid Percent', 'type' => 'number'],
        'attendance_status_main' => ['label' => 'Status Main', 'type' => 'select', 'listKey' => 'attendance_status_main'],
        'is_inactive' => ['label' => 'Inactive', 'type' => 'switch'],
        'work_shift_id' => ['label' => 'Work Shift', 'type' => 'select', 'listKey' => 'work_shifts'],
    ];

    // Filter fields configuration
    public array $filterFields = [
        'attendance_status_code' => ['label' => 'Status Code', 'type' => 'text'],
        'attendance_status_label' => ['label' => 'Status Label', 'type' => 'text'],
        'attendance_status_main' => ['label' => 'Status Main', 'type' => 'select', 'listKey' => 'attendance_status_main'],
        'is_inactive' => ['label' => 'Inactive', 'type' => 'select', 'listKey' => 'is_inactive'],
    ];

    public array $listsForFields = [];
    public array $filters = [];
    public array $visibleFields = [];
    public array $visibleFilterFields = [];

    public $formData = [
        'id' => null,
        'firm_id' => null,
        'attendance_status_code' => '',
        'attendance_status_label' => '',
        'attendance_status_desc' => '',
        'paid_percent' => 100,
        'attendance_status_main' => '',
        'attribute_json' => null,
        'is_inactive' => false,
        'work_shift_id' => null,
    ];

    public function mount()
    {
        $this->resetPage();
        $this->listsForFields['attendance_status_main'] = EmpAttendanceStatuses::ATTENDANCE_STATUS_MAIN_OPTIONS;
        
        $this->listsForFields['is_inactive'] = [
            '0' => 'Active',
            '1' => 'Inactive',
        ];
        
        // Load work shifts
        $this->listsForFields['work_shifts'] = WorkShift::where('firm_id', Session::get('firm_id'))
            ->where('is_inactive', false)
            ->pluck('shift_title', 'id')
            ->toArray();
        
        // Set default visible fields
        $this->visibleFields = ['attendance_status_code', 'attendance_status_label', 'attendance_status_main', 'paid_percent', 'is_inactive'];
        $this->visibleFilterFields = ['attendance_status_code', 'attendance_status_label', 'attendance_status_main'];
        
        // Initialize filters
        $this->filters = array_fill_keys(array_keys($this->filterFields), '');
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
        return EmpAttendanceStatuses::query()
            ->where('firm_id', Session::get('firm_id'))
            ->when($this->filters['attendance_status_code'], fn($query, $value) => 
                $query->where('attendance_status_code', 'like', "%{$value}%"))
            ->when($this->filters['attendance_status_label'], fn($query, $value) => 
                $query->where('attendance_status_label', 'like', "%{$value}%"))
            ->when($this->filters['attendance_status_main'], fn($query, $value) => 
                $query->where('attendance_status_main', $value))
            ->when($this->filters['is_inactive'], fn($query, $value) => 
                $query->where('is_inactive', $value))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);
    }

    protected function rules()
    {
        return [
            'formData.attendance_status_code' => 'required|string|max:50',
            'formData.attendance_status_label' => 'required|string|max:255',
            'formData.attendance_status_desc' => 'nullable|string',
            'formData.paid_percent' => 'required|numeric|min:0|max:100',
            'formData.attendance_status_main' => 'nullable|string',
            'formData.attribute_json' => 'nullable|string',
            'formData.is_inactive' => 'boolean',
            'formData.work_shift_id' => 'nullable|exists:work_shifts,id',
        ];
    }

    public function store()
    {
        $validatedData = $this->validate();

        $validatedData['formData'] = collect($validatedData['formData'])
            ->map(fn($val) => $val === '' ? null : $val)
            ->toArray();

        $validatedData['formData']['firm_id'] = session('firm_id');

        if ($this->isEditing) {
            $attendanceStatus = EmpAttendanceStatuses::findOrFail($this->formData['id']);
            $attendanceStatus->update($validatedData['formData']);
            $toastMsg = 'Attendance status updated successfully';
        } else {
            EmpAttendanceStatuses::create($validatedData['formData']);
            $toastMsg = 'Attendance status added successfully';
        }

        $this->resetForm();
        $this->modal('mdl-attendance-status')->close();
        Flux::toast(
            variant: 'success',
            heading: 'Changes saved.',
            text: $toastMsg,
        );
    }

    public function resetForm()
    {
        $this->reset(['formData']);
        $this->formData['paid_percent'] = 100;
        $this->formData['is_inactive'] = false;
        $this->isEditing = false;
    }

    public function edit($id)
    {
        $this->isEditing = true;
        $attendanceStatus = EmpAttendanceStatuses::findOrFail($id);
        $this->formData = $attendanceStatus->toArray();
        $this->modal('mdl-attendance-status')->show();
    }

    public function delete($id)
    {
        // Check if attendance status has related records
        $attendanceStatus = EmpAttendanceStatuses::findOrFail($id);
        
        // You can add additional checks here if needed
        // For example, check if this status is used in attendance records
        
        $attendanceStatus->delete();
        Flux::toast(
            variant: 'success',
            heading: 'Record Deleted.',
            text: 'Attendance status has been deleted successfully',
        );
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Attendance/blades/emp-attendance-status.blade.php'));
    }
}
