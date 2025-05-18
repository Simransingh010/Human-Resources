<?php

namespace App\Livewire\Hrms\Onboard;

use App\Models\BatchItem;
use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Hrms\Employee;
use App\Models\User;
use App\Models\Batch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\Settings\Department;
use App\Models\Settings\Designation;
use App\Models\Hrms\EmployeeJobProfile;
use App\Models\Hrms\EmployeeContact;
use App\Models\Hrms\EmployeeBankAccount;
use App\Models\Hrms\EmployeePersonalDetail;

class EmployeeBulkUpload extends Component
{
    use WithFileUploads;

    public $csvFile;
    public $currentBatch;
    public $uploadErrors = [];
    public $uploadSuccess = false;
    public $batches;
    public $selectedBatch = null;
    public $selectedBatchId = null;

    public function mount()
    {
        $this->batches = Batch::where('modulecomponent', 'hrms.onboard.employee-bulk-upload')
            ->where('action', 'bulk_upload')
            ->orderBy('created_at', 'desc')
            ->get();
        $this->currentBatch = $this->batches->first();
    }

    public function selectBatch($batchId)
    {
        $this->selectedBatchId = $batchId;
        $this->selectedBatch = Batch::with('items')->find($batchId);
    }

    public function downloadTemplate()
    {
        $filename = 'employee_template.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() {
            $file = fopen('php://output', 'w');

            fputcsv($file, [
                'First Name',
                'Middle Name',
                'Last Name',
                'Email',
                'Phone',
                'Gender',
                'Department Code',
                'Department Name',
                'Designation Code',
                'Designation Name',
                'Date of Joining',
                'Employee Code',
                'Reporting Manager ID',
                'Employment Type ID',
                'Job Location ID',
                'UAN Number',
                'ESIC Number',
                'Emergency Contact Person',
                'Emergency Contact Relation',
                'Emergency Contact Number',
                'Bank Name',
                'Branch Name',
                'Bank Address',
                'IFSC Code',
                'Bank Account Number',
                'Date of Birth',
                'Marital Status',
                'Date of Anniversary',
                'Nationality',
                'Father Name',
                'Mother Name',
                'Aadhar Number',
                'PAN Number'
            ]);

            fputcsv($file, [
                'John',
                'Middle',
                'Doe',
                'john.doe@example.com',
                '987646464',
                'Male',
                'DEPT001',
                'IT Department',
                'DESG001',
                'Software Engineer',
                '2024-01-01',
                'EMP001',
                'EMP0002',
                '1',
                '1',
                'UAN123456',
                'ESIC123456',
                'Jane Doe',
                'Spouse',
                '9876543210',
                'HDFC Bank',
                'Main Branch',
                '123 Main St',
                'HDFC0001234',
                '1234567890',
                '1990-01-01',
                'Married',
                '2015-01-01',
                'Indian',
                'John Doe Sr',
                'Jane Doe Sr',
                '123456789012',
                'ABCDE1234F'
            ]);

            fclose($file);
        };

        return response()->streamDownload($callback, $filename, $headers);
    }

    public function rollbackBatch()
    {
        if (!$this->currentBatch) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'No batch found to rollback'
            ]);
            return;
        }

        try {
            DB::beginTransaction();

            $items = $this->currentBatch->items()->orderBy('id', 'desc')->get();
            $deletedCount = 0;

            foreach ($items as $item) {
                if ($item->model_type === Employee::class) {
                    // Delete related records first
                    $employee = Employee::where('id', $item->model_id)->first();
                    if ($employee) {
                        // Delete Personal Details
                        EmployeePersonalDetail::where('employee_id', $employee->id)->delete();
                        // Delete Job Profile
                        EmployeeJobProfile::where('employee_id', $employee->id)->delete();
                        // Delete Contacts
                        EmployeeContact::where('employee_id', $employee->id)->delete();
                        // Delete Bank Accounts
                        EmployeeBankAccount::where('employee_id', $employee->id)->delete();
                        // Delete Employee
                        $employee->delete();
                        $deletedCount++;
                    }
                } elseif ($item->model_type === User::class) {
                    User::where('id', $item->model_id)->delete();
                    $deletedCount++;
                } elseif ($item->model_type === EmployeePersonalDetail::class) {
                    EmployeePersonalDetail::where('id', $item->model_id)->delete();
                    $deletedCount++;
                } elseif ($item->model_type === EmployeeJobProfile::class) {
                    EmployeeJobProfile::where('id', $item->model_id)->delete();
                    $deletedCount++;
                } elseif ($item->model_type === EmployeeContact::class) {
                    EmployeeContact::where('id', $item->model_id)->delete();
                    $deletedCount++;
                } elseif ($item->model_type === EmployeeBankAccount::class) {
                    EmployeeBankAccount::where('id', $item->model_id)->delete();
                    $deletedCount++;
                }
            }

            $this->currentBatch->items()->delete();
            $this->currentBatch->delete();

            DB::commit();

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => "Successfully deleted $deletedCount records and rolled back the upload"
            ]);

            $this->currentBatch = null;
            $this->uploadSuccess = false;
            $this->uploadErrors = [];
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Error during rollback: ' . $e->getMessage()
            ]);
        }
    }

    public function rollbackBatchById($batchId)
    {
        $batch = Batch::with('items')->find($batchId);
        if (!$batch) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'No batch found to rollback'
            ]);
            return;
        }

        try {
            DB::beginTransaction();

            $items = $batch->items()->orderBy('id', 'desc')->get();
            $deletedCount = 0;

            foreach ($items as $item) {
                if ($item->model_type === Employee::class) {
                    $employee = Employee::where('id', $item->model_id)->first();
                    if ($employee) {
                        EmployeePersonalDetail::where('employee_id', $employee->id)->delete();
                        EmployeeJobProfile::where('employee_id', $employee->id)->delete();
                        EmployeeContact::where('employee_id', $employee->id)->delete();
                        EmployeeBankAccount::where('employee_id', $employee->id)->delete();
                        $employee->delete();
                        $deletedCount++;
                    }
                } elseif ($item->model_type === User::class) {
                    User::where('id', $item->model_id)->delete();
                    $deletedCount++;
                } elseif ($item->model_type === EmployeePersonalDetail::class) {
                    EmployeePersonalDetail::where('id', $item->model_id)->delete();
                    $deletedCount++;
                } elseif ($item->model_type === EmployeeJobProfile::class) {
                    EmployeeJobProfile::where('id', $item->model_id)->delete();
                    $deletedCount++;
                } elseif ($item->model_type === EmployeeContact::class) {
                    EmployeeContact::where('id', $item->model_id)->delete();
                    $deletedCount++;
                } elseif ($item->model_type === EmployeeBankAccount::class) {
                    EmployeeBankAccount::where('id', $item->model_id)->delete();
                    $deletedCount++;
                }
            }

            $batch->items()->delete();
            $batch->delete();

            DB::commit();

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => "Successfully deleted $deletedCount records and rolled back the upload for Batch ID: $batchId"
            ]);

            // Refresh batches and selection
            $this->batches = Batch::where('modulecomponent', 'hrms.onboard.employee-bulk-upload')
                ->where('action', 'bulk_upload')
                ->orderBy('created_at', 'desc')
                ->get();
            $this->selectedBatch = null;
            $this->currentBatch = $this->batches->first();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Error during rollback: ' . $e->getMessage()
            ]);
        }
    }

    public function uploadEmployees()
    {
        $this->validate([
            'csvFile' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        try {
            $this->uploadErrors = [];
            $this->uploadSuccess = false;

            DB::beginTransaction();

            $batch = new Batch();
            $batch->firm_id = session('firm_id');
            $batch->user_id = auth()->id();
            $batch->modulecomponent = 'hrms.onboard.employee-bulk-upload';
            $batch->action = 'bulk_upload';
            $batch->title = 'Bulk Upload Employees';
            $batch->save();

            $file = fopen($this->csvFile->getRealPath(), 'r');
            fgetcsv($file);

            $successCount = 0;
            $errorCount = 0;
            $errors = [];
            $rowNumber = 2;

            while (($row = fgetcsv($file)) !== false) {
                try {
                    if (count($row) < 33) {
                        throw new \Exception("Invalid number of columns. Expected 33 columns.");
                    }

                    // Check for duplicate email
                    if (User::where('email', $row[3])->exists()) {
                        throw new \Exception("Email already exists");
                    }

                    // Handle Department
                    $department = Department::where('code', $row[6])
                        ->where('firm_id', session('firm_id'))
                        ->first();
                    
                    if (!$department) {
                        // Check if department exists in any firm
                        $existingDepartment = Department::where('code', $row[6])->first();
                        if ($existingDepartment) {
                            // If exists in another firm, use that department
                            $department = $existingDepartment;
                        } else {
                            // If doesn't exist anywhere, create with original code
                            $department = Department::create([
                                'firm_id' => session('firm_id'),
                                'code' => $row[6],
                                'title' => $row[7],
                                'is_inactive' => false
                            ]);
                        }
                    }

                    // Handle Designation
                    $designation = Designation::where('code', $row[8])
                        ->where('firm_id', session('firm_id'))
                        ->first();
                    
                    if (!$designation) {
                        // Check if designation exists in any firm
                        $existingDesignation = Designation::where('code', $row[8])->first();
                        if ($existingDesignation) {
                            // If exists in another firm, use that designation
                            $designation = $existingDesignation;
                        } else {
                            // If doesn't exist anywhere, create with original code
                            $designation = Designation::create([
                                'firm_id' => session('firm_id'),
                                'code' => $row[8],
                                'title' => $row[9],
                                'is_inactive' => false
                            ]);
                        }
                    }

                    // Get Reporting Manager ID
                    $reportingManagerId = null;
                    if (!empty($row[12])) {
                        $reportingManager = Employee::where('id', $row[12])->first();
                        if ($reportingManager) {
                            $reportingManagerId = $reportingManager->id;
                        }
                    }

                    // Handle Employment Type
                    $employmentType = null;
                    if (!empty($row[13])) {
                        $employmentType = \App\Models\Settings\EmploymentType::where('code', $row[13])
                            ->where('firm_id', session('firm_id'))
                            ->first();

                        if (!$employmentType) {
                            // Check if employment type exists in any firm
                            $existingEmploymentType = \App\Models\Settings\EmploymentType::where('code', $row[13])->first();
                            if ($existingEmploymentType) {
                                // If exists in another firm, use that employment type
                                $employmentType = $existingEmploymentType;

                            } else {
                                // If doesn't exist anywhere, create with original code
                                $employmentType = \App\Models\Settings\EmploymentType::create([
                                    'firm_id' => session('firm_id'),
                                    'code' => $row[13],
                                    'title' => $row[13], // Using code as title since we don't have a separate title column
                                    'is_inactive' => false
                                ]);

                            }

                        }
                    }

                    // Handle Job Location
//                    $jobLocation = null;
//                    if (!empty($row[14])) {
//                        $jobLocation = \App\Models\Settings\Joblocation::where('code', $row[14])
//                            ->where('firm_id', session('firm_id'))
//                            ->first();
//
//                        if (!$jobLocation) {
//                            // Check if job location exists in any firm
//                            $existingJobLocation = \App\Models\Settings\Joblocation::where('code', $row[14])->first();
//                            if ($existingJobLocation) {
//                                // If exists in another firm, use that job location
//                                $jobLocation = $existingJobLocation;
//                            } else {
//                                // If doesn't exist anywhere, create with original code
//                                $jobLocation = \App\Models\Settings\Joblocation::create([
//                                    'firm_id' => session('firm_id'),
//                                    'code' => $row[14],
//                                    'title' => $row[14], // Using code as title since we don't have a separate title column
//                                    'is_inactive' => false
//                                ]);
//                            }
//                        }
//                    }

                    // Create User
                    $user = User::create([
                        'name' => $row[0] . ' ' . $row[2],
                        'email' => $row[3],
                        'password' => Hash::make('password'),
                        'phone' => $row[4],
                        'passcode' => Hash::make('password'),
                    ]);

                    BatchItem::create([
                        'batch_id' => $batch->id,
                        'operation' => 'insert',
                        'model_type' => User::class,
                        'model_id' => $user->id,
                        'new_data' => json_encode($user->toArray())
                    ]);
                    // Create Employee
                    $employee = Employee::create([
                        'fname' => $row[0],
                        'mname' => $row[1],
                        'lname' => $row[2],
                        'email' => $row[3],
                        'phone' => $row[4],
                        'gender' => strtolower($row[5]) === 'male' ? 1 : (strtolower($row[5]) === 'female' ? 2 : 3),
                        'user_id' => $user->id,
                        'firm_id' => session('firm_id'),
                    ]);

                    BatchItem::create([
                        'batch_id' => $batch->id,
                        'operation' => 'insert',
                        'model_type' => Employee::class,
                        'model_id' => $employee->id,
                        'new_data' => json_encode($employee->toArray())
                    ]);

                    // Create Personal Details
                    $personalDetail = EmployeePersonalDetail::create([
                        'firm_id' => session('firm_id'),
                        'employee_id' => $employee->id,
                        'dob' => $row[25],
                        'marital_status' => $row[26],
                        'doa' => $row[27],
                        'nationality' => $row[28],
                        'fathername' => $row[29],
                        'mothername' => $row[30],
                        'adharno' => $row[31],
                        'panno' => $row[32]
                    ]);

                    BatchItem::create([
                        'batch_id' => $batch->id,
                        'operation' => 'insert',
                        'model_type' => EmployeePersonalDetail::class,
                        'model_id' => $personalDetail->id,
                        'new_data' => json_encode($personalDetail->toArray())
                    ]);

                    // Create Job Profile
                    $jobProfile = EmployeeJobProfile::create([
                        'firm_id' => session('firm_id'),
                        'employee_id' => $employee->id,
                        'employee_code' => $row[11],
                        'doh' => $row[10],
                        'department_id' => $department->id,
                        'designation_id' => $designation->id,
                        'reporting_manager' => $reportingManagerId,
                        'employment_type_id' => $employmentType ? $employmentType->id : null,
                        'joblocation_id' => null,
                        'uanno' => $row[15],
                        'esicno' => $row[16]
                    ]);

                    BatchItem::create([
                        'batch_id' => $batch->id,
                        'operation' => 'insert',
                        'model_type' => EmployeeJobProfile::class,
                        'model_id' => $jobProfile->id,
                        'new_data' => json_encode($jobProfile->toArray())
                    ]);

                    // Create Emergency Contact


                    $emergencyContact = EmployeeContact::create([
                        'firm_id' => session('firm_id'),
                        'employee_id' => $employee->id,
                        'contact_type' => 'phone',
                        'contact_value' => $row[19],
                        'contact_person' => $row[17],
                        'relation' => $row[18],
                        'is_primary' => false,
                        'is_for_emergency' => true,
                        'is_inactive' => false
                    ]);

                    BatchItem::create([
                        'batch_id' => $batch->id,
                        'operation' => 'insert',
                        'model_type' => EmployeeContact::class,
                        'model_id' => $emergencyContact->id,
                        'new_data' => json_encode($emergencyContact->toArray())
                    ]);

                    // Create Bank Account
                    $bankAccount = EmployeeBankAccount::create([
                        'firm_id' => session('firm_id'),
                        'employee_id' => $employee->id,
                        'bank_name' => $row[20],
                        'branch_name' => $row[21],
                        'address' => $row[22],
                        'ifsc' => $row[23],
                        'bankaccount' => $row[24],
                        'is_primary' => true,
                        'is_inactive' => false
                    ]);

                    BatchItem::create([
                        'batch_id' => $batch->id,
                        'operation' => 'insert',
                        'model_type' => EmployeeBankAccount::class,
                        'model_id' => $bankAccount->id,
                        'new_data' => json_encode($bankAccount->toArray())
                    ]);

                    $successCount++;
                } catch (\Exception $e) {
                    $errorCount++;
                    $errors[] = "Row $rowNumber: " . implode(',', $row) . " - " . $e->getMessage();
                    throw $e; // Re-throw the exception to trigger rollback
                }
                $rowNumber++;
            }

            fclose($file);

            if ($errorCount > 0) {
                DB::rollBack();
                $this->uploadErrors = $errors;
                $this->dispatch('notify', [
                    'type' => 'error',
                    'message' => "Failed to import $errorCount records. Please check the errors below."
                ]);
            } else {
                DB::commit();
                $this->currentBatch = $batch;
                $this->uploadSuccess = true;
                $this->dispatch('notify', [
                    'type' => 'success',
                    'message' => "Successfully imported $successCount employees."
                ]);
            }

        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Onboard/blades/employee-bulk-upload.blade.php'));
    }
}
