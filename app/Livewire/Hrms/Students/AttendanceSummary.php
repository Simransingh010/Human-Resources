<?php

namespace App\Livewire\Hrms\Students;

use App\Livewire\Hrms\Students\Exports\AttendanceSummaryExport;
use App\Models\Hrms\Student;
use App\Models\Hrms\StudyCentre;
use App\Models\Hrms\StudyGroup;
use App\Models\Hrms\StudentAttendance;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

class AttendanceSummary extends Component
{
	public array $filters = [
		'date_range' => [
			'start' => null,
			'end' => null,
		],
		'student_ids' => [],
		'study_centre_ids' => [],
		'study_group_ids' => [],
		'status_codes' => [],
		'search' => '',
	];

	public array $listsForFields = [
		'students' => [],
		'studyCentres' => [],
		'studyGroups' => [],
		'statuses' => [],
	];

	public function mount(): void
	{
		$this->filters['date_range']['start'] = now()->startOfMonth()->format('Y-m-d');
		$this->filters['date_range']['end'] = now()->endOfMonth()->format('Y-m-d');
		$this->initListsForFields();
	}

	protected function initListsForFields(): void
	{
		$firmId = session('firm_id');

		$this->listsForFields['students'] = Student::where('firm_id', $firmId)
			->orderBy('fname')
			->get()
			->mapWithKeys(fn($student) => [$student->id => trim("{$student->fname} {$student->lname}")])
			->toArray();

		$this->listsForFields['studyCentres'] = StudyCentre::where('firm_id', $firmId)
			->orderBy('name')
			->pluck('name', 'id')
			->toArray();

		$this->listsForFields['studyGroups'] = StudyGroup::where('firm_id', $firmId)
			->orderBy('name')
			->pluck('name', 'id')
			->toArray();

		$this->listsForFields['statuses'] = StudentAttendance::ATTENDANCE_STATUS_MAIN_SELECT;
	}

	public function applyFilters(): void
	{
		$this->resetErrorBag();
	}

	public function resetFilters(): void
	{
		$this->filters = [
			'date_range' => [
				'start' => now()->startOfMonth()->format('Y-m-d'),
				'end' => now()->endOfMonth()->format('Y-m-d'),
			],
			'student_ids' => [],
			'study_centre_ids' => [],
			'study_group_ids' => [],
			'status_codes' => [],
			'search' => '',
		];
	}

	public function export()
	{
		$this->validate([
			'filters.date_range.start' => 'required|date',
			'filters.date_range.end' => 'required|date|after_or_equal:filters.date_range.start',
		]);

		return Excel::download(
			new AttendanceSummaryExport($this->filters),
			'student-attendance-summary-' . now()->format('Ymd_His') . '.xlsx'
		);
	}

	#[Computed]
	public function summaryCards(): array
	{
		$rows = $this->reportRows;
		$totalStudents = $rows->count();

		$totals = [
			'present' => $rows->sum(fn($row) => $row['stats']['present']),
			'absent' => $rows->sum(fn($row) => $row['stats']['absent']),
			'leave' => $rows->sum(fn($row) => $row['stats']['leave']),
			'not_marked' => $rows->sum(fn($row) => $row['stats']['not_marked']),
			'late' => $rows->sum(fn($row) => $row['stats']['late']),
			'weekoff' => $rows->sum(fn($row) => $row['stats']['week_off']),
		];

		return [
			[
				'label' => 'Students in report',
				'value' => number_format($totalStudents),
				'hint' => 'records matching filters',
			],
			[
				'label' => 'Total Presents',
				'value' => number_format($totals['present']),
				'hint' => 'sum of present days',
			],
			[
				'label' => 'Total Absents',
				'value' => number_format($totals['absent']),
				'hint' => 'sum of absent days',
			],
			[
				'label' => 'Late Marks',
				'value' => number_format($totals['late']),
				'hint' => 'late/partial logs',
			],
			[
				'label' => 'Leaves Approved',
				'value' => number_format($totals['leave']),
				'hint' => 'approved leave statuses',
			],
			[
				'label' => 'Week Offs',
				'value' => number_format($totals['weekoff']),
				'hint' => 'scheduled weekly offs',
			],
			[
				'label' => 'Unmarked Entries',
				'value' => number_format($totals['not_marked']),
				'hint' => 'attendance pending verification',
			],
		];
	}

	#[Computed]
	public function reportRows(): Collection
	{
		[$start, $end, $dates] = $this->resolveDateRange();
		if (! $start || ! $end) {
			return collect();
		}

		$firmId = session('firm_id');
		$students = Student::query()
			->with([
				'study_centre:id,name',
				'study_groups:id,name',
				'student_attendances' => function ($query) use ($start, $end) {
					$query->whereBetween('attendance_date', [$start, $end])
						->orderBy('attendance_date')
						->with(['student_punches' => fn($punches) => $punches->orderBy('punch_datetime')]);
				},
			])
			->where('firm_id', $firmId)
			->when(! empty($this->filters['student_ids']), fn($query) => $query->whereIn('id', $this->filters['student_ids']))
			->when(! empty($this->filters['study_centre_ids']), function ($query) {
				$query->whereIn('study_centre_id', $this->filters['study_centre_ids']);
			})
			->when(! empty($this->filters['study_group_ids']), function ($query) {
				$query->whereHas('study_groups', fn($groupQuery) => $groupQuery->whereIn('study_groups.id', $this->filters['study_group_ids']));
			})
			->when(! empty($this->filters['status_codes']), function ($query) use ($start, $end) {
				$query->whereHas('student_attendances', function ($attendanceQuery) use ($start, $end) {
					$attendanceQuery->whereBetween('attendance_date', [$start, $end])
						->whereIn('attendance_status_main', $this->filters['status_codes']);
				});
			})
			->when($this->filters['search'], function ($query, $search) {
				$query->where(function ($subQuery) use ($search) {
					$subQuery->where('fname', 'like', "%{$search}%")
						->orWhere('lname', 'like', "%{$search}%")
						->orWhere('email', 'like', "%{$search}%")
						->orWhere('phone', 'like', "%{$search}%");
				});
			})
			->get();

		$rows = [];
		foreach ($students as $student) {
			$attendanceByDate = $student->student_attendances
				->keyBy(fn($attendance) => $attendance->attendance_date->format('Y-m-d'));

			$statusCounts = array_fill_keys(array_keys(StudentAttendance::ATTENDANCE_STATUS_MAIN_SELECT), 0);
			$statusCounts['NM'] = $statusCounts['NM'] ?? 0; // ensure NM exists

			$timeline = [];
			foreach ($dates as $date) {
				$attendance = $attendanceByDate[$date->format('Y-m-d')] ?? null;
				$statusCode = $attendance?->attendance_status_main ?? 'NM';
				if (isset($statusCounts[$statusCode])) {
					$statusCounts[$statusCode]++;
				} else {
					$statusCounts[$statusCode] = 1;
				}

				$firstPunch = $attendance?->student_punches->first()?->punch_datetime;
				$lastPunch = $attendance?->student_punches->last()?->punch_datetime;

				$timeline[] = [
					'date_label' => $date->format('d M, D'),
					'status' => $statusCode,
					'status_label' => StudentAttendance::ATTENDANCE_STATUS_MAIN_SELECT[$statusCode] ?? 'Not Marked',
					'first_punch' => $firstPunch ? $firstPunch->format('H:i') : '—',
					'last_punch' => $lastPunch ? $lastPunch->format('H:i') : '—',
					'remarks' => $attendance?->remarks,
				];
			}

			$rows[] = [
				'student_id' => $student->id,
				'student_name' => trim("{$student->fname} {$student->lname}"),
				'study_centre' => $student->study_centre->name ?? 'Unassigned',
				'study_groups' => $student->study_groups->pluck('name')->implode(', ') ?: '—',
				'email' => $student->email,
				'phone' => $student->phone,
				'stats' => [
					'present' => $statusCounts['P'] ?? 0,
					'absent' => $statusCounts['A'] ?? 0,
					'leave' => $statusCounts['L'] ?? 0,
					'week_off' => $statusCounts['W'] ?? 0,
					'half_day' => $statusCounts['HD'] ?? 0,
					'late' => $statusCounts['LM'] ?? 0,
					'not_marked' => $statusCounts['NM'] ?? 0,
				],
				'timeline' => $timeline,
			];
		}

		return collect($rows);
	}

	protected function resolveDateRange(): array
	{
		try {
			$start = isset($this->filters['date_range']['start'])
				? Carbon::parse($this->filters['date_range']['start'])->startOfDay()
				: now()->startOfMonth();

			$end = isset($this->filters['date_range']['end'])
				? Carbon::parse($this->filters['date_range']['end'])->endOfDay()
				: now()->endOfMonth();

			if ($end->lt($start)) {
				[$start, $end] = [$end->copy(), $start->copy()];
			}

			$dates = new \Carbon\CarbonPeriod($start, '1 day', $end);

			return [$start, $end, iterator_to_array($dates)];
		} catch (\Throwable $e) {
			return [null, null, []];
		}
	}

	public function render()
	{
		return view()->file(
			resource_path('views/livewire/hrms/students/attendance-summary.blade.php'),
			[
				'reportRows' => $this->reportRows,
				'summaryCards' => $this->summaryCards,
			]
		);
	}
}


