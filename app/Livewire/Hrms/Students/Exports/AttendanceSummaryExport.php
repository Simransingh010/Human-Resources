<?php

namespace App\Livewire\Hrms\Students\Exports;

use App\Models\Hrms\Student;
use App\Models\Hrms\StudentAttendance;
use App\Models\Hrms\StudentPunch;
use App\Models\Saas\Firm;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
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

	public function __construct(array $filters)
	{
		$this->filters = $filters;
		$this->resolveDateRange();
		$this->firmName = optional(Firm::find(session('firm_id')))->name ?? '—';
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
		$firmId = session('firm_id');

		$students = Student::query()
			->with([
				'study_centre:id,name',
				'study_groups:id,name',
				'student_education_detail:student_id,student_code',
				'student_attendances' => function ($query) {
					$query->whereBetween('attendance_date', [$this->start, $this->end])
						->orderBy('attendance_date')
						->with(['student_punches' => fn($punches) => $punches->orderBy('punch_datetime')]);
				},
			])
			->where('firm_id', $firmId)
			->when(! empty($this->filters['student_ids']), fn($query) => $query->whereIn('id', $this->filters['student_ids']))
			->when(! empty($this->filters['study_centre_ids']), fn($query) => $query->whereIn('study_centre_id', $this->filters['study_centre_ids']))
			->when(! empty($this->filters['study_group_ids']), function ($query) {
				$query->whereHas('study_groups', fn($groupQuery) => $groupQuery->whereIn('study_groups.id', $this->filters['study_group_ids']));
			})
			->when(! empty($this->filters['status_codes']), function ($query) {
				$query->whereHas('student_attendances', function ($attendanceQuery) {
					$attendanceQuery->whereBetween('attendance_date', [$this->start, $this->end])
						->whereIn('attendance_status_main', $this->filters['status_codes']);
				});
			})
			->orderBy('fname')
			->get();

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
				'student_code' => $student->student_education_detail->student_code ?? $student->id,
				'student_name' => trim("{$student->fname} {$student->lname}"),
				'study_centre' => $student->study_centre->name ?? 'Unassigned',
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


