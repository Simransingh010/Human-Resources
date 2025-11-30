<?php

namespace App\Livewire\Hrms\Students;

use App\Models\Hrms\Employee;
use App\Models\Hrms\StudyCentre;
use App\Models\Hrms\StudyGroup;
use App\Models\Hrms\EmployeeJobProfile;
use App\Models\Hrms\Student;
use App\Models\Hrms\StudentEducationDetail;
use Flux;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class BulkStudyGroups extends Component
{
	use WithPagination;

	public int $perPage = 10;
	public string $labelHeader = 'Study Group';
	public array $labelFields = ['name'];

	public array $fieldConfig = [
		'name' => ['label' => 'Group Name', 'type' => 'text', 'source' => 'group'],
		'study_centre_id' => ['label' => 'Study Centre', 'type' => 'select', 'source' => 'group', 'listKey' => 'studyCentres'],
		'coach_id' => ['label' => 'Coach', 'type' => 'select', 'source' => 'group', 'listKey' => 'coaches'],
		'is_active' => ['label' => 'Status', 'type' => 'select', 'source' => 'group', 'options' => ['1' => 'Active', '0' => 'Inactive']],
		'student_count' => ['label' => 'Students', 'type' => 'badge', 'source' => 'computed'],
	];

	public array $filterFields = [
		'name' => ['label' => 'Group Name', 'type' => 'text', 'source' => 'group'],
		'study_centre_id' => ['label' => 'Study Centre', 'type' => 'select', 'source' => 'group', 'listKey' => 'studyCentres'],
		'coach_id' => ['label' => 'Coach', 'type' => 'select', 'source' => 'group', 'listKey' => 'coaches'],
		'is_active' => ['label' => 'Status', 'type' => 'select', 'source' => 'group', 'options' => ['' => 'All', '1' => 'Active', '0' => 'Inactive']],
	];

	public array $listsForFields = [];
	public array $bulkupdate = [];
	public array $filters = [];
	public array $visibleFields = [];
	public array $visibleFilterFields = [];

	// Modal state
	public bool $showStudentModal = false;
	public int|null $selectedGroupId = null;
	public array $availableStudents = [];
	public array $selectedStudents = [];
	public string $studentSearch = '';

	// New group form
	public array $newGroupData = [
		'name' => '',
		'study_centre_id' => '',
		'coach_id' => '',
		'is_active' => true,
	];

	public function mount(): void
	{
		$firmId = $this->currentFirmId();

		$this->listsForFields = [
			'studyCentres' => StudyCentre::where('firm_id', $firmId)
				->where('is_inactive', false)
				->orderBy('name')
				->pluck('name', 'id')
				->toArray(),
			'coaches' => Employee::with('emp_job_profile')
				->where('firm_id', $firmId)
				->where('is_inactive', false)
				->orderBy('fname')
				->get()
				->mapWithKeys(function ($coach) {
					$code = optional($coach->emp_job_profile)->employee_code ?? 'N/A';
					$name = trim($coach->fname . ' ' . $coach->lname);
					return [$coach->id => "{$code} — {$name}"];
				})
				->toArray(),
		];

		// Default visible fields
		$this->visibleFields = ['name', 'study_centre_id', 'coach_id', 'is_active', 'student_count'];
		$this->visibleFilterFields = array_keys($this->filterFields);
		$this->filters = array_fill_keys(array_keys($this->filterFields), '');

		// Sync students for all groups that have empty pivot tables
		$this->syncAllEmptyGroups();
	}

	protected function syncAllEmptyGroups(): void
	{
		$firmId = $this->currentFirmId();
		$groups = StudyGroup::withCount('students')
			->where('firm_id', $firmId)
			->where('is_active', true)
			->having('students_count', '=', 0)
			->get();

		foreach ($groups as $group) {
			if ($group->study_centre_id) {
				$this->syncStudentsFromCentre($group->id);
			}
		}
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

		$query = StudyGroup::with(['study_centre', 'coach.emp_job_profile', 'students'])
			->where('firm_id', $firmId);

		foreach ($this->filterFields as $field => $config) {
			$value = $this->filters[$field] ?? null;
			if ($value === '' || $value === null) {
				continue;
			}

			$this->applyGroupFilter($query, $field, $config, $value);
		}

		$groups = $query->paginate($this->perPage);

		foreach ($groups as $group) {
			$group->computed_student_count = $group->students->count();

			foreach ($this->fieldConfig as $field => $config) {
				$this->bulkupdate[$group->id][$field] = $this->extractFieldValue($group, $field, $config);
			}
		}

		return $groups;
	}

	public function triggerUpdate(int $groupId, string $field): void
	{
		if (! isset($this->fieldConfig[$field])) {
			return;
		}

		// Don't update computed fields
		if ($field === 'student_count') {
			return;
		}

		$config = $this->fieldConfig[$field];
		$value = $this->bulkupdate[$groupId][$field] ?? null;

		$this->updateGroupRecord($groupId, $field, $value);
	}

	public function showManageStudents(int $groupId): void
	{
		$this->selectedGroupId = $groupId;
		$this->loadAvailableStudents();
		$this->loadGroupStudents($groupId);
		$this->showStudentModal = true;
	}

	public function loadAvailableStudents(): void
	{
		$this->filterStudents();
	}

	public function loadGroupStudents(int $groupId): void
	{
		if (! $group = StudyGroup::with('students')->find($groupId)) {
			return;
		}

		if ($group->students->isEmpty()) {
			$this->syncStudentsFromCentre($groupId);
			$group->load('students');
		}

		$this->selectedStudents = $group->students->pluck('id')->map(fn($id) => (string) $id)->toArray();
	}

	public function updatedStudentSearch(): void
	{
		$this->filterStudents();
	}

	protected function filterStudents(): void
	{
		$firmId = $this->currentFirmId();
		$query = Student::query()
			->where('firm_id', $firmId)
			->where('is_inactive', false)
			->with('student_personal_detail')
			->orderBy('fname');

		if ($this->selectedGroupId) {
			$centreId = StudyGroup::whereKey($this->selectedGroupId)->value('study_centre_id');
			if ($centreId) {
				$query->whereHas('student_education_detail', fn($q) => $q->where('study_centre_id', $centreId));
			}
		}

		if ($search = $this->studentSearch) {
			$query->where(function ($subQuery) use ($search) {
				$subQuery->where('fname', 'like', "%{$search}%")
					->orWhere('lname', 'like', "%{$search}%")
					->orWhere('email', 'like', "%{$search}%")
					->orWhere('phone', 'like', "%{$search}%");
			});
		}

		$students = $query->select(['id', 'fname', 'lname', 'email', 'phone'])->get();

		$this->availableStudents = $students->map(function ($student) {
			$name = trim("{$student->fname} {$student->lname}") ?: 'Unnamed Student';
			$adhar = $student->student_personal_detail->adharno ?? null;
			$label = $adhar ? "{$name} ({$adhar})" : $name;
			$meta = array_filter([$student->email, $student->phone]);

			return [
				'id' => (string) $student->id,
				'label' => $label,
				'meta' => $meta ? implode(' • ', $meta) : '—',
			];
		})->values()->toArray();
	}

	public function selectAllStudents(): void
	{
		$ids = array_column($this->availableStudents, 'id');
		$this->selectedStudents = array_unique(array_merge(
			$this->selectedStudents,
			array_map('strval', $ids)
		));
	}

	public function deselectAllStudents(): void
	{
		$this->selectedStudents = [];
	}

	public function clearStudentSearch(): void
	{
		$this->studentSearch = '';
		$this->filterStudents();
	}

	public function saveStudentAssignments(): void
	{
		if (! $this->selectedGroupId) {
			Flux::toast(
				variant: 'error',
				heading: 'Error',
				text: 'No study group selected.',
			);
			return;
		}

		try {
			$group = StudyGroup::findOrFail($this->selectedGroupId);
			$studentIds = array_map('intval', $this->selectedStudents);
			$group->students()->sync($studentIds);

			Flux::toast(
				heading: 'Success',
				text: 'Student assignments updated successfully.',
			);

			$this->closeStudentModal();
			$this->resetPage();
		} catch (\Exception $e) {
			Flux::toast(
				variant: 'error',
				heading: 'Error',
				text: 'Failed to update student assignments: ' . $e->getMessage(),
			);
		}
	}

	public function closeStudentModal(): void
	{
		$this->showStudentModal = false;
		$this->selectedGroupId = null;
		$this->selectedStudents = [];
		$this->studentSearch = '';
		$this->availableStudents = [];
	}

	public function createGroup(): void
	{
		$this->validate([
			'newGroupData.name' => 'required|string|max:255',
			'newGroupData.study_centre_id' => 'required|exists:study_centres,id',
			'newGroupData.coach_id' => 'nullable|exists:employees,id',
			'newGroupData.is_active' => 'boolean',
		]);

		try {
			$group = StudyGroup::create([
				'firm_id' => $this->currentFirmId(),
				'name' => $this->newGroupData['name'],
				'study_centre_id' => $this->newGroupData['study_centre_id'],
				'coach_id' => $this->newGroupData['coach_id'] ?: null,
				'is_active' => (bool) ($this->newGroupData['is_active'] ?? true),
			]);

			$this->syncStudentsFromCentre($group->id);

			Flux::toast(
				heading: 'Success',
				text: 'Study group created successfully.',
			);

			$this->resetNewGroup();
		} catch (\Exception $e) {
			Flux::toast(
				variant: 'error',
				heading: 'Error',
				text: 'Failed to create study group: ' . $e->getMessage(),
			);
		}
	}

	public function resetNewGroup(): void
	{
		$this->newGroupData = [
			'name' => '',
			'study_centre_id' => '',
			'coach_id' => '',
			'is_active' => true,
		];
	}

	public function deleteGroup(int $groupId): void
	{
		try {
			$group = StudyGroup::findOrFail($groupId);
			$groupName = $group->name;
			$group->delete();

			Flux::toast(
				heading: 'Success',
				text: "Study group '{$groupName}' deleted successfully.",
			);
		} catch (\Exception $e) {
			Flux::toast(
				variant: 'error',
				heading: 'Error',
				text: 'Failed to delete study group: ' . $e->getMessage(),
			);
		}
	}

	public function render()
	{
		return view('livewire.hrms.students.bulk-study-groups');
	}

	protected function currentFirmId(): int
	{
		$firmId = session('firm_id') ?? auth()?->user()?->firm_id;
		session()->put('firm_id', $firmId);

		return (int) $firmId;
	}

	protected function applyGroupFilter($query, string $field, array $config, mixed $value): void
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

	protected function extractFieldValue(StudyGroup $group, string $field, array $config)
	{
		return match ($field) {
			'student_count' => $group->students->count(),
			'study_centre_id' => $group->study_centre?->name ?? '—',
			'coach_id' => $group->coach ? $this->formatCoachLabel($group->coach) : '—',
			'is_active' => $group->is_active ? 'Active' : 'Inactive',
			default => $group->{$field} ?? '—',
		};
	}

	protected function updateGroupRecord(int $groupId, string $field, mixed $value): void
	{
		$value = $this->sanitizeValue($field, $value);

		try {
			StudyGroup::whereKey($groupId)->update([$field => $value]);
			if ($field === 'study_centre_id') {
				$this->syncStudentsFromCentre($groupId);
				$group = StudyGroup::with('students')->find($groupId);
				$this->bulkupdate[$groupId]['student_count'] = $group ? $group->students->count() : 0;
			}

			Flux::toast(
				heading: 'Updated',
				text: 'Study group updated successfully.',
			);
		} catch (\Exception $e) {
			Flux::toast(
				variant: 'error',
				heading: 'Error',
				text: 'Failed to update: ' . $e->getMessage(),
			);
		}
	}

	protected function sanitizeValue(string $field, mixed $value): mixed
	{
		if ($value === '' || $value === null) {
			return null;
		}

		if ($field === 'is_active') {
			// Handle both string and boolean values
			if (is_string($value)) {
				return in_array($value, ['1', 'true', 'on'], true);
			}
			return (bool) $value;
		}

		return $value;
	}

	protected function formatCoachLabel(Employee $coach): string
	{
		$code = optional($coach->emp_job_profile)->employee_code ?? 'N/A';
		$name = trim($coach->fname . ' ' . $coach->lname);

		return "{$code} — {$name}";
	}

	protected function syncStudentsFromCentre(int $groupId): void
	{
		$group = StudyGroup::find($groupId);
		if (! $group || ! $group->study_centre_id) {
			return;
		}

		$studentIds = StudentEducationDetail::where('study_centre_id', $group->study_centre_id)
			->pluck('student_id')
			->filter()
			->unique()
			->toArray();

		$group->students()->sync($studentIds);
	}

	protected function getStudentCountForCentre(?int $studyCentreId): int
	{
		if (! $studyCentreId) {
			return 0;
		}

		return StudentEducationDetail::where('study_centre_id', $studyCentreId)
			->distinct('student_id')
			->count('student_id');
	}
}

