<?php

namespace App\Livewire\Hrms\Onboard;

use App\Models\BatchItem;
use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Hrms\Student;
use App\Models\User;
use App\Models\Batch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\Hrms\StudentPersonalDetail;
use App\Models\Hrms\StudentEducationDetail;
use App\Models\Hrms\StudentAttendance;
use App\Models\Hrms\StudentPunch;
use App\Models\Hrms\StudyCentre;
use App\Models\Saas\Role;

class StudentBulkUpload extends Component
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
    
    // Performance tracking
    public $totalSuccess = 0;
    public $totalErrors = 0;
    private $errorMessages = [];
    
    // Progress tracking for UI
    public $isUploading = false;
    public $currentProgress = 0;
    public $totalRecords = 0;
    public $currentStudentName = '';
    public $processedCount = 0;
    public $estimatedTimeRemaining = 0;
    public $startTime = 0;

    public function mount()
    {
        $this->loadBatches();
    }

    private function loadBatches()
    {
        $this->batches = Batch::where('modulecomponent', 'hrms.onboard.student-bulk-upload')
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
        $filename = 'student_template.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() {
            $file = fopen('php://output', 'w');
            $this->writeTemplateHeaders($file);
            $this->writeTemplateSampleRow($file);
            fclose($file);
        };

        return response()->streamDownload($callback, $filename, $headers);
    }

    private function writeTemplateHeaders($file)
    {
        fputcsv($file, [
            'Player Name*',
            'Gender*',
            'DOB',
            'Mobile No*',
            'Aadhar*',
            'Father Name*',
        ]);
    }

    private function writeTemplateSampleRow($file)
    {
        fputcsv($file, [
            'John Middle Doe',
            'Male',
            '2000-01-15',
            '9876543210',
            '123456789012',
            'John Doe Sr',
        ]);
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

        $this->executeRollback($this->currentBatch);
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

        if ($this->hasRelatedRecords($batch)) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Cannot rollback: Related records exist for students in this batch. Please contact administrator.'
            ]);
            return;
        }

        $this->executeRollback($batch);
    }

    private function executeRollback($batch)
    {
        try {
            DB::beginTransaction();
            $deletedCount = $this->deleteBatchItems($batch);
            $this->deleteBatch($batch);
            DB::commit();

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => "Successfully deleted $deletedCount records and rolled back the upload"
            ]);

            $this->resetUploadState();
            $this->loadBatches();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Error during rollback: ' . $e->getMessage()
            ]);
        }
    }

    private function deleteBatchItems($batch)
    {
        $items = $batch->items()->orderBy('id', 'desc')->get();
        $deletedCount = 0;

        foreach ($items as $item) {
            $deletedCount += $this->deleteItemByType($item);
        }

        return $deletedCount;
    }

    private function deleteItemByType($item)
    {
        if ($item->model_type === Student::class) {
            return $this->deleteStudentRecord($item);
        }

        return $this->deleteStandardRecord($item);
    }

    private function deleteStudentRecord($item)
    {
        $student = Student::where('id', $item->model_id)->first();
        if (!$student) {
            return 0;
        }

        $this->deleteStudentRelatedRecords($student);
        $student->delete();
        return 1;
    }

    private function deleteStudentRelatedRecords($student)
    {
        StudentPersonalDetail::where('student_id', $student->id)->delete();
        StudentEducationDetail::where('student_id', $student->id)->delete();
        
        if ($student->user_id) {
            User::where('id', $student->user_id)->delete();
        }
    }

    private function deleteStandardRecord($item)
    {
        if ($item->model_type === User::class) {
            User::where('id', $item->model_id)->delete();
            return 1;
        }

        if ($item->model_type === StudentPersonalDetail::class) {
            StudentPersonalDetail::where('id', $item->model_id)->delete();
            return 1;
        }

        if ($item->model_type === StudentEducationDetail::class) {
            StudentEducationDetail::where('id', $item->model_id)->delete();
            return 1;
        }

        if ($item->model_type === StudyCentre::class) {
            StudyCentre::where('id', $item->model_id)->delete();
            return 1;
        }

        return 0;
    }

    private function deleteBatch($batch)
    {
        $batch->items()->delete();
        $batch->delete();
    }

    private function resetUploadState()
    {
        $this->currentBatch = null;
        $this->uploadSuccess = false;
        $this->uploadErrors = [];
    }

    protected function hasRelatedRecords($batch)
    {
        $studentIds = $batch->items()
            ->where('model_type', Student::class)
            ->pluck('model_id')
            ->toArray();

        if (empty($studentIds)) {
            return false;
        }

        return $this->checkStudentRelatedRecords($studentIds);
    }

    private function checkStudentRelatedRecords($studentIds)
    {
        return DB::table('students as s')
            ->whereIn('s.id', $studentIds)
            ->where(function ($query) {
                $query->whereExists(function ($subquery) {
                    $subquery->select(DB::raw(1))
                        ->from('student_attendances')
                        ->whereRaw('student_attendances.student_id = s.id');
                })
                ->orWhereExists(function ($subquery) {
                    $subquery->select(DB::raw(1))
                        ->from('student_punches')
                        ->whereRaw('student_punches.student_id = s.id');
                });
            })
            ->exists();
    }

    public function uploadStudents()
    {
        // Increase limits for large uploads
        set_time_limit(600); // 10 minutes
        ini_set('memory_limit', '512M'); // Increase memory limit
        
        $this->validate([
            'csvFile' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        try {
            $this->initializeUpload();
            $batch = $this->createBatch();
            
            // Count total records first
            $this->countTotalRecords();
            
            // Start progress tracking
            $this->isUploading = true;
            $this->startTime = microtime(true);
            $this->processedCount = 0;
            $this->currentProgress = 0;
            
            $result = $this->processCsvFile($batch);
            $this->finalizeUpload($batch, $result);
        } catch (\Exception $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            $this->isUploading = false;
            $this->handleUploadError($e);
        } catch (\Throwable $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            $this->isUploading = false;
            $this->handleUploadError($e);
        } finally {
            $this->isUploading = false;
        }
    }
    
    private function countTotalRecords()
    {
        $file = fopen($this->csvFile->getRealPath(), 'r');
        if (!$file) {
            return;
        }
        
        // Skip header
        fgetcsv($file);
        
        $count = 0;
        while (($row = fgetcsv($file)) !== false) {
            if (!empty(array_filter($row))) {
                $count++;
            }
        }
        
        fclose($file);
        $this->totalRecords = $count;
    }
    
    private function initializeUpload()
    {
        $this->uploadErrors = [];
        $this->uploadSuccess = false;
        $this->totalSuccess = 0;
        $this->totalErrors = 0;
        $this->errorMessages = [];
    }


    private function createBatch()
    {
        $batch = new Batch();
        $batch->firm_id = session('firm_id');
        $batch->user_id = auth()->id();
        $batch->modulecomponent = 'hrms.onboard.student-bulk-upload';
        $batch->action = 'bulk_upload';
        $batch->title = 'Bulk Upload Students';
        $batch->save();
        return $batch;
    }

    private function processCsvFile($batch)
    {
        $file = fopen($this->csvFile->getRealPath(), 'r');
        if (!$file) {
            throw new \Exception("Could not open the CSV file. Please check if the file is valid.");
        }

        $headers = fgetcsv($file);
        if (!$headers) {
            throw new \Exception("The CSV file appears to be empty or invalid.");
        }

        return $this->processCsvRows($file, $batch);
    }

    private function processCsvRows($file, $batch)
    {
        $rows = [];
        $rowNumber = 2;

        // Cache values once - major performance improvement
        $studentRole = Role::where('name', 'student')->first();
        $studentRoleId = $studentRole ? $studentRole->id : null;
        $firmId = session('firm_id');
        
        if (!$firmId) {
            throw new \Exception("Firm ID not found in session");
        }

        // Pre-hash password once instead of per record
        $hashedPassword = Hash::make('password');
        $hashedPasscode = Hash::make('password');
        $now = now();

        while (($row = fgetcsv($file)) !== false) {
            // Skip empty rows
            if (empty(array_filter($row))) {
                $rowNumber++;
                continue;
            }
            
            $rows[] = ['data' => $row, 'rowNumber' => $rowNumber];

            // Process in chunks of 300 for optimal performance
            if (count($rows) >= 300) {
                $this->processChunk($rows, $batch, $studentRoleId, $firmId, $hashedPassword, $hashedPasscode, $now);
                $rows = [];
            }

            $rowNumber++;
        }

        // Process remaining rows
        if (count($rows) > 0) {
            $this->processChunk($rows, $batch, $studentRoleId, $firmId, $hashedPassword, $hashedPasscode, $now);
        }

        fclose($file);

        return [
            'successCount' => $this->totalSuccess,
            'errorCount' => $this->totalErrors,
            'errors' => $this->errorMessages
        ];
    }

    private function processChunk(array $rows, $batch, $studentRoleId, $firmId, $hashedPassword, $hashedPasscode, $now)
    {
        DB::beginTransaction();
        try {
            $usersToInsert = [];
            $studentsToInsert = [];
            $personalDetailsToInsert = [];
            $roleUserPivots = [];
            $batchItemsToInsert = [];
            $phonesToCheck = [];
            $phoneToIndexMap = []; // Track phone to index mapping for deduplication

            $studentNames = [];
            $validIndices = []; // Track which rows are valid
            
            foreach ($rows as $index => $rowData) {
                $row = $rowData['data'];
                $rowNumber = $rowData['rowNumber'];
                
                try {
                    // Ensure row has proper structure - pad to 6 elements
                    $row = array_pad($row, 6, '');
                    
                    // Validate row
                    $this->validateRow($row);
                    
                    // Get phone number
                    $phone = trim($row[3] ?? '');
                    
                    // Skip if phone already exists in this chunk (deduplicate within chunk)
                    if (!empty($phone) && isset($phoneToIndexMap[$phone])) {
                        $this->totalErrors++;
                        $this->errorMessages[] = "Row $rowNumber: Duplicate phone number '$phone' found in the same upload batch. Skipping duplicate.";
                        continue;
                    }
                    
                    // Track phone number
                    if (!empty($phone)) {
                        $phoneToIndexMap[$phone] = $index;
                        $phonesToCheck[] = $phone;
                    }
                    
                    // Get student name for display
                    $nameParts = $this->splitPlayerName($row[0] ?? '');
                    $studentName = trim(implode(' ', array_filter($nameParts)));
                    $studentNames[] = $studentName;
                    
                    // Prepare data for bulk insert
                    $userData = $this->prepareUserDataForBulk($row, $hashedPassword, $hashedPasscode, $now);
                    $studentData = $this->prepareStudentDataForBulk($row, $userData, $firmId, $now);
                    $personalDetailData = $this->preparePersonalDetailDataForBulk($row, $now);
                    
                    $usersToInsert[] = $userData;
                    $studentsToInsert[] = $studentData;
                    $personalDetailsToInsert[] = $personalDetailData;
                    $validIndices[] = $index; // Track valid row index
                    
                    if ($studentRoleId) {
                        $roleUserPivots[] = [
                            'user_id' => null, // Will be set after user insert
                            'role_id' => $studentRoleId,
                            'firm_id' => $firmId
                        ];
                    }
                    
                    $this->totalSuccess++;
                } catch (\Exception $e) {
                    $this->totalErrors++;
                    $this->errorMessages[] = "Row $rowNumber: " . $e->getMessage();
                }
            }

            // Check for existing users by phone and delete old student data
            if (count($phonesToCheck) > 0) {
                // Remove duplicates from phonesToCheck array
                $phonesToCheck = array_unique($phonesToCheck);
                $this->deleteExistingStudentsByPhone($phonesToCheck, $firmId);
            }

            // Bulk insert/update users
            if (count($usersToInsert) > 0) {
                $userPhoneMap = $this->insertOrUpdateUsers($usersToInsert);
                
                // Get all users in the correct order based on phones
                $phones = array_column($usersToInsert, 'phone');
                $insertedUsers = collect($phones)->map(function($phone) use ($userPhoneMap) {
                    return $userPhoneMap[$phone] ?? null;
                })->filter()->values();
                
                // Update student data with user_ids and track valid indices
                $validStudentIndices = [];
                foreach ($studentsToInsert as $index => &$studentData) {
                    if (isset($insertedUsers[$index])) {
                        $studentData['user_id'] = $insertedUsers[$index]->id;
                        $validStudentIndices[] = $index; // Track which indices are valid
                    }
                }
                
                // Filter out students without valid user_ids
                $validStudentsToInsert = array_filter($studentsToInsert, function($student) {
                    return !empty($student['user_id']);
                });
                $validStudentsToInsert = array_values($validStudentsToInsert); // Re-index array
                
                // Bulk insert students (only if we have valid students)
                if (count($validStudentsToInsert) > 0) {
                    Student::insert($validStudentsToInsert);
                    
                    // Get inserted student IDs by user_id
                    $userIds = array_column($validStudentsToInsert, 'user_id');
                    $insertedStudents = Student::whereIn('user_id', $userIds)->get();
                    // Sort by insertion order
                    $insertedStudents = $insertedStudents->sortBy(function($student) use ($userIds) {
                        return array_search($student->user_id, $userIds);
                    })->values();
                } else {
                    $insertedStudents = collect();
                }
                
                // Build phone -> user_id map from userPhoneMap
                $userIdByPhone = [];
                foreach ($userPhoneMap as $phoneKey => $userObj) {
                    if ($userObj) {
                        $userIdByPhone[$phoneKey] = $userObj->id;
                    }
                }
                // Build user_id -> student_id map from inserted students
                $studentIdByUserId = [];
                foreach ($insertedStudents as $student) {
                    $studentIdByUserId[$student->user_id] = $student->id;
                }
                // Map personal details by phone -> user_id -> student_id
                $finalPersonalDetails = [];
                if (count($personalDetailsToInsert) > 0 && !empty($userIdByPhone) && !empty($studentIdByUserId)) {
                    $phonesForRows = array_column($usersToInsert, 'phone');
                    foreach ($personalDetailsToInsert as $idx => $detail) {
                        $phoneForRow = $phonesForRows[$idx] ?? null;
                        if (!$phoneForRow) {
                            continue;
                        }
                        $userId = $userIdByPhone[$phoneForRow] ?? null;
                        if (!$userId) {
                            continue;
                        }
                        $studentId = $studentIdByUserId[$userId] ?? null;
                        if (!$studentId) {
                            continue;
                        }
                        $detail['student_id'] = $studentId;
                        $finalPersonalDetails[] = $detail;
                    }
                    // Deduplicate by student_id (ensure one personal detail per student)
                    $dedupedByStudent = [];
                    foreach ($finalPersonalDetails as $detail) {
                        $dedupedByStudent[$detail['student_id']] = $detail;
                    }
                    $finalPersonalDetails = array_values($dedupedByStudent);
                    if (count($finalPersonalDetails) > 0) {
                        StudentPersonalDetail::insert($finalPersonalDetails);
                    }
                }
                
                // Get inserted personal detail IDs
                if (!empty($finalPersonalDetails)) {
                    $studentIds = array_column($finalPersonalDetails, 'student_id');
                    $insertedPersonalDetails = StudentPersonalDetail::whereIn('student_id', $studentIds)->get();
                    // Sort by insertion order
                    $insertedPersonalDetails = $insertedPersonalDetails->sortBy(function($detail) use ($studentIds) {
                        return array_search($detail->student_id, $studentIds);
                    })->values();
                } else {
                    $insertedPersonalDetails = collect();
                }
                
                // Update role user pivots with user_ids
                foreach ($roleUserPivots as $index => &$pivot) {
                    if (isset($insertedUsers[$index])) {
                        $pivot['user_id'] = $insertedUsers[$index]->id;
                    }
                }
                
                // Filter out pivots without valid user_ids
                $roleUserPivots = array_filter($roleUserPivots, function($pivot) {
                    return !empty($pivot['user_id']);
                });
                $roleUserPivots = array_values($roleUserPivots);
                
                // Bulk insert role user pivots
                if (count($roleUserPivots) > 0) {
                    DB::table('role_user')->insertOrIgnore($roleUserPivots);
                }
                
                // Track batch items (optional - can be skipped for speed)
                if ($insertedUsers->isNotEmpty()) {
                    $this->trackBatchItemsBulk($batch, $insertedUsers, $insertedStudents, $insertedPersonalDetails);
                }
                
                // Update progress after chunk is saved
                $this->processedCount += count($studentNames);
                $this->currentProgress = $this->totalRecords > 0 
                    ? round(($this->processedCount / $this->totalRecords) * 100, 2) 
                    : 0;
                
                // Calculate estimated time remaining
                if ($this->processedCount > 0) {
                    $elapsed = microtime(true) - $this->startTime;
                    $avgTimePerRecord = $elapsed / $this->processedCount;
                    $remaining = ($this->totalRecords - $this->processedCount) * $avgTimePerRecord;
                    $this->estimatedTimeRemaining = max(0, round($remaining));
                }
                
                // Show last student name being processed
                if (!empty($studentNames)) {
                    $this->currentStudentName = end($studentNames);
                }
                
                // Dispatch progress update
                $this->dispatch('progress-updated', [
                    'progress' => $this->currentProgress,
                    'processed' => $this->processedCount,
                    'total' => $this->totalRecords,
                    'currentStudent' => $this->currentStudentName,
                    'timeRemaining' => $this->estimatedTimeRemaining
                ]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $chunkStartRow = $rows[0]['rowNumber'] ?? 0;
            $this->totalErrors += count($rows);
            $this->errorMessages[] = "Chunk starting at row $chunkStartRow failed: " . $e->getMessage();
            \Log::error("Bulk upload chunk failed: " . $e->getMessage());
        }
    }

    private function validateRow($row)
    {
        // Ensure row has at least 6 elements, pad with empty strings if needed
        $row = array_pad($row, 6, '');
        
        if (count($row) < 6) {
            throw new \Exception("Row has insufficient columns. Expected 6 columns.");
        }

        $this->validateRequiredFields($row);
        $this->validateFieldFormats($row);
    }

    private function validateRequiredFields($row)
    {
        // Safely access array keys with null coalescing
        $playerName = trim($row[0] ?? '');
        $gender = trim($row[1] ?? '');
        $mobileNo = trim($row[3] ?? '');
        $aadhar = trim($row[4] ?? '');
        $fatherName = trim($row[5] ?? '');
        
        if (empty($playerName)) throw new \Exception("Player Name is required");
        if (empty($gender)) throw new \Exception("Gender is required");
        if (empty($mobileNo)) throw new \Exception("Mobile No is required");
        if (empty($aadhar)) throw new \Exception("Aadhar is required");
        if (empty($fatherName)) throw new \Exception("Father Name is required");
    }

    private function validateFieldFormats($row)
    {
        if (!empty($row[2] ?? '')) {
            $this->validateDate($row[2]);
        }

        if (!empty($row[3] ?? '') && !preg_match('/^\d{10}$/', $row[3])) {
            throw new \Exception("Invalid mobile number format. Must be 10 digits: " . ($row[3] ?? ''));
        }

        if (!empty($row[4] ?? '') && !preg_match('/^\d{12}$/', $row[4])) {
            throw new \Exception("Invalid Aadhar format. Must be 12 digits: " . ($row[4] ?? ''));
        }
    }

    private function validateDate($dateString)
    {
        try {
            \Carbon\Carbon::parse($dateString);
        } catch (\Exception $e) {
            throw new \Exception("Invalid date format for DOB. Use YYYY-MM-DD format: " . $dateString);
        }
    }


    private function deleteExistingStudentsByPhone(array $phones, $firmId)
    {
        // Find existing users by phone
        $existingUsers = User::whereIn('phone', $phones)->get();
        
        if ($existingUsers->isEmpty()) {
            return;
        }
        
        $userIds = $existingUsers->pluck('id')->toArray();
        
        // Find students linked to these users
        $existingStudents = Student::whereIn('user_id', $userIds)
            ->where('firm_id', $firmId)
            ->get();
        
        if ($existingStudents->isEmpty()) {
            return;
        }
        
        $studentIds = $existingStudents->pluck('id')->toArray();
        
        // Delete related records
        StudentPersonalDetail::whereIn('student_id', $studentIds)->delete();
        StudentEducationDetail::whereIn('student_id', $studentIds)->delete();
        
        // Delete students
        Student::whereIn('id', $studentIds)->delete();
        
        // Delete users
        User::whereIn('id', $userIds)->delete();
        
        // Delete role_user pivots
        DB::table('role_user')->whereIn('user_id', $userIds)->delete();
    }
    
    private function insertOrUpdateUsers(array $usersToInsert)
    {
        // Get all phones and emails from the insert data
        $phones = array_column($usersToInsert, 'phone');
        $emails = array_column($usersToInsert, 'email');
        
        // Remove duplicates from phones array (in case same phone appears multiple times)
        $phones = array_unique($phones);
        $emails = array_unique($emails);
        
        // Find existing users
        $existingUsers = User::whereIn('phone', $phones)
            ->orWhereIn('email', $emails)
            ->get();
        
        // Create maps for quick lookup
        $existingByPhone = $existingUsers->keyBy('phone');
        $existingByEmail = $existingUsers->keyBy('email');
        
        $newUsers = [];
        $userPhoneMap = [];
        $processedPhones = []; // Track processed phones to avoid duplicates
        
        foreach ($usersToInsert as $userData) {
            $phone = $userData['phone'];
            $email = $userData['email'];
            
            // Skip if we've already processed this phone in this batch
            if (isset($processedPhones[$phone])) {
                continue;
            }
            
            // Check if user exists by phone or email
            $existingUser = $existingByPhone->get($phone) ?? $existingByEmail->get($email);
            
            if ($existingUser) {
                // Update existing user
                $existingUser->update([
                    'name' => $userData['name'],
                    'email' => $email,
                    'password' => $userData['password'],
                    'passcode' => $userData['passcode'],
                    'role_main' => $userData['role_main'],
                    'updated_at' => $userData['updated_at']
                ]);
                $userPhoneMap[$phone] = $existingUser;
                $processedPhones[$phone] = true;
            } else {
                // New user to insert - check for duplicates in newUsers array
                $isDuplicate = false;
                foreach ($newUsers as $newUser) {
                    if ($newUser['phone'] === $phone || $newUser['email'] === $email) {
                        $isDuplicate = true;
                        break;
                    }
                }
                
                if (!$isDuplicate) {
                    $newUsers[] = $userData;
                    $userPhoneMap[$phone] = null; // Will be set after insert
                    $processedPhones[$phone] = true;
                }
            }
        }
        
        // Bulk insert new users, handling duplicates gracefully
        if (count($newUsers) > 0) {
            try {
                // Try bulk insert first
                User::insert($newUsers);
                
                // Get newly inserted users
                $newPhones = array_column($newUsers, 'phone');
                $newlyInserted = User::whereIn('phone', $newPhones)->get()->keyBy('phone');
                
                // Update the map with newly inserted users
                foreach ($newlyInserted as $phone => $user) {
                    $userPhoneMap[$phone] = $user;
                }
            } catch (\Exception $e) {
                // If bulk insert fails due to duplicates, insert one by one
                foreach ($newUsers as $userData) {
                    try {
                        $phone = $userData['phone'];
                        $email = $userData['email'];
                        
                        // Check if user exists (might have been inserted by another process)
                        $existingUser = User::where('phone', $phone)
                            ->orWhere('email', $email)
                            ->first();
                        
                        if ($existingUser) {
                            // Update existing user
                            $existingUser->update([
                                'name' => $userData['name'],
                                'email' => $email,
                                'password' => $userData['password'],
                                'passcode' => $userData['passcode'],
                                'role_main' => $userData['role_main'],
                                'updated_at' => $userData['updated_at']
                            ]);
                            $userPhoneMap[$phone] = $existingUser;
                        } else {
                            // Try to insert
                            try {
                                User::insert($userData);
                                $insertedUser = User::where('phone', $phone)->first();
                                if ($insertedUser) {
                                    $userPhoneMap[$phone] = $insertedUser;
                                }
                            } catch (\Exception $insertException) {
                                // If still fails, fetch and update
                                $existingUser = User::where('phone', $phone)->first();
                                if ($existingUser) {
                                    $existingUser->update([
                                        'name' => $userData['name'],
                                        'email' => $email,
                                        'password' => $userData['password'],
                                        'passcode' => $userData['passcode'],
                                        'role_main' => $userData['role_main'],
                                        'updated_at' => $userData['updated_at']
                                    ]);
                                    $userPhoneMap[$phone] = $existingUser;
                                } else {
                                    \Log::error("Failed to insert/update user with phone: $phone - " . $insertException->getMessage());
                                }
                            }
                        }
                    } catch (\Exception $userException) {
                        \Log::error("Error processing user with phone: " . ($userData['phone'] ?? 'unknown') . " - " . $userException->getMessage());
                    }
                }
            }
        }
        
        return $userPhoneMap;
    }
    
    private function prepareUserDataForBulk($row, $hashedPassword, $hashedPasscode, $now)
    {
        $nameParts = $this->splitPlayerName($row[0] ?? '');
        $fullName = trim(implode(' ', array_filter($nameParts)));
        $email = $this->generateEmailFromName($fullName);
        
        return [
            'name' => $fullName,
            'email' => $email,
            'password' => $hashedPassword,
            'phone' => $row[3] ?? '',
            'passcode' => $hashedPasscode,
            'role_main' => 'L0_student',
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }
    
    private function prepareStudentDataForBulk($row, $userData, $firmId, $now)
    {
        $nameParts = $this->splitPlayerName($row[0] ?? '');
        
        return [
            'fname' => $nameParts['fname'],
            'mname' => $nameParts['mname'],
            'lname' => $nameParts['lname'],
            'email' => $userData['email'],
            'phone' => $row[3] ?? '',
            'user_id' => null, // Will be set after user insert
            'firm_id' => $firmId,
            'is_inactive' => false,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }
    
    private function preparePersonalDetailDataForBulk($row, $now)
    {
        return [
            'student_id' => null, // Will be set after student insert
            'gender' => $this->normalizeGender($row[1] ?? ''),
            'dob' => $this->parseDate($row[2] ?? ''),
            'mobile_number' => $row[3] ?? '',
            'adharno' => $row[4] ?? '',
            'fathername' => $row[5] ?? '',
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }
    
    private function trackBatchItemsBulk($batch, $users, $students, $personalDetails)
    {
        $batchItems = [];
        
        foreach ($users as $user) {
            $batchItems[] = [
                'batch_id' => $batch->id,
                'operation' => 'insert',
                'model_type' => User::class,
                'model_id' => $user->id,
                'new_data' => json_encode($user->getAttributes()),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        
        foreach ($students as $student) {
            $batchItems[] = [
                'batch_id' => $batch->id,
                'operation' => 'insert',
                'model_type' => Student::class,
                'model_id' => $student->id,
                'new_data' => json_encode($student->getAttributes()),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        
        foreach ($personalDetails as $personalDetail) {
            $batchItems[] = [
                'batch_id' => $batch->id,
                'operation' => 'insert',
                'model_type' => StudentPersonalDetail::class,
                'model_id' => $personalDetail->id,
                'new_data' => json_encode($personalDetail->getAttributes()),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        
        if (count($batchItems) > 0) {
            // Insert in chunks to avoid query size limits
            $chunks = array_chunk($batchItems, 500);
            foreach ($chunks as $chunk) {
                BatchItem::insert($chunk);
            }
        }
    }

    private function splitPlayerName($playerName)
    {
        $parts = array_map('trim', explode(' ', $playerName));
        $parts = array_filter($parts);
        
        if (count($parts) === 0) {
            return ['fname' => '', 'mname' => null, 'lname' => null];
        }
        
        if (count($parts) === 1) {
            return ['fname' => $parts[0], 'mname' => null, 'lname' => null];
        }
        
        if (count($parts) === 2) {
            return ['fname' => $parts[0], 'mname' => null, 'lname' => $parts[1]];
        }
        
        return [
            'fname' => $parts[0],
            'mname' => implode(' ', array_slice($parts, 1, -1)),
            'lname' => end($parts)
        ];
    }

    private function generateEmailFromName($name)
    {
        $cleanName = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $name));
        return $cleanName . '@student.local';
    }


    private function parseDate($dateString)
    {
        if (empty($dateString)) {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($dateString)->format('Y-m-d');
        } catch (\Exception $e) {
            throw new \Exception("Invalid date format: " . $dateString);
        }
    }

    private function normalizeGender($gender)
    {
        $genderLower = strtolower(trim($gender));
        if (in_array($genderLower, ['male', 'm'])) {
            return 'Male';
        }
        if (in_array($genderLower, ['female', 'f'])) {
            return 'Female';
        }
        return $gender;
    }


    private function finalizeUpload($batch, $result)
    {
        if ($result['errorCount'] > 0) {
            $this->uploadErrors = $result['errors'];
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => "Failed to import {$result['errorCount']} records. Please check the errors below."
            ]);
        } else {
            $this->currentBatch = $batch;
            $this->uploadSuccess = true;
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => "Successfully imported {$result['successCount']} students."
            ]);
        }
    }

    private function handleUploadError($e)
    {
        $errorMessage = $e->getMessage();
        if (empty($errorMessage)) {
            $errorMessage = "An unknown error occurred. Please check server logs.";
        }
        
        $this->uploadErrors = array_merge($this->uploadErrors, [$errorMessage]);
        
        // Log full error for debugging
        \Log::error('Student bulk upload failed: ' . $e->getMessage(), [
            'trace' => $e->getTraceAsString(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);
        
        $this->dispatch('notify', [
            'type' => 'error',
            'message' => "Upload failed: " . $errorMessage
        ]);
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Onboard/blades/student-bulk-upload.blade.php'));
    }
}

