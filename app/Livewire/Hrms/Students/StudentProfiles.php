<?php

namespace App\Livewire\Hrms\Students;

use App\Models\Hrms\Employee;
use App\Models\Hrms\Student;
use App\Models\Hrms\StudentEducationDetail;
use App\Models\Hrms\StudentPersonalDetail;
use App\Models\Hrms\StudyCentre;
use App\Models\Hrms\StudyGroup;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class StudentProfiles extends Component
{
	use WithPagination;

	public int $perPage = 10;
	public string $labelHeader = 'Student';
	public array $labelFields = ['fname', 'lname'];

	public array $fieldConfig = [
		'email' => ['label' => 'Email', 'type' => 'text', 'source' => 'student'],
		'phone' => ['label' => 'Phone', 'type' => 'text', 'source' => 'student'],
		'study_centre_id' => ['label' => 'Study Centre', 'type' => 'select', 'source' => 'student', 'listKey' => 'studyCentres'],
		'is_inactive' => ['label' => 'Status', 'type' => 'select', 'source' => 'student', 'options' => ['0' => 'Active', '1' => 'Inactive']],

		'student_code' => ['label' => 'Student Code', 'type' => 'text', 'source' => 'education'],
		'doh' => ['label' => 'Date of Hire', 'type' => 'date', 'source' => 'education'],
		'doe' => ['label' => 'Date of Exit', 'type' => 'date', 'source' => 'education'],
		'reporting_coach_id' => ['label' => 'Coach', 'type' => 'select', 'source' => 'education', 'listKey' => 'coaches'],

		'gender' => ['label' => 'Gender', 'type' => 'select', 'source' => 'personal', 'options' => [
			'male' => 'Male',
			'female' => 'Female',
			'other' => 'Other',
		]],
		'fathername' => ['label' => 'Father Name', 'type' => 'text', 'source' => 'personal'],
		'mothername' => ['label' => 'Mother Name', 'type' => 'text', 'source' => 'personal'],
		'mobile_number' => ['label' => 'Guardian Mobile', 'type' => 'text', 'source' => 'personal'],
		'dob' => ['label' => 'Date of Birth', 'type' => 'date', 'source' => 'personal'],
		'admission_date' => ['label' => 'Admission Date', 'type' => 'date', 'source' => 'personal'],

		'study_group_ids' => ['label' => 'Study Groups', 'type' => 'multiselect', 'source' => 'groups', 'listKey' => 'studyGroups'],
	];

	public array $filterFields = [
		'fname' => ['label' => 'First Name', 'type' => 'text', 'source' => 'student'],
		'student_code' => ['label' => 'Student Code', 'type' => 'text', 'source' => 'education'],
		'study_centre_id' => ['label' => 'Study Centre', 'type' => 'select', 'source' => 'student', 'listKey' => 'studyCentres'],
		'reporting_coach_id' => ['label' => 'Coach', 'type' => 'select', 'source' => 'education', 'listKey' => 'coaches'],
		'study_group_id' => ['label' => 'Study Group', 'type' => 'select', 'source' => 'groups', 'listKey' => 'studyGroups'],
		'is_inactive' => ['label' => 'Status', 'type' => 'select', 'source' => 'student', 'options' => ['' => 'All', '0' => 'Active', '1' => 'Inactive']],
	];

	public array $listsForFields = [];
	public array $bulkupdate = [];
	public array $filters = [];
	public array $visibleFields = [];
	public array $visibleFilterFields = [];

	public function mount(): void
	{
		$firmId = $this->currentFirmId();

		$this->listsForFields = [
			'studyCentres' => StudyCentre::where('firm_id', $firmId)
				->where('is_inactive', false)
				->orderBy('name')
				->pluck('name', 'id')
				->toArray(),
			'coaches' => Employee::where('firm_id', $firmId)
				->where('is_inactive', false)
				->orderBy('fname')
				->get()
				->mapWithKeys(fn($coach) => [$coach->id => trim($coach->fname . ' ' . $coach->lname)])
				->toArray(),
			'studyGroups' => StudyGroup::where('firm_id', $firmId)
				->orderBy('name')
				->pluck('name', 'id')
				->toArray(),
		];

		// Default visible fields - show only essential columns
		$this->visibleFields = [
			'student_code',
			'study_centre_id',
			'reporting_coach_id',
			'email',
			'phone',
		];
		
		$this->visibleFilterFields = array_keys($this->filterFields);
		$this->filters = array_fill_keys(array_keys($this->filterFields), '');
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
		if (in_array($field, $this->visibleFields, true)) {
			$this->visibleFields = array_values(array_filter(
				$this->visibleFields,
				fn($visibleField) => $visibleField !== $field
			));
			return;
		}

		$this->visibleFields[] = $field;
	}

	public function toggleFilterColumn(string $field): void
	{
		if (in_array($field, $this->visibleFilterFields, true)) {
			$this->visibleFilterFields = array_values(array_filter(
				$this->visibleFilterFields,
				fn($visibleField) => $visibleField !== $field
			));
			return;
		}

		$this->visibleFilterFields[] = $field;
	}

	#[Computed]
	public function list()
	{
		$firmId = $this->currentFirmId();

		$query = Student::with(['student_personal_detail', 'student_education_detail', 'study_groups'])
			->where('firm_id', $firmId);

		foreach ($this->filterFields as $field => $config) {
			$value = $this->filters[$field] ?? null;
			if ($value === '' || $value === null) {
				continue;
			}

			switch ($config['source']) {
				case 'student':
					$this->applyStudentFilter($query, $field, $config, $value);
					break;
				case 'personal':
					$query->whereHas('student_personal_detail', function ($personalQuery) use ($field, $config, $value) {
						$this->applyRelationFilter($personalQuery, $field, $config, $value);
					});
					break;
				case 'education':
					$query->whereHas('student_education_detail', function ($educationQuery) use ($field, $config, $value) {
						$this->applyRelationFilter($educationQuery, $field, $config, $value);
					});
					break;
				case 'groups':
					$query->whereHas('study_groups', function ($groupQuery) use ($value) {
						$groupQuery->where('study_groups.id', $value);
					});
					break;
			}
		}

		$students = $query->paginate($this->perPage);

		foreach ($students as $student) {
			foreach ($this->fieldConfig as $field => $config) {
				$this->bulkupdate[$student->id][$field] = $this->extractFieldValue($student, $field, $config);
			}
		}

		return $students;
	}

	public function triggerUpdate(int $studentId, string $field): void
	{
		if (! isset($this->fieldConfig[$field])) {
			return;
		}

		$config = $this->fieldConfig[$field];
		$value = $this->bulkupdate[$studentId][$field] ?? null;

		switch ($config['source']) {
			case 'student':
				$this->updateStudentRecord($studentId, $field, $value);
				break;
			case 'personal':
				$this->updatePersonalRecord($studentId, $field, $value);
				break;
			case 'education':
				$this->updateEducationRecord($studentId, $field, $value);
				break;
			case 'groups':
				$this->updateGroupAssignments($studentId, (array) ($value ?? []));
				break;
		}
	}

	public function render()
	{
		return view('livewire.hrms.students.student-profiles');
	}

	protected function currentFirmId(): int
	{
		$firmId = session('firm_id') ?? auth()?->user()?->firm_id;
		session()->put('firm_id', $firmId);

		return (int) $firmId;
	}

	protected function applyStudentFilter($query, string $field, array $config, mixed $value): void
	{
		if (($config['type'] ?? 'text') === 'select' && isset($config['options'])) {
			if ($value === '') {
				return;
			}
			$query->where($field, $value);
			return;
		}

		if (($config['type'] ?? 'text') === 'select') {
			$query->where($field, $value);
			return;
		}

		$query->where($field, 'like', "%{$value}%");
	}

	protected function applyRelationFilter($relationQuery, string $field, array $config, mixed $value): void
	{
		if (($config['type'] ?? 'text') === 'select') {
			$relationQuery->where($field, $value);
			return;
		}

		if (($config['type'] ?? 'text') === 'date') {
			$relationQuery->whereDate($field, $value);
			return;
		}

		$relationQuery->where($field, 'like', "%{$value}%");
	}

	protected function extractFieldValue(Student $student, string $field, array $config)
	{
		return match ($config['source']) {
			'student' => $student->{$field},
			'personal' => optional($student->student_personal_detail)->{$field},
			'education' => optional($student->student_education_detail)->{$field},
			'groups' => $student->study_groups->pluck('id')->toArray(),
			default => null,
		};
	}

	protected function updateStudentRecord(int $studentId, string $field, mixed $value): void
	{
		$value = $this->sanitizeValue($field, $value);
		Student::whereKey($studentId)->update([$field => $value]);
	}

	protected function updatePersonalRecord(int $studentId, string $field, mixed $value): void
	{
		$value = $this->sanitizeValue($field, $value);

		StudentPersonalDetail::updateOrCreate(
			['student_id' => $studentId],
			[$field => $value]
		);
	}

	protected function updateEducationRecord(int $studentId, string $field, mixed $value): void
	{
		$value = $this->sanitizeValue($field, $value);

		StudentEducationDetail::updateOrCreate(
			['student_id' => $studentId],
			[$field => $value]
		);
	}

	protected function updateGroupAssignments(int $studentId, array $groupIds): void
	{
		$student = Student::find($studentId);
		if (! $student) {
			return;
		}

		$student->study_groups()->sync(array_filter($groupIds));
	}

	protected function sanitizeValue(string $field, mixed $value): mixed
	{
		if ($value === '' || $value === null) {
			return null;
		}

		if ($field === 'is_inactive') {
			return (bool) (int) $value;
		}

		return $value;
	}
}


