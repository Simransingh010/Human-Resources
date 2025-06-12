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
use App\Models\Hrms\EmpPunch;
use App\Models\Hrms\EmpWorkShift;
use App\Models\Hrms\EmpAttendance;
use App\Models\Hrms\EmpLeaveAllocation;

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
    public $showBatchDetails = false;
    public $showRollbackConfirmation = false;

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
        $this->dispatch('open-modal', 'batch-details-modal');
    }

    public function confirmRollback($batchId)
    {
        $this->selectedBatchId = $batchId;
        $this->dispatch('open-modal', 'rollback-confirmation-modal');
    }

    public function closeBatchDetails()
    {
        $this->dispatch('close-modal', 'batch-details-modal');
        $this->selectedBatch = null;
        $this->selectedBatchId = null;
    }

    public function closeRollbackConfirmation()
    {
        $this->dispatch('close-modal', 'rollback-confirmation-modal');
        $this->selectedBatchId = null;
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

            // Headers with required fields marked
            fputcsv($file, [
                'First Name*', // Required
                'Middle Name',
                'Last Name',
                'Email*', // Required
                'Phone*', // Required
                'Gender',
                'Date of Birth',
                'Marital Status',
                'Nationality',
                'Aadhar Number',
                'PAN Number',
                'Department Name*', // Required
                'Designation Name*', // Required
                'Date of Joining',
                'Father Name',
                'Mother Name',
                'Employee Code*', // Required
                'Employment Type',
                'Bank Name',
                'IFSC Code',
                'Bank Account Number',
            ]);

            // Sample data row
            fputcsv($file, [
                'John', // First Name (Required)
                'Middle',
                'Doe',
                'john.doe@example.com', // Email (Required)
                '9876543210', // Phone (Required)
                'Male',
                '1990-01-01',
                'Married',
                'Indian',
                '123456789012',
                'ABCDE1234F',
                'IT Department', // Department Name (Required)
                'Software Engineer', // Designation Name (Required)
                '2024-01-01',
                'John Doe Sr',
                'Jane Doe Sr',
                'EMP001', // Employee Code (Required)
                'Full Time',
                'HDFC Bank',
                'HDFC0001234',
                '1234567890',
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
                elseif ($item->model_type === Department::class){
                    Department::where('id', $item->model_id)->delete();
                    $deletedCount++;
                }
                elseif ($item->model_type === Designation::class){
                    Designation::where('id', $item->model_id)->delete();
                    $deletedCount++;
                }
                elseif ($item->model_type === EmploymentType::class){
                    EmploymentType::where('id', $item->model_id)->delete();
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

    protected function hasRelatedRecords($batch)
    {
        $employeeIds = $batch->items()
            ->where('model_type', Employee::class)
            ->pluck('model_id')
            ->toArray();

        if (empty($employeeIds)) {
            return false;
        }

        // Single query to check all related records
        $hasRelatedRecords = DB::table('employees as e')
            ->whereIn('e.id', $employeeIds)
            ->where(function ($query) {
                $query->whereExists(function ($subquery) {
                    $subquery->select(DB::raw(1))
                        ->from('employee_personal_details')
                        ->whereRaw('employee_personal_details.employee_id = e.id');
                })
                ->orWhereExists(function ($subquery) {
                    $subquery->select(DB::raw(1))
                        ->from('employee_job_profiles')
                        ->whereRaw('employee_job_profiles.employee_id = e.id');
                })
                ->orWhereExists(function ($subquery) {
                    $subquery->select(DB::raw(1))
                        ->from('employee_contacts')
                        ->whereRaw('employee_contacts.employee_id = e.id');
                })
                ->orWhereExists(function ($subquery) {
                    $subquery->select(DB::raw(1))
                        ->from('employee_bank_accounts')
                        ->whereRaw('employee_bank_accounts.employee_id = e.id');
                })
                ->orWhereExists(function ($subquery) {
                    $subquery->select(DB::raw(1))
                        ->from('emp_punches')
                        ->whereRaw('emp_punches.employee_id = e.id');
                })
                ->orWhereExists(function ($subquery) {
                    $subquery->select(DB::raw(1))
                        ->from('emp_work_shifts')
                        ->whereRaw('emp_work_shifts.employee_id = e.id');
                })
                ->orWhereExists(function ($subquery) {
                    $subquery->select(DB::raw(1))
                        ->from('emp_attendances')
                        ->whereRaw('emp_attendances.employee_id = e.id');
                })
                ->orWhereExists(function ($subquery) {
                    $subquery->select(DB::raw(1))
                        ->from('emp_leave_allocations')
                        ->whereRaw('emp_leave_allocations.employee_id = e.id');
                });
            })
            ->exists();

        return $hasRelatedRecords;
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

        // Check for related records before proceeding
        if ($this->hasRelatedRecords($batch)) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Cannot rollback: Related records exist for employees in this batch. Please contact administrator.'
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
                        // Delete only if no related records exist
                        if (!$this->hasRelatedRecords($batch)) {
                            $employee->delete();
                            $deletedCount++;
                        }
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
        set_time_limit(300);
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
            if (!$file) {
                throw new \Exception("Could not open the CSV file. Please check if the file is valid.");
            }

            $headers = fgetcsv($file); // Get header row
            if (!$headers) {
                throw new \Exception("The CSV file appears to be empty or invalid.");
            }

            $successCount = 0;
            $errorCount = 0;
            $errors = [];
            $rowNumber = 2;
            $phoneCounter = 1;

            while (($row = fgetcsv($file)) !== false) {
                try {
                    // Check if row has enough columns
                    if (count($row) < 17) {
                        throw new \Exception("Row has insufficient columns. Expected at least 17 columns.");
                    }

                    // Validate required fields with specific messages
                    if (empty($row[0])) throw new \Exception("First Name is required");
                    if (empty($row[3])) throw new \Exception("Email is required");
                    if (empty($row[11])) throw new \Exception("Department Name is required");
                    if (empty($row[12])) throw new \Exception("Designation Name is required");
                    if (empty($row[16])) throw new \Exception("Employee Code is required");

                    // Validate email format
                    if (!filter_var($row[3], FILTER_VALIDATE_EMAIL)) {
                        throw new \Exception("Invalid email format: " . $row[3]);
                    }

                    // Check for duplicate email
//                    if (User::where('email', $row[3])->exists()) {
//                        throw new \Exception("Email already exists in the system: " . $row[3]);
//                    }

                    // Check for duplicate employee code
                    if (EmployeeJobProfile::where('employee_code', $row[16])->where('firm_id', session('firm_id'))->exists()) {
                        throw new \Exception("Employee Code already exists in the system: " . $row[16]);
                    }

                    // Generate dummy phone number if not provided
                    $phoneNumber = !empty($row[4]) ? $row[4] : '123456' . str_pad($phoneCounter++, 4, '0', STR_PAD_LEFT);

                    // Validate phone number format if provided
                    if (!empty($row[4]) && !preg_match('/^\d{10}$/', $row[4])) {
                        throw new \Exception("Invalid phone number format. Must be 10 digits: " . $row[4]);
                    }

                    // Handle Department
                    $departmentTitle = $row[11];
                    // Create a clean code from title
                    $baseCode = preg_replace('/[^a-zA-Z0-9\s]/', '', $departmentTitle);
                    $baseCode = str_replace(' ', '_', trim($baseCode));
                    $baseCode = strtoupper($baseCode);

                    // Make code unique by adding firm_id
                    $departmentCode = 'F' . session('firm_id') . '_' . $baseCode;

                    // Check if department exists in current firm
                    $department = Department::where('firm_id', session('firm_id'))
                        ->where('title', $departmentTitle)
                        ->first();

                    if (!$department) {
                        try {
                            $departmentData = [
                                'firm_id' => session('firm_id'),
                                'code' => $departmentCode,
                                'title' => $departmentTitle,
                                'is_inactive' => false
                            ];
                            
                            $department = Department::create($departmentData);
                            
                            // Track department creation in batch
                            BatchItem::create([
                                'batch_id' => $batch->id,
                                'operation' => 'insert',
                                'model_type' => Department::class,
                                'model_id' => $department->id,
                                'new_data' => json_encode($departmentData)
                            ]);
                        } catch (\Exception $e) {
                            \Log::error('Department creation failed: ' . $e->getMessage());
                            throw new \Exception("Failed to create department '{$departmentTitle}': " . $e->getMessage());
                        }
                    }

                    // Handle Designation
                    $designationTitle = $row[12];
                    // Create a clean code from title
                    $baseCode = preg_replace('/[^a-zA-Z0-9\s]/', '', $designationTitle);
                    $baseCode = str_replace(' ', '_', trim($baseCode));
                    $baseCode = strtoupper($baseCode);

                    // Make code unique by adding firm_id
                    $designationCode = 'F' . session('firm_id') . '_' . $baseCode;

                    // Check if designation exists in current firm
                    $designation = Designation::where('firm_id', session('firm_id'))
                        ->where('title', $designationTitle)
                        ->first();

                    if (!$designation) {
                        try {
                            $designationData = [
                                'firm_id' => session('firm_id'),
                                'code' => $designationCode,
                                'title' => $designationTitle,
                                'is_inactive' => false
                            ];
                            
                            $designation = Designation::create($designationData);
                            
                            // Track designation creation in batch
                            BatchItem::create([
                                'batch_id' => $batch->id,
                                'operation' => 'insert',
                                'model_type' => Designation::class,
                                'model_id' => $designation->id,
                                'new_data' => json_encode($designationData)
                            ]);
                        } catch (\Exception $e) {
                            \Log::error('Designation creation failed: ' . $e->getMessage());
                            throw new \Exception("Failed to create designation '{$designationTitle}': " . $e->getMessage());
                        }
                    }

                    // Handle Employment Type
                    if (!empty($row[17])) {
                        $employmentTypeTitle = $row[17];
                        // Create a clean code from title
                        $baseCode = preg_replace('/[^a-zA-Z0-9\s]/', '', $employmentTypeTitle);
                        $baseCode = str_replace(' ', '_', trim($baseCode));
                        $baseCode = strtoupper($baseCode);

                        // Make code unique by adding firm_id
                        $employmentTypeCode = 'F' . session('firm_id') . '_' . $baseCode;

                        // Check if employment type exists in current firm
                        $employmentType = \App\Models\Settings\EmploymentType::where('firm_id', session('firm_id'))
                            ->where('title', $employmentTypeTitle)
                            ->first();

                        if (!$employmentType) {
                            try {
                                $employmentTypeData = [
                                    'firm_id' => session('firm_id'),
                                    'code' => $employmentTypeCode,
                                    'title' => $employmentTypeTitle,
                                    'description' => 'Created from bulk upload',
                                    'is_inactive' => false
                                ];
                                
                                $employmentType = \App\Models\Settings\EmploymentType::create($employmentTypeData);
                                
                                // Track employment type creation in batch
                                BatchItem::create([
                                    'batch_id' => $batch->id,
                                    'operation' => 'insert',
                                    'model_type' => \App\Models\Settings\EmploymentType::class,
                                    'model_id' => $employmentType->id,
                                    'new_data' => json_encode($employmentTypeData)
                                ]);
                            } catch (\Exception $e) {
                                \Log::error('Employment Type creation failed: ' . $e->getMessage());
                                throw new \Exception("Failed to create employment type '{$employmentTypeTitle}': " . $e->getMessage());
                            }
                        }
                    }

                    // Create User with the phone number (real or dummy)
                    try {
                        $user = User::where('email',$row[3])->first();
                        if(!$user)
                        {
                            $user = User::create([
                                'name' => $row[0] . ' ' . ($row[2] ?? ''),
                                'email' => $row[3],
                                'password' => Hash::make('password'),
                                'phone' => $phoneNumber,
                                'passcode' => Hash::make('password'),
                            ]);
                        }

                    } catch (\Exception $e) {
                        throw new \Exception("Failed to create user: " . $e->getMessage());
                    }

                    BatchItem::create([
                        'batch_id' => $batch->id,
                        'operation' => 'insert',
                        'model_type' => User::class,
                        'model_id' => $user->id,
                        'new_data' => json_encode($user->toArray())
                    ]);

                    // Create Employee with the phone number (real or dummy)
                    try {
                        $employee = Employee::create([
                            'fname' => $row[0],
                            'mname' => $row[1] ?? null,
                            'lname' => $row[2] ?? null,
                            'email' => $row[3],
                            'phone' => $phoneNumber,
                            'gender' => !empty($row[5]) ? (strtolower($row[5]) === 'male' ? 1 : (strtolower($row[5]) === 'female' ? 2 : 3)) : null,
                            'user_id' => $user->id,
                            'firm_id' => session('firm_id'),
                        ]);
                    } catch (\Exception $e) {
                        throw new \Exception("Failed to create employee: " . $e->getMessage());
                    }

                    BatchItem::create([
                        'batch_id' => $batch->id,
                        'operation' => 'insert',
                        'model_type' => Employee::class,
                        'model_id' => $employee->id,
                        'new_data' => json_encode($employee->toArray())
                    ]);

                    // Create Personal Details if any optional fields are provided
                    if (!empty($row[6]) || !empty($row[7]) || !empty($row[8]) || !empty($row[9]) || !empty($row[10])) {
                        $personalDetail = EmployeePersonalDetail::create([
                            'firm_id' => session('firm_id'),
                            'employee_id' => $employee->id,
                            'dob' => $row[6] ?? null,
                            'marital_status' => $row[7] ?? null,
                            'nationality' => $row[8] ?? null,
                            'adharno' => $row[9] ?? null,
                            'panno' => $row[10] ?? null,
                        ]);

                        BatchItem::create([
                            'batch_id' => $batch->id,
                            'operation' => 'insert',
                            'model_type' => EmployeePersonalDetail::class,
                            'model_id' => $personalDetail->id,
                            'new_data' => json_encode($personalDetail->toArray())
                        ]);
                    }

                    // Create Job Profile with employment type
                    $jobProfileData = [
                        'firm_id' => session('firm_id'),
                        'employee_id' => $employee->id,
                        'employee_code' => $row[16],
                        'doh' => $row[13] ?? null,
                        'department_id' => $department->id,
                        'designation_id' => $designation->id,
                        'employment_type_id' => isset($employmentType) ? $employmentType->id : null,
                    ];

                    $jobProfile = EmployeeJobProfile::create($jobProfileData);

                    BatchItem::create([
                        'batch_id' => $batch->id,
                        'operation' => 'insert',
                        'model_type' => EmployeeJobProfile::class,
                        'model_id' => $jobProfile->id,
                        'new_data' => json_encode($jobProfile->toArray())
                    ]);

                    // Create Bank Account if provided
                    if (!empty($row[18]) || !empty($row[19]) || !empty($row[20])) {
                        $bankAccount = EmployeeBankAccount::create([
                            'firm_id' => session('firm_id'),
                            'employee_id' => $employee->id,
                            'bank_name' => $row[18] ?? null,
                            'branch_name' => $row[18] ?? 'Main Branch',
                            'ifsc' => $row[19] ?? null,
                            'bankaccount' => $row[20] ?? null,
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
                    }

                    $successCount++;
                } catch (\Exception $e) {
                    $errorCount++;
                    $errors[] = "Row $rowNumber: " . $e->getMessage();
                    throw $e;
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
            $this->uploadErrors = array_merge($this->uploadErrors, [$e->getMessage()]);
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => "Upload failed: " . $e->getMessage()
            ]);
        }
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Onboard/blades/employee-bulk-upload.blade.php'));
    }
}
