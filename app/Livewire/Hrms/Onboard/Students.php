<?php

namespace App\Livewire\Hrms\Onboard;

use App\Models\Hrms\Student;
use App\Models\Hrms\StudyCentre;
use App\Models\Saas\Role;
use App\Models\User;
use Livewire\Component;
use Flux;
use Illuminate\Validation\Rule;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Cache;

class Students extends Component
{
    use WithPagination;
    
    public array $studentStatuses = [];
    public array $listsForFields = [];

    public $studentData = [
        'id' => null,
        'fname' => '',
        'mname' => '',
        'lname' => '',
        'email' => '',
        'phone' => '',
        'study_centre_id' => null,
    ];

    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $isEditing = false;
    public $selectedStudentId = null;
	public $viewMode = 'table';

    public array $fieldConfig = [
        'fname' => ['label' => 'First Name', 'type' => 'text'],
        'mname' => ['label' => 'Middle Name', 'type' => 'text'],
        'lname' => ['label' => 'Last Name', 'type' => 'text'],
        'email' => ['label' => 'Email', 'type' => 'email'],
        'phone' => ['label' => 'Phone', 'type' => 'text'],
        'study_centre_id' => ['label' => 'Study Centre', 'type' => 'select', 'listKey' => 'study_centres']
    ];

    public array $filterFields = [
        'fname' => ['label' => 'First Name', 'type' => 'text'],
        'lname' => ['label' => 'Last Name', 'type' => 'text'],
        'email' => ['label' => 'Email', 'type' => 'text'],
        'phone' => ['label' => 'Phone', 'type' => 'text'],
    ];

    public array $filters = [];
    public array $visibleFields = [];
    public array $visibleFilterFields = [];

    protected $listeners = [
        'student-updated' => '$refresh',
        'student-saved' => '$refresh',
        'close-modal' => 'closeModal'
    ];

    public function mount()
    {
        $this->loadStudentStatuses();
        $this->resetPage();
        $this->initListsForFields();
        $this->visibleFields = ['fname', 'lname', 'email', 'phone'];
        $this->visibleFilterFields = ['fname', 'lname', 'email', 'phone'];
        $this->filters = array_fill_keys(array_merge(array_keys($this->filterFields), ['students']), '');
		$this->viewMode = session('students_view_mode', $this->viewMode);
    }

    private function loadStudentStatuses()
    {
        $this->studentStatuses = Student::pluck('is_inactive', 'id')
            ->map(function ($status) {
                return !$status;
            })
            ->toArray();
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

    #[Computed]
    public function studentslist()
    {
        $cacheKey = 'studentslist_' . md5(json_encode($this->filters) . $this->sortBy . $this->sortDirection . session('firm_id') . request('page', 1));
        return Cache::remember($cacheKey, 60, function () {
            return Student::query()
                ->with(['study_centre', 'student_personal_detail'])
                ->when($this->sortBy, fn($query) => $query->orderBy($this->sortBy, $this->sortDirection))
                ->when($this->filters['students'], function($query, $value) {
                    $query->where(function($q) use ($value) {
                        $q->where('fname', 'like', "%{$value}%")
                          ->orWhere('mname', 'like', "%{$value}%")
                          ->orWhere('lname', 'like', "%{$value}%");
                    });
                })
                ->when($this->filters['fname'], fn($query, $value) => $query->where('fname', 'like', "%{$value}%"))
                ->when($this->filters['lname'], fn($query, $value) => $query->where('lname', 'like', "%{$value}%"))
                ->when($this->filters['email'], fn($query, $value) => $query->where('email', 'like', "%{$value}%"))
                ->when($this->filters['phone'], fn($query, $value) => $query->where('phone', 'like', "%{$value}%"))
                ->where('firm_id', session('firm_id'))
                ->paginate(12);
        });
    }

    public function getStudentStudyCentre($student)
    {
        return $student->study_centre ? $student->study_centre->name : '-';
    }

	public function updatedViewMode($value): void
	{
		session(['students_view_mode' => $value]);
	}

    public function fetchStudent($id)
    {
        $student = Student::findOrFail($id);
        $this->studentData = $student->toArray();
        $this->isEditing = true;
        $this->modal('mdl-student')->show();
    }

    public function saveStudent()
    {
        if ($this->studentData['id']) {
            $student = Student::findOrFail($this->studentData['id']);
        } else {
            $student = new Student();
        }

        $validatedData = $this->validate([
            'studentData.fname' => 'required|string|max:255',
            'studentData.mname' => 'nullable|string|max:255',
            'studentData.lname' => 'nullable|string|max:255',
            'studentData.email' => [
                'required', 'string', 'email', 'max:255',
                Rule::unique('students', 'email')->ignore($this->studentData['id']),
            ],
            'studentData.phone' => [
                'required',
                Rule::unique('students', 'phone')->ignore($this->studentData['id']),
            ],
            'studentData.study_centre_id' => 'nullable|exists:study_centres,id',
        ]);

        $validatedDataUsr = $this->validate([
            'studentData.email' => [
                'required', 'string', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($student->user_id),
            ],
            'studentData.phone' => [
                'required',
                Rule::unique('users', 'phone')->ignore($student->user_id),
            ],
        ]);

        if ($this->isEditing) {
            $student->update($validatedData['studentData']);
            $user = User::findOrFail($student->user_id);
            $user->update($validatedDataUsr['studentData']);

            $role = Role::where('name', 'student')->first();
            if ($role) {
                $user->roles()->sync([
                    $role->id => ['firm_id' => session('firm_id')]
                ]);
            }

            $toast = 'Student updated successfully.';
        } else {
            $validatedData['studentData']['firm_id'] = session('firm_id');
            $student = Student::create($validatedData['studentData']);

            $user = new User();
            $user->name = $validatedData['studentData']['fname'] . " " . $validatedData['studentData']['lname'];
            $user->password = 'iqwing@1947';
            $user->passcode = '1111';
            $user->email = $validatedDataUsr['studentData']['email'];
            $user->phone = $validatedDataUsr['studentData']['phone'];
            $user->role_main = 'L0_student';
            $user->save();

            $role = Role::where('name', 'student')->first();
            if ($role) {
                $user->roles()->sync([
                    $role->id => ['firm_id' => session('firm_id')]
                ]);
            }

            $student->user_id = $user->id;
            $student->save();

            $toast = 'Student added successfully.';
        }

        $this->resetForm();
        $this->modal('mdl-student')->close();
        Flux::toast(
            heading: 'Changes saved.',
            text: $toast,
        );
        Cache::flush();
    }

    public function closeModal($modalName)
    {
        $this->modal($modalName)->close();
    }

    protected function initListsForFields(): void
    {
        $this->listsForFields['studentlist'] = Student::where('firm_id', session('firm_id'))->pluck('fname', 'id');
        $this->listsForFields['study_centres'] = StudyCentre::where('firm_id', session('firm_id'))->pluck('name', 'id');
    }

    public function resetForm()
    {
        $this->studentData = [
            'id' => null,
            'fname' => '',
            'mname' => '',
            'lname' => '',
            'email' => '',
            'phone' => '',
            'study_centre_id' => null,
        ];
        $this->isEditing = false;
    }

    public function applyFilters()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->filters = array_fill_keys(array_merge(array_keys($this->filterFields), ['students']), '');
        $this->resetPage();
    }

    public function toggleStatus($studentId)
    {
        $student = Student::findOrFail($studentId);
        $student->is_inactive = !$student->is_inactive;
        $student->save();

        $this->studentStatuses[$studentId] = !$student->is_inactive;

        Flux::toast(
            heading: 'Status Updated',
            text: $student->is_inactive ? 'Student has been deactivated.' : 'Student has been activated.'
        );
    }

    public function deleteStudent($studentId)
    {
        $student = Student::findOrFail($studentId);
        $studentName = $student->fname . ' ' . $student->lname;

        $student->delete();

        Flux::toast(
            heading: 'Student Deleted',
            text: "Student {$studentName} has been deleted successfully."
        );
        Cache::flush();
    }

    public function showstudentModal($studentId = null)
    {
        $this->selectedStudentId = $studentId;
        $this->modal('edit-student')->show();
    }

    public function showmodal_personal_details($studentId)
    {
        $this->selectedStudentId = $studentId;
        $this->modal('add-personal-details')->show();
    }

    public function showmodal_education_details($studentId)
    {
        $this->selectedStudentId = $studentId;
        $this->modal('add-education-details')->show();
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

    public function getProfileCompletionPercentage($studentId)
    {
        $student = Student::find($studentId);
        if (!$student) return 0;

        $totalSteps = 3;
        $completedSteps = 0;

        if ($student->fname && $student->email) $completedSteps++;
        if ($student->student_personal_detail()->exists()) $completedSteps++;
        if ($student->student_education_detail()->exists()) $completedSteps++;

        return ($completedSteps / $totalSteps) * 100;
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Onboard/blades/students.blade.php'));
    }
}
