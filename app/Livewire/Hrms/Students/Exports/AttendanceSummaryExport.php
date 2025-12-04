<?php

namespace App\Livewire\Hrms\Students\Exports;

use App\Models\Hrms\Student;
use App\Models\Hrms\StudentAttendance;
use App\Models\Hrms\StudentPunch;
use App\Models\Saas\Firm;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class AttendanceSummaryExport implements FromCollection, WithHeadings, WithMapping, WithEvents, WithCustomStartCell
{
	protected array $filters;
	protected Carbon $start;
	protected Carbon $end;
	protected array $dates = [];
	protected Collection $rows;
	protected string $firmName = '';
	protected ?int $firmId;

	public function __construct(array $filters, ?int $firmId = null)
	{
		$this->filters = $filters;
		$this->firmId = $firmId ?? session('firm_id');
		$this->resolveDateRange();
		$this->firmName = optional(Firm::find($this->firmId))->name ?? '—';

		Log::info('AttendanceSummaryExport::__construct', [
			'firmId' => $this->firmId,
			'firmName' => $this->firmName,
			'filters' => $this->filters,
			'start' => $this->start->toDateString(),
			'end' => $this->end->toDateString(),
			'dates_count' => count($this->dates),
		]);
	}

	public function collection(): Collection
	{
		return $this->rows = $this->buildRows();
	}

	public function headings(): array
	{
		$headers = [
			'Student Code',
			'Student Name',
			'Study Centre',
			'Study Groups',
			'Email',
			'Phone',
		];

		foreach ($this->dates as $date) {
			$headers[] = '[' . $date->format('D') . '] ' . $date->format('d M');
		}

		return array_merge($headers, [
			'Total Present',
			'Total Absent',
			'Total Leave',
			'Total Week Off',
			'Total Half Day',
			'Total Late',
			'Total Not Marked',
			'Total Days',
		]);
	}

	public function map($row): array
	{
		$exportRow = [
			$row['student_code'],
			$row['student_name'],
			$row['study_centre'],
			$row['study_groups'],
			$row['email'],
			$row['phone'],
		];

		foreach ($row['timeline'] as $day) {
			$cell = $day['status_label'];
			if ($day['first_punch'] || $day['last_punch']) {
				$cell .= "\nIN: " . ($day['first_punch'] ?? '—');
				$cell .= "\nOUT: " . ($day['last_punch'] ?? '—');
			}
			$exportRow[] = $cell;
		}

		return array_merge($exportRow, [
			$row['stats']['present'],
			$row['stats']['absent'],
			$row['stats']['leave'],
			$row['stats']['week_off'],
			$row['stats']['half_day'],
			$row['stats']['late'],
			$row['stats']['not_marked'],
			count($row['timeline']),
		]);
	}

	public function startCell(): string
	{
		return 'A4';
	}

	public function registerEvents(): array
	{
		return [
			AfterSheet::class => function (AfterSheet $event) {
				$sheet = $event->sheet->getDelegate();

				$totalColumns = 6 + count($this->dates) + 8; // base columns + day columns + summary
				$endColumn = Coordinate::stringFromColumnIndex($totalColumns);

				$title = strtoupper($this->firmName) . ' — STUDENT ATTENDANCE SUMMARY';
				$period = 'Period: ' . $this->start->format('d M Y') . ' to ' . $this->end->format('d M Y');

				$sheet->setCellValue('A1', $title);
				$sheet->mergeCells("A1:{$endColumn}1");
				$sheet->getStyle('A1')->getFont()->setSize(16)->setBold(true);
				$sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

				$sheet->setCellValue('A2', $period);
				$sheet->mergeCells("A2:{$endColumn}2");
				$sheet->getStyle('A2')->getFont()->setSize(12)->setItalic(true);
				$sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

				$headerRange = "A4:{$endColumn}4";
				$sheet->getStyle($headerRange)->getFont()->setBold(true);
				$sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
				$sheet->getStyle($headerRange)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
				$sheet->getStyle($headerRange)->getFill()
					->setFillType(Fill::FILL_SOLID)
					->getStartColor()->setARGB('FFEFF1F5');

				$sheet->getColumnDimension('A')->setWidth(16);
				$sheet->getColumnDimension('B')->setWidth(26);
				$sheet->getColumnDimension('C')->setWidth(22);
				$sheet->getColumnDimension('D')->setWidth(26);
				$sheet->getColumnDimension('E')->setWidth(26);
				$sheet->getColumnDimension('F')->setWidth(18);

				for ($i = 7; $i < 7 + count($this->dates); $i++) {
					$sheet->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setWidth(14);
				}

				$summaryStart = 7 + count($this->dates);
				for ($i = $summaryStart; $i <= $totalColumns; $i++) {
					$sheet->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setWidth(16);
				}

				$dataRange = "A4:{$endColumn}" . $sheet->getHighestRow();
				$sheet->getStyle($dataRange)->getAlignment()->setWrapText(true);
				$sheet->getStyle($dataRange)->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
				$sheet->getStyle($dataRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

				$sheet->freezePane('G5');
			},
		];
	}

	protected function buildRows(): Collection
	{
		// Debug: Check total students in DB for this firm
		$totalStudentsInDb = Student::where('firm_id', $this->firmId)->count();
		Log::info('AttendanceSummaryExport::buildRows - Total students in DB', [
			'firmId' => $this->firmId,
			'totalStudentsInDb' => $totalStudentsInDb,
		]);

		// Debug: Check without any filters first
		$studentsWithoutFilters = Student::where('firm_id', $this->firmId)->count();
		Log::info('AttendanceSummaryExport::buildRows - Students without filters', [
			'count' => $studentsWithoutFilters,
		]);

		// Debug: Check what study_centre_ids exist for students in this firm
		$existingStudyCentreIds = Student::where('firm_id', $this->firmId)
			->whereNotNull('study_centre_id')
			->distinct()
			->pluck('study_centre_id')
			->toArray();
		Log::info('AttendanceSummaryExport::buildRows - Existing study_centre_ids in students table', [
			'study_centre_ids' => $existingStudyCentreIds,
		]);

		// Debug: Check how many students have study_centre_id = 2
		$studentsWithCentre2 = Student::where('firm_id', $this->firmId)
			->where('study_centre_id', 2)
			->count();
		Log::info('AttendanceSummaryExport::buildRows - Students with study_centre_id=2', [
			'count' => $studentsWithCentre2,
		]);

		// Debug: Check how many students have NULL study_centre_id
		$studentsWithNullCentre = Student::where('firm_id', $this->firmId)
			->whereNull('study_centre_id')
			->count();
		Log::info('AttendanceSummaryExport::buildRows - Students with NULL study_centre_id', [
			'count' => $studentsWithNullCentre,
		]);

		$query = Student::query()
			->with([
				'study_groups:id,name',
				'student_education_detail',
				'student_education_detail.study_centre:id,name',
				'student_attendances' => function ($query) {
					$query->whereBetween('attendance_date', [$this->start, $this->end])
						->orderBy('attendance_date')
						->with(['student_punches' => fn($punches) => $punches->orderBy('punch_datetime')]);
				},
			])
			->where('firm_id', $this->firmId);

		// Log the filters being applied
		Log::info('AttendanceSummaryExport::buildRows - Filters', [
			'student_ids' => $this->filters['student_ids'] ?? [],
			'study_centre_ids' => $this->filters['study_centre_ids'] ?? [],
			'study_group_ids' => $this->filters['study_group_ids'] ?? [],
			'status_codes' => $this->filters['status_codes'] ?? [],
		]);

		// Cast filter values to integers to avoid string/int comparison issues
		$studentIds = array_map('intval', array_filter($this->filters['student_ids'] ?? []));
		$studyCentreIds = array_map('intval', array_filter($this->filters['study_centre_ids'] ?? []));
		$studyGroupIds = array_map('intval', array_filter($this->filters['study_group_ids'] ?? []));

		$query->when(! empty($studentIds), fn($q) => $q->whereIn('id', $studentIds))
			->when(! empty($studyCentreIds), function ($q) use ($studyCentreIds) {
				// Filter by study_centre_id from student_education_details table
				$q->whereHas('student_education_detail', fn($eduQuery) => $eduQuery->whereIn('study_centre_id', $studyCentreIds));
			})
			->when(! empty($studyGroupIds), function ($q) use ($studyGroupIds) {
				$q->whereHas('study_groups', fn($groupQuery) => $groupQuery->whereIn('study_groups.id', $studyGroupIds));
			})
			->when(! empty($this->filters['status_codes']), function ($q) {
				$q->whereHas('student_attendances', function ($attendanceQuery) {
					$attendanceQuery->whereBetween('attendance_date', [$this->start, $this->end])
						->whereIn('attendance_status_main', $this->filters['status_codes']);
				});
			});

		// Log the SQL query
		Log::info('AttendanceSummaryExport::buildRows - SQL', [
			'sql' => $query->toSql(),
			'bindings' => $query->getBindings(),
		]);

		$students = $query->orderBy('fname')->get();

		Log::info('AttendanceSummaryExport::buildRows - Students fetched', [
			'count' => $students->count(),
			'student_ids' => $students->pluck('id')->toArray(),
		]);

		return $students->map(function ($student) {
			$attendanceByDate = $student->student_attendances
				->keyBy(fn($attendance) => $attendance->attendance_date->format('Y-m-d'));

			$statusCounts = array_fill_keys(array_keys(StudentAttendance::ATTENDANCE_STATUS_MAIN_SELECT), 0);
			$statusCounts['NM'] = $statusCounts['NM'] ?? 0;

			$timeline = [];
			foreach ($this->dates as $date) {
				$attendance = $attendanceByDate[$date->format('Y-m-d')] ?? null;
				$statusCode = $attendance?->attendance_status_main ?? 'NM';
				if (isset($statusCounts[$statusCode])) {
					$statusCounts[$statusCode]++;
				} else {
					$statusCounts[$statusCode] = 1;
				}

				$punches = $attendance?->student_punches ?? collect();
				$firstPunch = $punches->first()?->punch_datetime;
				$lastPunch = $punches->last()?->punch_datetime;

				$timeline[] = [
					'status' => $statusCode,
					'status_label' => StudentAttendance::ATTENDANCE_STATUS_MAIN_SELECT[$statusCode] ?? 'Not Marked',
					'first_punch' => $firstPunch ? Carbon::parse($firstPunch)->format('H:i') : null,
					'last_punch' => $lastPunch ? Carbon::parse($lastPunch)->format('H:i') : null,
				];
			}

			return [
				'student_id' => $student->id,
				'student_code' => $student->student_education_detail?->student_code ?? $student->id,
				'student_name' => trim("{$student->fname} {$student->lname}"),
				'study_centre' => $student->student_education_detail?->study_centre?->name ?? 'Unassigned',
				'study_groups' => $student->study_groups->pluck('name')->implode(', ') ?: '—',
				'email' => $student->email,
				'phone' => $student->phone,
				'stats' => [
					'present' => $statusCounts['P'] ?? 0,
					'absent' => $statusCounts['A'] ?? 0,
					'leave' => $statusCounts['L'] ?? 0,
					'week_off' => ($statusCounts['W'] ?? 0) + ($statusCounts['WFR'] ?? 0),
					'half_day' => $statusCounts['HD'] ?? 0,
					'late' => $statusCounts['LM'] ?? 0,
					'not_marked' => $statusCounts['NM'] ?? 0,
				],
				'timeline' => $timeline,
			];
		});
	}

	protected function resolveDateRange(): void
	{
		try {
			$this->start = isset($this->filters['date_range']['start'])
				? Carbon::parse($this->filters['date_range']['start'])->startOfDay()
				: now()->startOfMonth();

			$this->end = isset($this->filters['date_range']['end'])
				? Carbon::parse($this->filters['date_range']['end'])->endOfDay()
				: now()->endOfMonth();

			if ($this->end->lt($this->start)) {
				[$this->start, $this->end] = [$this->end->copy(), $this->start->copy()];
			}

			$this->dates = iterator_to_array(new CarbonPeriod($this->start, '1 day', $this->end));
		} catch (\Throwable $e) {
			$this->start = now()->startOfMonth();
			$this->end = now()->endOfMonth();
			$this->dates = iterator_to_array(new CarbonPeriod($this->start, '1 day', $this->end));
		}
	}
}


