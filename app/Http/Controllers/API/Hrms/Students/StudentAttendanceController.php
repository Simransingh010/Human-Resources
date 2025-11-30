<?php

namespace App\Http\Controllers\API\Hrms\Students;

use App\Http\Controllers\Controller;
use App\Models\Hrms\Employee;
use App\Models\Hrms\Student;
use App\Models\Hrms\StudentAttendance;
use App\Models\Hrms\StudentPunch;
use App\Models\Hrms\StudentEducationDetail;
use App\Models\Hrms\StudyGroup;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class StudentAttendanceController extends Controller
{
	/**
	 * Constructor removed - middleware applied at route level
	 */

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
			return response()->json([
				'message_type' => 'error',
				'message' => 'Validation failed',
				'error_type' => 'VALIDATION_ERROR',
				'errors' => $e->errors()
			], 422);
		} catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
			return response()->json([
				'message_type' => 'error',
				'message' => $e->getMessage(),
				'error_type' => 'HTTP_ERROR',
				'status_code' => $e->getStatusCode()
			], $e->getStatusCode());
		} catch (\Throwable $e) {
			return response()->json([
				'message_type' => 'error',
				'message' => 'Failed to fetch coach students',
				'error_type' => 'INTERNAL_ERROR',
				'error_message' => $e->getMessage(),
				'error_file' => $e->getFile(),
				'error_line' => $e->getLine(),
				'error_trace' => explode("\n", $e->getTraceAsString())
			], 500);
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
			
			// Determine message type based on results
			$hasErrors = $results->contains(fn($r) => $r['result'] === 'error');
			$hasSuccess = $results->contains(fn($r) => $r['result'] === 'success');
			$hasSkipped = $results->contains(fn($r) => $r['result'] === 'skipped');
			
			if ($hasErrors && !$hasSuccess) {
				$messageType = 'error';
				$message = 'Failed to mark attendance for all students';
			} elseif ($hasErrors) {
				$messageType = 'warning';
				$message = 'Attendance processed with some errors';
			} elseif ($hasSkipped && !$hasSuccess) {
				$messageType = 'info';
				$message = 'All students already had attendance marked';
			} else {
				$messageType = 'success';
				$message = 'Attendance marked successfully';
			}
			
			// Also return full assigned list with marked/not-marked for UI differentiation
			$students = $this->fetchCoachStudents($coach->id);
			$attendanceMap = $this->mapAttendancesForDate($students->pluck('id')->all(), $data['date']);
			$studentsList = $students->map(fn($s) => $this->buildStudentSummaryMarked($s, $attendanceMap, $data['date']));
			return response()->json([
				'message_type' => $messageType,
				'message_display' => 'flash',
				'message' => $message,
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
					'study_centre_id' => optional($student->student_education_detail)->study_centre_id,
					'todaypunches' => [],
				], 200);
			}

			return response()->json([
				'message_type' => 'info',
				'message_display' => 'none',
				'message' => 'Today punches fetched',
				'nextpunch' => $next,
				'study_centre_id' => optional($student->student_education_detail)->study_centre_id,
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
	 * Get complete student details including personal and education information.
	 */
	public function studentDetails(Request $request)
	{
		try {
			$student = $this->getAuthenticatedStudent($request);
			$studentWithDetails = $this->fetchStudentWithAllDetails($student->id);
			$details = $this->buildCompleteStudentDetails($studentWithDetails);
			
			return response()->json([
				'message_type' => 'success',
				'message_display' => 'none',
				'message' => 'Student details fetched',
				'student' => $details,
			], 200);
		} catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
			return $this->errorResponse($e->getStatusCode(), $e->getMessage(), 'HTTP_ERROR');
		} catch (\Throwable $e) {
			\Log::error('Student details error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
			return $this->errorResponseThrowable($e, 500, 'Failed to fetch student details. Internal server error.', 'INTERNAL_ERROR');
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
		if (!$user) {
			throw new \Symfony\Component\HttpKernel\Exception\HttpException(401, 'Unauthenticated: No user found in request. Please provide a valid Bearer token.');
		}
		if (!method_exists($user, 'student')) {
			throw new \Symfony\Component\HttpKernel\Exception\HttpException(500, 'User model does not have a student relationship. Please check your user setup.');
		}
		if (!$user->student) {
			throw new \Symfony\Component\HttpKernel\Exception\HttpException(404, 'Authenticated user does not have a student record. Please onboard the user as a student.');
		}
		$student = $user->student;
		$student->load('student_education_detail.study_centre');
		return $student;
	}

	/**
	 * Get authenticated coach's employee record (required for coach actions).
	 */
	private function getAuthenticatedCoach(Request $request)
	{
		$user = $request->user();
		if (!$user) {
			throw new \Symfony\Component\HttpKernel\Exception\HttpException(401, 'Unauthenticated: No user found in request. Please provide a valid Bearer token.');
		}
		
		if (!method_exists($user, 'employee')) {
			\Log::error('User model missing employee relationship', ['user_id' => $user->id]);
			throw new \Symfony\Component\HttpKernel\Exception\HttpException(500, 'User model does not have an employee relationship. Please check your user setup.');
		}
		
		if (!$user->employee) {
			\Log::warning('User has no employee record', ['user_id' => $user->id]);
			throw new \Symfony\Component\HttpKernel\Exception\HttpException(403, 'Only employees can perform coach attendance actions. Please ensure you are onboarded as an employee.');
		}
		
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
		// Get coach's firm_id from the employee record
		$coach = Employee::find($coachEmployeeId);
		
		if (!$coach) {
			\Log::error("Coach employee not found", ['coach_id' => $coachEmployeeId]);
			abort(404, 'Coach employee record not found. Please ensure the employee is properly set up.');
		}
		
		if (!$coach->firm_id) {
			\Log::error("Coach has no firm_id", ['coach_id' => $coachEmployeeId]);
			abort(422, 'Coach employee has no firm assigned. Please contact administrator.');
		}
		
		$firmId = $coach->firm_id;

		// Get all active study groups assigned to this coach
		$groups = StudyGroup::with('study_centre')
			->where('coach_id', $coachEmployeeId)
			->where('is_active', true)
			->where('firm_id', $firmId)
			->get();

		if ($groups->isEmpty()) {
			// Fallback: get students directly assigned via reporting_coach_id
			return Student::with('student_education_detail.study_centre')
				->where('firm_id', $firmId)
				->where('is_inactive', false)
				->whereHas('student_education_detail', fn($q) => $q->where('reporting_coach_id', $coachEmployeeId))
				->orderBy('fname')
				->get();
		}

		// Get all study centre IDs from the coach's groups
		$studyCentreIds = $groups->pluck('study_centre_id')->filter()->unique()->toArray();

		if (empty($studyCentreIds)) {
			return collect();
		}

		// Fetch all students from those study centres (same logic as Livewire)
		$students = Student::with('student_education_detail.study_centre')
			->where('firm_id', $firmId)
			->where('is_inactive', false)
			->whereHas('student_education_detail', function ($q) use ($studyCentreIds) {
				$q->whereIn('study_centre_id', $studyCentreIds);
			})
			->orderBy('fname')
			->get();

		return $students;
	}

	private function mapAttendancesForDate(array $studentIds, string $date)
	{
		$attendances = StudentAttendance::whereIn('student_id', $studentIds)
			->whereDate('attendance_date', $date)
			->get(['id', 'student_id', 'attendance_status_main'])
			->keyBy('student_id');
		
		// Manually load first and last punch for each attendance
		$attendanceIds = $attendances->pluck('id')->all();
		if (!empty($attendanceIds)) {
			$allPunches = StudentPunch::whereIn('student_attendance_id', $attendanceIds)
				->orderBy('punch_datetime', 'asc')
				->get()
				->groupBy('student_attendance_id');
			
			foreach ($attendances as $attendance) {
				if ($allPunches->has($attendance->id)) {
					$punches = $allPunches->get($attendance->id);
					// Store both first and last punch
					$attendance->setRelation('student_punches', collect([
						'first' => $punches->first(),
						'last' => $punches->last(),
					]));
				} else {
					$attendance->setRelation('student_punches', collect());
				}
			}
		}
		
		return $attendances;
	}

	private function buildStudentSummary($student, $attendanceMap, string $date): array
	{
		$att = $attendanceMap->get($student->id);
		$status = $att->attendance_status_main ?? null;
		$label = $status ? (StudentAttendance::ATTENDANCE_STATUS_MAIN_SELECT[$status] ?? $status) : null;
		$marked = $att ? 'yes' : 'no';
		
		// Extract selfie and times from first and last punch if attendance exists
		$selfieUrl = null;
		$selfieThumbUrl = null;
		$markedTime = null;
		$exitTime = null;
		
		if ($att && $att->student_punches->isNotEmpty()) {
			$firstPunch = $att->student_punches->get('first');
			$lastPunch = $att->student_punches->get('last');
			
			if ($firstPunch) {
				$media = $firstPunch->getMedia('selfie')->first();
				if ($media) {
					$selfieUrl = $media->getUrl();
					$selfieThumbUrl = $media->getUrl('thumb');
				}
				$markedTime = $firstPunch->punch_datetime ? $firstPunch->punch_datetime->format('Y-m-d H:i:s') : null;
			}
			
			// Only set exit time if there's a last punch and it's different from first (multiple punches)
			if ($lastPunch && $firstPunch && $lastPunch->id !== $firstPunch->id) {
				$exitTime = $lastPunch->punch_datetime ? $lastPunch->punch_datetime->format('Y-m-d H:i:s') : null;
			}
		}
		
		return [
			'id' => $student->id,
			'student_id' => $student->id,
			'firm_id' => $student->firm_id,
			'name' => trim(($student->fname ?? '') . ' ' . ($student->mname ?? '') . ' ' . ($student->lname ?? '')),
			
			'email' => $student->email,
			'phone' => $student->phone,
			'student_code' => optional($student->student_education_detail)->student_code ?? '',
			'study_centre_id' => optional($student->student_education_detail)->study_centre_id ?? '',
			'study_centre' => optional($student->student_education_detail)->study_centre->name ?? '',
			'date' => $date,
			'attendance_status_main' => $status ?? '',
			'attendance_status_label' => $label ?? '',
			'marked_attendence' => $marked,
			'marked_time' => $markedTime,
			'exit_time' => $exitTime,
			'captured_image' => $selfieUrl,
			'captured_image_thumb' => $selfieThumbUrl,
		];
	}

	private function validateCoachMarkInput(Request $request): array
	{
		$allowed = implode(',', array_keys(StudentAttendance::ATTENDANCE_STATUS_MAIN_SELECT));
		$data = Validator::validate($request->all(), [
			'date' => 'required|date',
			'attendance_status_main' => "required|string|in:$allowed",
			'remarks' => 'nullable|string|max:255',
			'student_id' => 'nullable|integer',
			'student_ids' => 'nullable|array',
			'student_ids.*' => 'integer',
			'latitude' => 'nullable|numeric|between:-90,90',
			'longitude' => 'nullable|numeric|between:-180,180',
			'selfie' => 'nullable|image|max:4096',
		]);
		
		// Validate location/selfie requirements based on attendance status
		$status = $data['attendance_status_main'];
		$statusesWithoutPresence = ['A', 'L', 'LWP', 'S']; // Absent, Leave, Leave without Pay, Suspended
		
		if (!in_array($status, $statusesWithoutPresence)) {
			// For present statuses, require at least location
			if (empty($data['latitude']) && empty($data['longitude'])) {
				throw \Illuminate\Validation\ValidationException::withMessages([
					'latitude' => ['Location (latitude/longitude) is required when marking attendance as present.'],
				]);
			}
		}
		
		return $data;
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
		
		// Check if attendance exists
		$attendanceExists = $this->attendanceAlreadyExists($studentId, $data['date']);
		
		if ($attendanceExists) {
			// Attendance already exists - check if we should create additional punch
			$attendance = StudentAttendance::where('student_id', $studentId)
				->whereDate('attendance_date', $data['date'])
				->first();
			
			// Only create punch if metadata (selfie/location) is provided
			$hasSelfie = $request->hasFile('selfie');
			$hasLatLng = isset($data['latitude']) || isset($data['longitude']);
			
			if ($hasSelfie || $hasLatLng) {
				// Create additional punch based on last punch
				$this->maybeCreateCoachPunch($request, $studentId, $attendance, $data);
				return $this->coachResult($studentId, 'success', 'Additional punch recorded', true);
			}
			
			return $this->coachResult($studentId, 'skipped', 'Attendance already marked', true);
		}
		
		// Create new attendance
		$attendance = $this->createCoachAttendance($studentId, $data, $coach);
		$this->maybeCreateCoachPunch($request, $studentId, $attendance, $data);
		return $this->coachResult($studentId, 'success', 'Attendance marked', true);
	}

	private function isStudentAssignedToCoach(int $studentId, int $coachEmployeeId): bool
	{
		// Get all study centre IDs from coach's active groups
		$studyCentreIds = StudyGroup::where('coach_id', $coachEmployeeId)
			->where('is_active', true)
			->pluck('study_centre_id')
			->filter()
			->unique()
			->toArray();

		if (!empty($studyCentreIds)) {
			// Check if student belongs to any of those study centres
			$inStudyCentre = StudentEducationDetail::where('student_id', $studentId)
				->whereIn('study_centre_id', $studyCentreIds)
				->exists();

			if ($inStudyCentre) {
				return true;
			}
		}

		// Fallback: check reporting_coach_id in education detail
		return StudentEducationDetail::where('student_id', $studentId)
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
		$student = \App\Models\Hrms\Student::with('student_education_detail.study_centre')->find($studentId);
		
		if (!$student) {
			\Log::error('Student not found during coach attendance creation', ['student_id' => $studentId]);
			abort(404, 'Student not found. Please verify the student ID.');
		}
		
		if (!$student->firm_id) {
			\Log::error('Student has no firm_id', ['student_id' => $studentId]);
			abort(422, 'Student has no firm assigned. Please contact administrator.');
		}
		
		$coachName = trim(($coach->fname ?? '') . ' ' . ($coach->lname ?? ''));
		$remarks = 'Marked by Coach - ' . $coachName;
		if (!empty($data['remarks'])) {
			$remarks .= ' | ' . $data['remarks'];
		}
		return StudentAttendance::create([
			'firm_id' => (int) $student->firm_id,
			'student_id' => $student->id,
			'study_centre_id' => optional($student->student_education_detail)->study_centre_id,
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
	 * Skip punch creation for absent/leave statuses.
	 * Automatically determines IN/OUT based on last punch.
	 */
	private function maybeCreateCoachPunch(Request $request, int $studentId, StudentAttendance $attendance, array $data): void
	{
		// Don't create punch for absent/leave statuses
		$statusesWithoutPresence = ['A', 'L', 'LWP', 'S']; // Absent, Leave, Leave without Pay, Suspended
		if (in_array($data['attendance_status_main'], $statusesWithoutPresence)) {
			return;
		}
		
        $hasSelfie = $request->hasFile('selfie');
        $hasLatLng = isset($data['latitude']) || isset($data['longitude']);
        if (!$hasSelfie && !$hasLatLng) {
            return;
        }
        $student = \App\Models\Hrms\Student::with('student_education_detail.study_centre')->find($studentId);
        
        if (!$student) {
            \Log::error('Student not found during coach punch creation', ['student_id' => $studentId]);
            return; // Silently skip punch creation if student not found
        }
        
        [$today, $now] = $this->getTodayAndNow();
        
        // Determine IN/OUT based on last punch for this date
        $lastPunch = $this->getLastPunchForDate($studentId, $data['date'] ?? $today);
        $inOut = 'in'; // Default to 'in'
        
        if ($lastPunch) {
            // Alternate based on last punch
            $inOut = ($lastPunch->in_out === 'in') ? 'out' : 'in';
        }
        
        $punch = $this->createPunchRecord($student, $attendance, [
            'study_centre_id' => optional($student->student_education_detail)->study_centre_id,
            'in_out' => $inOut,
            'punch_type' => 'manual',
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'device_id' => null,
        ], $data['date'] ?? $today, $now);
        $this->attachSelfieIfProvided($request, $punch);
        
        // Update duration if this is an 'out' punch
        if ($inOut === 'out' && $lastPunch && $lastPunch->in_out === 'in') {
            $this->updateDurationOnOutIfNeeded($attendance, $lastPunch, 'out', $now);
        }
	}

	// ---------------------------
	// Student details helpers
	// ---------------------------

	private function fetchStudentWithAllDetails(int $studentId): Student
	{
		return Student::with([
			'student_personal_detail',
			'student_education_detail.study_centre',
			'student_education_detail.reporting_coach',
			'student_education_detail.location',
			'firm',
			'user'
		])->findOrFail($studentId);
	}

	private function buildCompleteStudentDetails(Student $student): array
	{
		return array_merge(
			$this->extractStudentBasicInfo($student),
			$this->extractPersonalDetails($student),
			$this->extractEducationDetails($student)
		);
	}

	private function extractStudentBasicInfo(Student $student): array
	{
		return [
			'id' => $student->id,
			'firm_id' => $student->firm_id,
			'user_id' => $student->user_id,
			'name' => $this->buildFullName($student),
			'fname' => $student->fname,
			'mname' => $student->mname,
			'lname' => $student->lname,
			'email' => $student->email,
			'phone' => $student->phone,
			'is_inactive' => $student->is_inactive,
			'created_at' => $student->created_at,
			'updated_at' => $student->updated_at,
		];
	}

	private function extractPersonalDetails(Student $student): array
	{
		$personal = $student->student_personal_detail;
		if (!$personal) {
			return $this->getEmptyPersonalDetails();
		}
		return [
			'gender' => $personal->gender,
			'fathername' => $personal->fathername,
			'mothername' => $personal->mothername,
			'mobile_number' => $personal->mobile_number,
			'dob' => $personal->dob,
			'admission_date' => $personal->admission_date,
			'marital_status' => $personal->marital_status,
			'doa' => $personal->doa,
			'nationality' => $personal->nationality,
			'adharno' => $personal->adharno,
			'panno' => $personal->panno,
		];
	}

	private function extractEducationDetails(Student $student): array
	{
		$education = $student->student_education_detail;
		if (!$education) {
			return $this->getEmptyEducationDetails();
		}
		return [
			'student_code' => $education->student_code,
			'doh' => $education->doh,
			'study_centre_id' => $education->study_centre_id,
			'study_centre_name' => optional($education->study_centre)->name,
			'reporting_coach_id' => $education->reporting_coach_id,
			'reporting_coach_name' => $this->buildCoachName($education->reporting_coach),
			'location_id' => $education->location_id,
			'location_name' => optional($education->location)->name,
			'doe' => $education->doe,
		];
	}

	private function buildFullName(Student $student): string
	{
		return trim(($student->fname ?? '') . ' ' . ($student->mname ?? '') . ' ' . ($student->lname ?? ''));
	}

	private function buildCoachName($coach): ?string
	{
		if (!$coach) {
			return null;
		}
		return trim(($coach->fname ?? '') . ' ' . ($coach->lname ?? ''));
	}

	private function getEmptyPersonalDetails(): array
	{
		return [
			'gender' => null,
			'fathername' => null,
			'mothername' => null,
			'mobile_number' => null,
			'dob' => null,
			'admission_date' => null,
			'marital_status' => null,
			'doa' => null,
			'nationality' => null,
			'adharno' => null,
			'panno' => null,
		];
	}

	private function getEmptyEducationDetails(): array
	{
		return [
			'student_code' => null,
			'doh' => null,
			'study_centre_id' => null,
			'study_centre_name' => null,
			'reporting_coach_id' => null,
			'reporting_coach_name' => null,
			'location_id' => null,
			'location_name' => null,
			'doe' => null,
		];
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


