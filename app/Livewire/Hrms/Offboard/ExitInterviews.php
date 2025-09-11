<?php

namespace App\Livewire\Hrms\Offboard;

use App\Models\Hrms\ExitInterview;
use App\Models\Hrms\EmployeeExit;
use App\Models\Hrms\Employee;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;
use Flux;

class ExitInterviews extends Component
{
    use WithPagination;

    public $perPage = 10;
    public $sortBy = 'interview_date';
    public $sortDirection = 'desc';
    public $isEditing = false;

    public array $fieldConfig = [
        'exit_id' => ['label' => 'Exit', 'type' => 'select', 'listKey' => 'exits'],
        'interviewer_id' => ['label' => 'Interviewer', 'type' => 'select', 'listKey' => 'employees'],
        'interview_date' => ['label' => 'Interview Date', 'type' => 'date'],
        'feedback_rating' => ['label' => 'Rating', 'type' => 'number'],
        'interview_notes' => ['label' => 'Notes', 'type' => 'textarea'],
    ];

    public array $filterFields = [
        'exit_id' => ['label' => 'Exit', 'type' => 'select', 'listKey' => 'exits'],
        'interviewer_id' => ['label' => 'Interviewer', 'type' => 'select', 'listKey' => 'employees'],
    ];

    public array $listsForFields = [];
    public array $filters = [];
    public array $visibleFields = [];
    public array $visibleFilterFields = [];

    public $formData = [
        'id' => null,
        'firm_id' => null,
        'exit_id' => '',
        'interviewer_id' => '',
        'interview_date' => '',
        'interview_notes' => '',
        'feedback_rating' => '',
    ];

    public function mount(): void
    {
        $this->initListsForFields();
        $this->visibleFields = ['exit_id', 'interviewer_id', 'interview_date', 'feedback_rating'];
        $this->visibleFilterFields = ['exit_id', 'interviewer_id'];
        $this->filters = array_fill_keys(array_keys($this->filterFields), '');
        if (empty($this->formData['interview_date'])) {
            $this->formData['interview_date'] = date('Y-m-d');
        }
    }

    protected function initListsForFields(): void
    {
        $firmId = Session::get('firm_id');
        $cacheKey = "exit_interviews_lists_{$firmId}";

        $this->listsForFields = Cache::remember($cacheKey, 300, function () use ($firmId) {
            return [
                'exits' => EmployeeExit::where('firm_id', $firmId)
                    ->with('employee')
                    ->orderByDesc('id')
                    ->get()
                    ->map(function ($exit) {
                        $label = "Exit #{$exit->id}";
                        if ($exit->employee) {
                            $label .= " - {$exit->employee->fname} {$exit->employee->lname}";
                        }
                        return [
                            'id' => $exit->id,
                            'label' => $label,
                            'employee_id' => $exit->employee_id,
                        ];
                    })
                    ->toArray(),
                'employees' => Employee::where('firm_id', $firmId)
                    ->where(function ($q) {
                        $q->whereNull('is_inactive')->orWhere('is_inactive', false);
                    })
                    ->orderBy('fname')
                    ->get()
                    ->mapWithKeys(function ($employee) {
                        $name = trim("{$employee->fname} {$employee->lname}") ?: "Employee #{$employee->id}";
                        return [$employee->id => $name];
                    })
                    ->toArray(),
            ];
        });
    }

    public function applyFilters(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->filters = array_fill_keys(array_keys($this->filterFields), '');
        $this->resetPage();
    }

    public function toggleColumn(string $field): void
    {
        if (in_array($field, $this->visibleFields)) {
            $this->visibleFields = array_filter($this->visibleFields, fn ($f) => $f !== $field);
        } else {
            $this->visibleFields[] = $field;
        }
    }

    public function toggleFilterColumn(string $field): void
    {
        if (in_array($field, $this->visibleFilterFields)) {
            $this->visibleFilterFields = array_filter($this->visibleFilterFields, fn ($f) => $f !== $field);
        } else {
            $this->visibleFilterFields[] = $field;
        }
    }

    #[Computed]
    public function list()
    {
        $firmId = Session::get('firm_id');

        return ExitInterview::query()
            ->with(['exit.employee', 'interviewer'])
            ->where('firm_id', $firmId)
            ->when($this->filters['exit_id'], fn ($q, $v) => $q->where('exit_id', $v))
            ->when($this->filters['interviewer_id'], fn ($q, $v) => $q->where('interviewer_id', $v))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);
    }

    protected function rules(): array
    {
        return [
            'formData.exit_id' => 'required|exists:employee_exits,id',
            'formData.interviewer_id' => 'required|exists:employees,id',
            'formData.interview_date' => 'required|date',
            'formData.feedback_rating' => 'nullable|integer|min:1|max:5',
            'formData.interview_notes' => 'nullable|string|max:2000',
        ];
    }

    public function store(): void
    {
        $validated = $this->validate();
        $data = collect($validated['formData'])
            ->map(fn ($v) => $v === '' ? null : $v)
            ->toArray();
        $data['firm_id'] = Session::get('firm_id');

        if ($this->isEditing) {
            $record = ExitInterview::findOrFail($this->formData['id']);
            $record->update($data);
            $msg = 'Exit interview updated.';
        } else {
            ExitInterview::create($data);
            $msg = 'Exit interview scheduled.';
        }

        $this->resetForm();
        $this->modal('mdl-exit-interview')->close();
        Flux::toast(variant: 'success', heading: 'Saved', text: $msg);
    }

    public function edit($id): void
    {
        $this->isEditing = true;
        $record = ExitInterview::findOrFail($id);
        $this->formData = $record->toArray();
        $this->modal('mdl-exit-interview')->show();
    }

    public function delete($id): void
    {
        $record = ExitInterview::findOrFail($id);
        $record->delete();
        Flux::toast(variant: 'success', heading: 'Deleted', text: 'Exit interview removed.');
    }

    public function resetForm(): void
    {
        $this->reset(['formData']);
        $this->isEditing = false;
        $this->formData['interview_date'] = date('Y-m-d');
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Offboard/blades/exit-interviews.blade.php'));
    }
}


