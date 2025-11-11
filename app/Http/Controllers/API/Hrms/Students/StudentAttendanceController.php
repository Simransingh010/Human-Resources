<?php

namespace App\Http\Controllers\API\Hrms\Students;

use App\Http\Controllers\Controller;
use App\Models\Hrms\Student;
use App\Models\Hrms\StudentAttendance;
use App\Models\Hrms\StudentPunch;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class StudentAttendanceController extends Controller
{
	/**
	 * List students assigned to the authenticated coach (by reporting_coach_id)
	 * with their attendance status for the given date (defaults to today).
	 */
	public function coachStudents(Request $request)
	{
		try {
			$date = $this->validateCoachStudentsInput($request);
			$coach = $this->getAuthenticatedCoach($request);
			$students = $this->fetchCoachStudents($coach->id);
			$attendanceMap = $this->mapAttendancesForDate($students->pluck('id')->all(), $date);
			$list = $students->map(fn($s) => $this->buildStudentSummary($s, $attendanceMap, $date));
			return response()->json([
				'message_type' => 'success',
				'message_display' => 'none',
				'message' => 'Coach students fetched',
				'date' => $date,
				'students' => $list,
			], 200);
		} catch (\Illuminate\Validation\ValidationException $e) {
			return $this->errorResponse(422, 'Validation failed', 'VALIDATION_ERROR', $e->errors());
		} catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
			return $this->errorResponse($e->getStatusCode(), $e->getMessage(), 'HTTP_ERROR');
		} catch (\Throwable $e) {
			\Log::error('coachStudents error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
			return $this->errorResponseThrowable($e, 500, 'Failed to fetch coach students. Internal server error.', 'INTERNAL_ERROR');
		}
	}

	/**
	 * Coach marks attendance for one or more assigned students for today only.
	 * Does NOT overwrite if already marked.
	 */
	public function coachMarkAttendance(Request $request)
	{
		try {
			$data = $this->validateCoachMarkInput($request);
			$coach = $this->getAuthenticatedCoach($request);
			$studentIds = $this->normalizeStudentIds($data);
			$this->assertDateIsToday($data['date']);
			$results = collect($studentIds)->map(fn($sid) => $this->processCoachMark($sid, $data, $coach, $request));
			// Also return full assigned list with marked/not-marked for UI differentiation
			$students = $this->fetchCoachStudents($coach->id);
			$attendanceMap = $this->mapAttendancesForDate($students->pluck('id')->all(), $data['date']);
			$studentsList = $students->map(fn($s) => $this->buildStudentSummaryMarked($s, $attendanceMap, $data['date']));
			return response()->json([
				'message_type' => 'success',
				'message_display' => 'none',
				'message' => 'Coach attendance processed',
				'date' => $data['date'],
				'results' => $results,
				'students' => $studentsList,
			], 200);
		} catch (\Illuminate\Validation\ValidationException $e) {
			return $this->errorResponse(422, 'Validation failed', 'VALIDATION_ERROR', $e->errors());
		} catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
			return $this->errorResponse($e->getStatusCode(), $e->getMessage(), 'HTTP_ERROR');
		} catch (\Throwable $e) {
			\Log::error('coachMarkAttendance error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
			return $this->errorResponseThrowable($e, 500, 'Failed to mark coach attendance. Internal server error.', 'INTERNAL_ERROR');
		}
	}
	/**
	 * Record a student IN/OUT punch with optional selfie and geolocation.
	 */
	public function punch(Request $request)
	{
		try {
			$data = $this->validatePunchInput($request);
			$student = $this->getAuthenticatedStudent($request);
			[$today, $now] = $this->getTodayAndNow();

			$lastPunch = $this->getLastPunchForDate($student->id, $today);
			$this->assertValidPunchSequence($lastPunch, $data['in_out']);

			$attendance = $this->resolveAttendanceForPunch($student, $data, $today);
			$punch = $this->createPunchRecord($student, $attendance, $data, $today, $now);
			$this->attachSelfieIfProvided($request, $punch);
			$this->updateDurationOnOutIfNeeded($attendance, $lastPunch, $data['in_out'], $now);

			return response()->json([
				'message_type' => 'success',
				'message_display' => 'none',
				'message' => 'Punch recorded',
				'punch' => $punch,
			], 201);
		} catch (\Illuminate\Validation\ValidationException $e) {
			return $this->errorResponse(
				422,
				'Validation failed',
				'VALIDATION_ERROR',
				$e->errors()
			);
		} catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
			return $this->errorResponse(
				$e->getStatusCode(),
				$e->getMessage(),
				'HTTP_ERROR'
			);
		} catch (\Throwable $e) {
			\Log::error('Student punch error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
			return $this->errorResponseThrowable(
				$e,
				500,
				'Failed to record punch. Internal server error.',
				'INTERNAL_ERROR'
			);
		}
	}

	/**
	 * Return today's punches and next expected punch.
	 */
	public function punchStatus(Request $request)
	{
		try {
			$student = $this->getAuthenticatedStudent($request);
			[$today, ] = $this->getTodayAndNow();

			$punches = $this->getPunchesForDate($student->id, $today);
			$punches = $this->mapSelfieUrls($punches);
			$next = $this->getNextPunchDirection($punches);

			if ($punches->isEmpty()) {
				return response()->json([
					'message_type' => 'info',
					'message_display' => 'none',
					'message' => 'No punches found for today',
					'nextpunch' => 'in',
					'study_centre_id' => $student->study_centre_id,
					'todaypunches' => [],
				], 200);
			}

			return response()->json([
				'message_type' => 'info',
				'message_display' => 'none',
				'message' => 'Today punches fetched',
				'nextpunch' => $next,
				'study_centre_id' => $student->study_centre_id,
				'todaypunches' => $punches,
			], 200);
		} catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
			return $this->errorResponse(
				$e->getStatusCode(),
				$e->getMessage(),
				'HTTP_ERROR'
			);
		} catch (\Throwable $e) {
			\Log::error('Student punchStatus error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
			return $this->errorResponseThrowable(
				$e,
				500,
				'Failed to fetch today punches. Internal server error.',
				'INTERNAL_ERROR'
			);
		}
	}

	/**
	 * List attendances with punches in a period.
	 */
	public function attendanceWithPunches(Request $request)
	{
		try {
			$validated = $this->validatePeriodInput($request);
			$student = $this->getAuthenticatedStudent($request);

			$attendances = StudentAttendance::with(['student_punches' => function ($q) {
				$q->orderBy('punch_datetime', 'desc');
			}])
				->where('student_id', $student->id)
				->whereBetween('attendance_date', [$validated['start_date'], $validated['end_date']])
				->orderBy('attendance_date')
				->get()
				->map(function ($a) {
					$a->weekday = Carbon::parse($a->attendance_date)->format('D');
					return $a;
				});

			if ($attendances->isEmpty()) {
				return response()->json([
					'message_type' => 'info',
					'message_display' => 'none',
					'message' => 'No attendance found for the selected period',
					'attendances' => [],
				], 200);
			}

			return response()->json([
				'message_type' => 'success',
				'message_display' => 'none',
				'message' => 'Attendance list fetched',
				'attendances' => $attendances,
			], 200);
		} catch (\Illuminate\Validation\ValidationException $e) {
			return $this->errorResponse(
				422,
				'Validation failed',
				'VALIDATION_ERROR',
				$e->errors()
			);
		} catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
			return $this->errorResponse(
				$e->getStatusCode(),
				$e->getMessage(),
				'HTTP_ERROR'
			);
		} catch (\Throwable $e) {
			\Log::error('Student attendanceWithPunches error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
			return $this->errorResponseThrowable(
				$e,
				500,
				'Failed to fetch attendance list. Internal server error.',
				'INTERNAL_ERROR'
			);
		}
	}

	/**
	 * Validation helpers
	 */

	private function validatePunchInput(Request $request): array
	{
		return Validator::validate($request->all(), [
			'in_out' => 'required|in:in,out',
			'punch_type' => 'required|in:manual,auto',
			'latitude' => 'nullable|numeric|between:-90,90',
			'longitude' => 'nullable|numeric|between:-180,180',
			'study_centre_id' => 'nullable|integer',
			'device_id' => 'nullable|string|max:255',
			'selfie' => 'nullable|image|max:4096',
		]);
	}

	private function validatePeriodInput(Request $request): array
	{
		return Validator::validate($request->all(), [
			'start_date' => 'required|date',
			'end_date' => 'required|date|after_or_equal:start_date',
		]);
	}

	// ---------------------------
	// Authentication helpers
	// ---------------------------

	private function getAuthenticatedStudent(Request $request): Student
	{
		$user = $request->user();
		abort_if(!$user, 401, 'Unauthenticated: No user found in request. Please provide a valid Bearer token.');
		abort_if(!method_exists($user, 'student'), 500, 'User model does not have a student relationship. Please check your user setup.');
		abort_if(!$user->student, 404, 'Authenticated user does not have a student record. Please onboard the user as a student.');
		return $user->student;
	}

	/**
	 * Get authenticated coach's employee record (required for coach actions).
	 */
	private function getAuthenticatedCoach(Request $request)
	{
		$user = $request->user();
		abort_if(!$user, 401, 'Unauthenticated: No user found in request. Please provide a valid Bearer token.');
		abort_if(!method_exists($user, 'employee') || !$user->employee, 403, 'Only employees can perform coach attendance actions.');
		return $user->employee;
	}

	// ---------------------------
	// Date/time helpers
	// ---------------------------

	private function getTodayAndNow(): array
	{
		return [Carbon::today()->toDateString(), Carbon::now()];
	}

	// ---------------------------
	// Punch helpers
	// ---------------------------

	private function getLastPunchForDate(int $studentId, string $date): ?StudentPunch
	{
		return StudentPunch::where('student_id', $studentId)
			->whereDate('date', $date)
			->orderByDesc('punch_datetime')
			->first();
	}

	private function assertValidPunchSequence(?StudentPunch $lastPunch, string $currentInOut): void
	{
		if ($lastPunch && $lastPunch->in_out === $currentInOut) {
			abort(422, 'Invalid punch sequence');
		}
	}

	private function resolveAttendanceForPunch(Student $student, array $data, string $date): StudentAttendance
	{
		if ($data['in_out'] === 'in') {
			return StudentAttendance::updateOrCreate(
				[
					'firm_id' => $student->firm_id,
					'student_id' => $student->id,
					'attendance_date' => $date,
				],
				[
					'study_centre_id' => $data['study_centre_id'] ?? null,
					'attendance_status_main' => 'P',
					'duration_hours' => 0,
					'remarks' => 'Punched in',
				]
			);
		}

		$attendance = StudentAttendance::where([
			'firm_id' => $student->firm_id,
			'student_id' => $student->id,
			'attendance_date' => $date,
		])->first();

		abort_if(!$attendance, 422, 'Cannot punch out without punching in');
		return $attendance;
	}

	private function createPunchRecord(Student $student, StudentAttendance $attendance, array $data, string $date, Carbon $now): StudentPunch
	{
		return StudentPunch::create([
			'study_centre_id' => $data['study_centre_id'] ?? null,
			'student_id' => $student->id,
			'student_attendance_id' => $attendance->id,
			'date' => $date,
			'punch_datetime' => $now,
			'attendance_location_id' => null,
			'punch_geolocation' => $this->buildGeoLocation($data),
			'in_out' => $data['in_out'],
			'punch_type' => $data['punch_type'],
			'device_id' => $data['device_id'] ?? null,
			'punch_details' => null,
			'marked_by' => optional($student->user)->id,
		]);
	}

	private function buildGeoLocation(array $data): ?array
	{
		if (!isset($data['latitude'], $data['longitude'])) {
			return null;
		}
		return [
			'latitude' => $data['latitude'],
			'longitude' => $data['longitude'],
		];
	}

	private function attachSelfieIfProvided(Request $request, StudentPunch $punch): void
	{
		if ($request->hasFile('selfie')) {
			$punch->addMediaFromRequest('selfie')->toMediaCollection('selfie');
		}
	}

	private function updateDurationOnOutIfNeeded(StudentAttendance $attendance, ?StudentPunch $lastPunch, string $inOut, Carbon $now): void
	{
		if ($inOut !== 'out' || !$lastPunch || $lastPunch->in_out !== 'in') {
			return;
		}

		$workedSeconds = Carbon::parse($lastPunch->punch_datetime)->diffInSeconds($now);
		$workedHours = round($workedSeconds / 3600, 2);

		StudentAttendance::where('id', $attendance->id)->update([
			'duration_hours' => DB::raw('COALESCE(duration_hours,0) + ' . $workedHours),
			'remarks' => 'Punched out',
		]);
	}

	private function getPunchesForDate(int $studentId, string $date)
	{
		return StudentPunch::where('student_id', $studentId)
			->whereDate('date', $date)
			->orderByDesc('punch_datetime')
			->get();
	}

	private function mapSelfieUrls($punches)
	{
		return $punches->map(function ($p) {
			$media = $p->getMedia('selfie')->first();
			$p->selfie_url = $media ? $media->getUrl() : null;
			$p->selfie_thumb_url = $media ? $media->getUrl('thumb') : null;
			return $p;
		});
	}

	private function getNextPunchDirection($punches): string
	{
		return ($punches->first() && $punches->first()->in_out === 'in') ? 'out' : 'in';
	}

	// ---------------------------
	// Coach helpers
	// ---------------------------

	private function validateCoachStudentsInput(Request $request): string
	{
		$validated = Validator::validate($request->all(), [
			'date' => 'nullable|date',
		]);
		return $validated['date'] ?? Carbon::today()->toDateString();
	}

	private function fetchCoachStudents(int $coachEmployeeId)
	{
		return \App\Models\Hrms\Student::with(['study_centre', 'student_education_detail'])
			->whereHas('student_education_detail', function ($q) use ($coachEmployeeId) {
				$q->where('reporting_coach_id', $coachEmployeeId);
			})
			->orderBy('fname')
			->get(['id','firm_id','study_centre_id','fname','mname','lname','email','phone']);
	}

	private function mapAttendancesForDate(array $studentIds, string $date)
	{
		return StudentAttendance::whereIn('student_id', $studentIds)
			->whereDate('attendance_date', $date)
			->get(['student_id','attendance_status_main'])
			->keyBy('student_id');
	}

	private function buildStudentSummary($student, $attendanceMap, string $date): array
	{
		$att = $attendanceMap->get($student->id);
		$status = $att->attendance_status_main ?? null;
		$label = $status ? (StudentAttendance::ATTENDANCE_STATUS_MAIN_SELECT[$status] ?? $status) : null;
		return [
			'id' => $student->id,
			'student_id' => $student->id,
			'firm_id' => $student->firm_id,
			'name' => trim(($student->fname ?? '') . ' ' . ($student->mname ?? '') . ' ' . ($student->lname ?? '')),
			
			'email' => $student->email,
			'phone' => $student->phone,
			'student_code' => optional($student->student_education_detail)->student_code,
			'study_centre_id' => $student->study_centre_id,
			'study_centre' => optional($student->study_centre)->name,
			'date' => $date,
			'attendance_status_main' => $status,
			'attendance_status_label' => $label,
		];
	}

	private function validateCoachMarkInput(Request $request): array
	{
		$allowed = implode(',', array_keys(StudentAttendance::ATTENDANCE_STATUS_MAIN_SELECT));
		return Validator::validate($request->all(), [
			'date' => 'required|date',
			'attendance_status_main' => "required|string|in:$allowed",
			'remarks' => 'nullable|string|max:255',
			'student_id' => 'nullable|integer',
			'student_ids' => 'nullable|array',
			'student_ids.*' => 'integer',
			// Location required for coach mark (single or bulk)
			'latitude' => 'required|numeric|between:-90,90',
			'longitude' => 'required|numeric|between:-180,180',
			'selfie' => 'nullable|image|max:4096',
		]);
	}

	private function normalizeStudentIds(array $data): array
	{
		if (!empty($data['student_ids']) && is_array($data['student_ids'])) {
			return array_values(array_unique(array_map('intval', $data['student_ids'])));
		}
		if (!empty($data['student_id'])) {
			return [(int) $data['student_id']];
		}
		abort(422, 'Provide student_id or student_ids array.');
		return []; 
	}

	private function assertDateIsToday(string $date): void
	{
		abort_if(Carbon::parse($date)->toDateString() !== Carbon::today()->toDateString(), 422, 'Only today\'s attendance can be marked.');
	}

	private function processCoachMark(int $studentId, array $data, $coach, Request $request): array
	{
		if (!$this->isStudentAssignedToCoach($studentId, $coach->id)) {
			return $this->coachResult($studentId, 'error', 'Student is not assigned to this coach', false);
		}
		if ($this->attendanceAlreadyExists($studentId, $data['date'])) {
			return $this->coachResult($studentId, 'skipped', 'Attendance already marked', true);
		}
		$attendance = $this->createCoachAttendance($studentId, $data, $coach);
		$this->maybeCreateCoachPunch($request, $studentId, $attendance, $data);
		return $this->coachResult($studentId, 'success', 'Attendance marked', true);
	}

	private function isStudentAssignedToCoach(int $studentId, int $coachEmployeeId): bool
	{
		return \App\Models\Hrms\StudentEducationDetail::where('student_id', $studentId)
			->where('reporting_coach_id', $coachEmployeeId)
			->exists();
	}

	private function attendanceAlreadyExists(int $studentId, string $date): bool
	{
		return StudentAttendance::where('student_id', $studentId)
			->whereDate('attendance_date', $date)
			->exists();
	}

	private function createCoachAttendance(int $studentId, array $data, $coach): StudentAttendance
	{
		$student = \App\Models\Hrms\Student::findOrFail($studentId);
		$coachName = trim(($coach->fname ?? '') . ' ' . ($coach->lname ?? ''));
		$remarks = 'Marked by Coach - ' . $coachName;
		if (!empty($data['remarks'])) {
			$remarks .= ' | ' . $data['remarks'];
		}
		return StudentAttendance::create([
			'firm_id' => (int) $student->firm_id,
			'student_id' => $student->id,
			'study_centre_id' => $student->study_centre_id,
			'attendance_date' => $data['date'],
			'attendance_status_main' => $data['attendance_status_main'],
			'duration_hours' => 0,
			'remarks' => $remarks,
		]);
	}

	private function coachResult(int $studentId, string $status, string $message, bool $marked): array
	{
		return [
			'student_id' => $studentId,
			'result' => $status,
			'message' => $message,
			'attendance_marked' => $marked,
		];
	}

	private function buildStudentSummaryMarked($student, $attendanceMap, string $date): array
	{
		$summary = $this->buildStudentSummary($student, $attendanceMap, $date);
		$summary['attendance_marked'] = !empty($summary['attendance_status_main']);
		return $summary;
	}

	/**
	 * Create a minimal punch if metadata (selfie or lat/long) provided during coach bulk mark.
	 */
	private function maybeCreateCoachPunch(Request $request, int $studentId, StudentAttendance $attendance, array $data): void
	{
        $hasSelfie = $request->hasFile('selfie');
        $hasLatLng = isset($data['latitude']) || isset($data['longitude']);
        if (!$hasSelfie && !$hasLatLng) {
            return;
        }
        $student = \App\Models\Hrms\Student::findOrFail($studentId);
        [$today, $now] = $this->getTodayAndNow();
        $punch = $this->createPunchRecord($student, $attendance, [
            'study_centre_id' => $student->study_centre_id,
            'in_out' => 'in',
            'punch_type' => 'manual',
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'device_id' => null,
        ], $data['date'] ?? $today, $now);
        $this->attachSelfieIfProvided($request, $punch);
	}

	// ---------------------------
	// Response helpers
	// ---------------------------

	private function errorResponse(int $status, string $message, ?string $errorCode = null, $errors = null)
	{
		$payload = [
			'message_type' => 'error',
			'message_display' => 'flash',
			'message' => $message,
		];
		if ($errorCode) {
			$payload['error_code'] = $errorCode;
		}
		if (!is_null($errors)) {
			$payload['errors'] = $errors;
		}
		return response()->json($payload, $status);
	}

	/**
	 * Build an error response including exception details.
	 * Always includes the exception message under 'error'.
	 * Includes 'trace' only when app.debug is true.
	 */
	private function errorResponseThrowable(\Throwable $e, int $status, string $message, ?string $errorCode = null)
	{
		$payload = [
			'message_type' => 'error',
			'message_display' => 'flash',
			'message' => $message,
			'error' => $e->getMessage(),
		];
		if ($errorCode) {
			$payload['error_code'] = $errorCode;
		}
		if (config('app.debug')) {
			$payload['trace'] = $e->getTraceAsString();
		}
		return response()->json($payload, $status);
	}
}


