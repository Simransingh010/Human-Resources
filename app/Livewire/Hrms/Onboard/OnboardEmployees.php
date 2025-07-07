<?php

namespace App\Livewire\Hrms\Onboard;

use App\Models\Saas\Role;
use App\Models\User;
use Livewire\Component;
use App\Models\Hrms\Employee;
use Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;

class OnboardEmployees extends Component
{
    public $currentStep = 1;
    public $totalSteps = 10;
    public $selectedEmpId = null;
    public $completedSteps = [];
    public $employeeData = [
        'id' => null,
        'fname' => '',
        'mname' => '',
        'lname' => '',
        'email' => '',
        'phone' => '',
        'gender' => '',
    ];

    protected $listeners = [
        'stepCompleted' => 'handleStepCompleted',
        'stepUncompleted' => 'handleStepUncompleted'
    ];

    public function mount($employeeId = null)
    {
        if ($employeeId) {
            $this->selectedEmpId = $employeeId;
            $employee = Employee::findOrFail($employeeId);
            $this->employeeData = $employee->toArray();
            $this->determineCompletedSteps();
        }
    }

    protected function determineCompletedSteps()
    {
        $employee = Employee::find($this->selectedEmpId);
        if (!$employee) return;

        $this->completedSteps = []; // Reset completed steps

        // Step 1: Basic Info - Always completed if employee exists
        if ($employee->fname && $employee->email) {
            $this->completedSteps[] = 1;
        }

        // Step 2: Address
        if ($employee->emp_address()->exists()) {
            $this->completedSteps[] = 2;
        }

        // Step 3: Bank Accounts
        if ($employee->bank_account()->exists()) {
            $this->completedSteps[] = 3;
        }

        // Step 4: Contacts
        if ($employee->emp_emergency_contact()->exists()) {
            $this->completedSteps[] = 4;
        }

        // Step 5: Job Profile
        if ($employee->emp_job_profile()->exists()) {
            $this->completedSteps[] = 5;
        }

        // Step 6: Personal Details
        if ($employee->emp_personal_detail()->exists()) {
            $this->completedSteps[] = 6;
        }

        // Step 7: Documents
        if ($employee->documents()->exists()) {
            $this->completedSteps[] = 7;
        }

        // Step 8: Relations
        if ($employee->relations()->exists()) {
            $this->completedSteps[] = 8;
        }

        // Step 9: Work Shift
        if ($employee->emp_work_shifts()->exists()) {
            $this->completedSteps[] = 9;
        }

        // Step 10: Attendance Policy
        if ($employee->attendance_policy()->exists()) {
            $this->completedSteps[] = 10;
        }

        // Sort steps to maintain order
        sort($this->completedSteps);
    }

    public function handleStepCompleted($step)
    {
        if (!in_array($step, $this->completedSteps)) {
            $this->completedSteps[] = $step;
            sort($this->completedSteps);
        }
    }

    public function handleStepUncompleted($step)
    {
        $this->completedSteps = array_diff($this->completedSteps, [$step]);
        sort($this->completedSteps);
    }

    public function nextStep()
    {
        if ($this->currentStep < $this->totalSteps) {
            $this->currentStep++;
        }
    }

    public function previousStep()
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
        }
    }

    public function goToStep($step)
    {
        if ($step >= 1 && $step <= $this->totalSteps) {
            if (!$this->selectedEmpId && $step > 1) {
                Flux::toast(
                    heading: 'Complete Basic Info First',
                    text: 'Please complete Step 1 before proceeding to other steps.',
                    variant: 'warning'
                );
                return;
            }

            $this->currentStep = $step;
        }
    }

    public function saveEmployee()
    {
        $employee = $this->employeeData['id']
            ? Employee::findOrFail($this->employeeData['id'])
            : null;

        // Figure out which user ID to ignore (null for new)
        $ignoreUserId = $employee
            ? optional($employee->user)->id
            : null;

        $this->validate([
            'employeeData.fname'     => 'required|string|max:255',
            'employeeData.mname'     => 'nullable|string|max:255',
            'employeeData.lname'     => 'nullable|string|max:255',
            'employeeData.email'     => [
                'required','email',
                Rule::unique('users','email')->ignore($ignoreUserId, 'id'),
            ],
            'employeeData.phone'     => 'required|string|max:20',
            'employeeData.gender'    => 'required|in:1,2,3',
        ]);
        
        if ($this->employeeData['id']) {
            // Update existing employee
            $employee = Employee::findOrFail($this->employeeData['id']);
            $employee->update($this->employeeData);

            if ($employee->user) {
                $updates = [];
                $newName = trim($this->employeeData['fname'] . " " . $this->employeeData['lname']);
        
                if ($employee->user->email !== $this->employeeData['email']) {
                    $updates['email'] = $this->employeeData['email'];
                }
        
                if ($employee->user->phone !== $this->employeeData['phone']) {
                    $updates['phone'] = $this->employeeData['phone'];
                }
        
                if ($employee->user->name !== $newName) {
                    $updates['name'] = $newName;
                }
        
                if (!empty($updates)) {
                    $employee->user->update($updates);
                }
            }
            
            $toast = 'Employee updated successfully.';
        } else {
            // Create new employee
            $this->employeeData['firm_id'] = session('firm_id');
            $employee = Employee::create($this->employeeData);

            // Create user account
            $this->createUserAccount($employee);

            $this->selectedEmpId = $employee->id;
            $this->employeeData['id'] = $employee->id;
            $toast = 'Employee added successfully.';
        }

        // Mark step 1 as completed
        $this->handleStepCompleted(1);

        // Emit events to refresh parent components
        $this->dispatch('employee-updated', employeeId: $employee->id);
        $this->dispatch('employee-saved');

        Flux::toast(
            heading: 'Changes saved',
            text: $toast,
        );
        
        // Close the modal
        $this->dispatch('close-modal', 'edit-employee');
    }

    protected function createUserAccount($employee)
    {
        $user = new User();
        $user->name = trim($this->employeeData['fname'] . " " . $this->employeeData['lname']);
        $user->password = bcrypt('iqwing@1947');
        $user->passcode = '1111';
        $user->email = $this->employeeData['email'];
        $user->phone = $this->employeeData['phone'];
        $user->role_main = 'L0_emp';
        $user->save();

        // Assign employee role
        $role = Role::where('name', 'employee')->first();
        if ($role) {
            $user->roles()->sync([
                $role->id => ['firm_id' => session('firm_id')]
            ]);
        }

        // Link user to employee
        $employee->user_id = $user->id;
        $employee->save();

        // Add to firm and panel
        $user->firms()->attach(session('firm_id'), ['is_default' => true]);
        $user->panels()->syncWithoutDetaching([1]);
    }

    public function isStepCompleted($step)
    {
        return in_array($step, $this->completedSteps);
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Onboard/blades/onboard-employees.blade.php'));
    }
}