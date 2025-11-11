<?php

namespace App\Livewire\Hrms\Onboard;

use App\Models\Hrms\Student;
use App\Models\Hrms\StudentEducationDetail;
use App\Models\Hrms\StudentPersonalDetail;
use Livewire\Component;
use Illuminate\Validation\Rule;
use Flux;

class OnboardStudents extends Component
{
	public ?int $selectedStudentId = null;
	public int $currentStep = 1;
	public int $totalSteps = 3;

	public array $studentData = [
		'id' => null,
		'fname' => '',
		'mname' => '',
		'lname' => '',
		'email' => '',
		'phone' => '',
		'study_centre_id' => null,
	];

	public array $personalData = [
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

	public array $educationData = [
		'student_code' => null,
		'doh' => null,
		'study_centre_id' => null,
		'reporting_coach_id' => null,
		'location_id' => null,
		'doe' => null,
	];

	public function mount(?int $studentId = null): void
	{
		$this->selectedStudentId = $studentId;
		if ($studentId) {
			$this->loadStudent($studentId);
		}
	}

	public function nextStep(): void
	{
		if ($this->currentStep === 1) $this->saveStudentStep();
		if ($this->currentStep === 2) $this->savePersonalStep();
		if ($this->currentStep < $this->totalSteps) $this->currentStep++;
	}

	public function previousStep(): void
	{
		if ($this->currentStep > 1) $this->currentStep--;
	}

	public function goToStep(int $step): void
	{
		if ($step >= 1 && $step <= $this->totalSteps) $this->currentStep = $step;
	}

	private function loadStudent(int $id): void
	{
		$student = Student::with(['student_personal_detail', 'student_education_detail'])->findOrFail($id);
		$this->studentData = array_merge($this->studentData, $student->only(['id','fname','mname','lname','email','phone','study_centre_id']));
		if ($student->student_personal_detail) {
			$this->personalData = array_merge($this->personalData, $student->student_personal_detail->only(array_keys($this->personalData)));
		}
		if ($student->student_education_detail) {
			$this->educationData = array_merge($this->educationData, $student->student_education_detail->only(array_keys($this->educationData)));
		}
	}

	private function validateStudent(): array
	{
		return $this->validate([
			'studentData.fname' => 'required|string|max:255',
			'studentData.mname' => 'nullable|string|max:255',
			'studentData.lname' => 'nullable|string|max:255',
			'studentData.email' => [
				'required','string','email','max:255',
				Rule::unique('students','email')->ignore($this->studentData['id']),
			],
			'studentData.phone' => [
				'required',
				Rule::unique('students','phone')->ignore($this->studentData['id']),
			],
			'studentData.study_centre_id' => 'nullable|exists:study_centres,id',
		]);
	}

	private function validatePersonal(): array
	{
		return $this->validate([
			'personalData.gender' => 'nullable|string|max:20',
			'personalData.fathername' => 'nullable|string|max:255',
			'personalData.mothername' => 'nullable|string|max:255',
			'personalData.mobile_number' => 'nullable|string|max:20',
			'personalData.dob' => 'nullable|date',
			'personalData.admission_date' => 'nullable|date',
			'personalData.marital_status' => 'nullable|string|max:50',
			'personalData.doa' => 'nullable|date',
			'personalData.nationality' => 'nullable|string|max:50',
			'personalData.adharno' => 'nullable|string|max:20',
			'personalData.panno' => 'nullable|string|max:20',
		]);
	}

	private function validateEducation(): array
	{
		return $this->validate([
			'educationData.student_code' => 'nullable|string|max:50',
			'educationData.doh' => 'nullable|date',
			'educationData.study_centre_id' => 'nullable|exists:study_centres,id',
			'educationData.reporting_coach_id' => 'nullable|exists:employees,id',
			'educationData.location_id' => 'nullable|exists:joblocations,id',
			'educationData.doe' => 'nullable|date',
		]);
	}

	public function saveStudentStep(): void
	{
		$this->validateStudent();
		$student = $this->studentData['id']
			? Student::findOrFail($this->studentData['id'])
			: new Student();
		$payload = $this->studentData;
		$payload['firm_id'] = $payload['firm_id'] ?? session('firm_id');
		unset($payload['id']);
		$student->fill($payload)->save();
		$this->studentData['id'] = $student->id;
		$this->selectedStudentId = $student->id;
		Flux::toast(heading: 'Saved', text: 'Student info saved.');
	}

	public function savePersonalStep(): void
	{
		$this->validatePersonal();
		$studentId = $this->studentData['id'];
		$personal = StudentPersonalDetail::firstOrNew(['student_id' => $studentId]);
		$personal->fill(array_merge($this->personalData, ['student_id' => $studentId]))->save();
		Flux::toast(heading: 'Saved', text: 'Personal details saved.');
	}

	public function saveEducationStep(): void
	{
		$this->validateEducation();
		$studentId = $this->studentData['id'];
		$education = StudentEducationDetail::firstOrNew(['student_id' => $studentId]);
		$education->fill(array_merge($this->educationData, ['student_id' => $studentId]))->save();
		Flux::toast(heading: 'Saved', text: 'Education details saved.');
	}

	public function completeOnboarding(): void
	{
		$this->saveEducationStep();
		$this->dispatch('student-updated', studentId: $this->studentData['id']);
		$this->dispatch('close-modal', 'edit-student');
		Flux::toast(heading: 'Onboarding Complete', text: 'Student onboarding finished.');
	}

	public function render()
	{
		return view()->file(app_path('Livewire/Hrms/Onboard/blades/onboard-students.blade.php'));
	}
}


