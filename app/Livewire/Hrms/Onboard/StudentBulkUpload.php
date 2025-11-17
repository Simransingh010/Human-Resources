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

    private function prescanCsvFile()
    {
        $file = fopen($this->csvFile->getRealPath(), 'r');
        if (!$file) {
            throw new \Exception("Could not open the CSV file. Please check if the file is valid.");
        }

        $headers = fgetcsv($file);
        if (!$headers) {
            fclose($file);
            throw new \Exception("The CSV file appears to be empty or invalid.");
        }

        $validRows = [];
        $phones = [];
        $errors = [];
        $rowNumber = 2;

        while (($row = fgetcsv($file)) !== false) {
            if (empty(array_filter($row))) {
                $rowNumber++;
                continue;
            }

            try {
                $row = array_pad($row, 7, '');
                $centreName = $this->normalizeCentreName($row[0] ?? '');
                $centreKey = $this->normalizeCentreKey($centreName);
                $phone = trim($row[4] ?? '');

                if (empty($centreName)) {
                    $errors[] = "Row $rowNumber: Center Name is required.";
                    $rowNumber++;
                    continue;
                }

                if (empty($phone)) {
                    $errors[] = "Row $rowNumber: Mobile No is required.";
                    $rowNumber++;
                    continue;
                }

                if (!preg_match('/^\d{10,12}$/', $row[4])) {
                    $errors[] = "Row $rowNumber: Invalid phone/identifier. Must be 10-12 digits: {$row[4]}";
                    $rowNumber++;
                    continue;
                }

                if (isset($phones[$phone])) {
                    $errors[] = "Row $rowNumber: Duplicate phone $phone in uploaded file (previous row {$phones[$phone]}).";
                    $rowNumber++;
                    continue;
                }

                $phones[$phone] = $rowNumber;
                $validRows[] = [
                    'data' => $row,
                    'rowNumber' => $rowNumber,
                    'phone' => $phone,
                    'centre_name' => $centreName,
                    'centre_key' => $centreKey,
                ];
            } catch (\Throwable $rowError) {
                $errors[] = "Row $rowNumber: " . $rowError->getMessage();
                $rowNumber++;
                continue;
            }

            $rowNumber++;
        }

        fclose($file);

        if (!empty($phones)) {
            $existingUsers = User::whereIn('phone', array_keys($phones))
                ->pluck('id', 'phone')
                ->all();

            if (!empty($existingUsers)) {
                foreach ($validRows as $index => $row) {
                    $phone = $row['phone'] ?? null;
                    if (!$phone) {
                        continue;
                    }

                    if (isset($existingUsers[$phone])) {
                        $errors[] = "Row {$row['rowNumber']}: User already exists for phone '$phone' (user id {$existingUsers[$phone]}) — skipping row.";
                        unset($validRows[$index]);
                    }
                }
            }
        }

        return [
            'rows' => array_values($validRows),
            'errors' => $errors,
        ];
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
            'Center Name*',
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
            'GHANDHIR',
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
        } catch (\Throwable $e) {
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
        $batchUserIds = $items
            ->where('model_type', User::class)
            ->pluck('model_id')
            ->toArray();

        foreach ($items as $item) {
            $deletedCount += $this->deleteItemByType($item, $batchUserIds);
        }

        return $deletedCount;
    }

    private function deleteItemByType($item, array $batchUserIds)
    {
        if ($item->model_type === Student::class) {
            return $this->deleteStudentRecord($item, $batchUserIds);
        }

        return $this->deleteStandardRecord($item);
    }

    private function deleteStudentRecord($item, array $batchUserIds)
    {
        $student = Student::where('id', $item->model_id)->first();
        if (!$student) {
            return 0;
        }

        $this->deleteStudentRelatedRecords($student, $batchUserIds);
        $student->delete();
        return 1;
    }

    private function deleteStudentRelatedRecords($student, array $batchUserIds)
    {
        StudentPersonalDetail::where('student_id', $student->id)->delete();
        StudentEducationDetail::where('student_id', $student->id)->delete();

        $userId = $student->user_id;
        if ($userId && in_array($userId, $batchUserIds)) {
            User::where('id', $userId)->delete();
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
            $scanResult = $this->prescanCsvFile();
            $this->uploadErrors = $scanResult['errors'];

            $validRows = $scanResult['rows'];
            $this->totalRecords = count($validRows);

            if (!empty($this->uploadErrors)) {
                $this->dispatch('notify', [
                    'type' => 'warning',
                    'message' => 'Some rows were skipped due to validation errors. Remaining valid records will be processed.'
                ]);
            }

            if ($this->totalRecords === 0) {
                $this->dispatch('notify', [
                    'type' => 'error',
                    'message' => 'The upload file does not contain any valid records.'
                ]);
                return;
            }

            $batch = $this->createBatch();
            
            // Start progress tracking
            $this->isUploading = true;
            $this->startTime = microtime(true);
            $this->processedCount = 0;
            $this->currentProgress = 0;
            
            $result = $this->processRows($validRows, $batch);
            $this->finalizeUpload($batch, $result);
        } catch (\Throwable $e) {
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

    private function processRows(array $rows, $batch)
    {
        // Cache values once - major performance improvement
        $studentRole = Role::where('name', 'student')->first();
        $studentRoleId = $studentRole ? $studentRole->id : null;
        $firmId = session('firm_id');
        
        if (!$firmId) {
            throw new \Exception("Firm ID not found in session");
        }

        $studyCentreMap = $this->ensureStudyCentresForRows($rows, $firmId, $batch);

        // Pre-hash password once instead of per record
        $hashedPassword = Hash::make('password');
        $hashedPasscode = Hash::make('password');
        $now = now();

        $chunks = array_chunk($rows, 300);
        foreach ($chunks as $chunk) {
            $this->processChunk($chunk, $batch, $studentRoleId, $firmId, $hashedPassword, $hashedPasscode, $now, $studyCentreMap);
        }

        return [
            'successCount' => $this->totalSuccess,
            'errorCount' => $this->totalErrors,
            'errors' => $this->errorMessages
        ];
    }

    private function processChunk(array $rows, $batch, $studentRoleId, $firmId, $hashedPassword, $hashedPasscode, $now, array $studyCentreMap)
    {
        DB::beginTransaction();
        try {
            $usersToInsert = [];
            $studentsToInsert = [];
            $personalDetailEntries = [];
            $educationDetailEntries = [];
            $phoneToIndexMap = [];
            $rowNumbersByPhone = [];
            $studentNames = [];

            foreach ($rows as $index => $rowData) {
                $row = array_pad($rowData['data'], 7, '');
                $rowNumber = $rowData['rowNumber'];
                $phone = trim($rowData['phone'] ?? $row[4] ?? '');
                $centreName = $rowData['centre_name'] ?? $this->normalizeCentreName($row[0] ?? '');
                $centreKey = $rowData['centre_key'] ?? $this->normalizeCentreKey($centreName);

                try {
                    $this->validateRow($row);

                    if (empty($centreName)) {
                        $this->totalErrors++;
                        $this->errorMessages[] = "Row $rowNumber: Center Name is required.";
                        continue;
                    }

                    if (!$centreKey || !isset($studyCentreMap[$centreKey])) {
                        $this->totalErrors++;
                        $this->errorMessages[] = "Row $rowNumber: Unable to resolve study centre '$centreName'.";
                        continue;
                    }
                    $studyCentreId = $studyCentreMap[$centreKey] ?? null;

                    if (empty($phone)) {
                        $this->totalErrors++;
                        $this->errorMessages[] = "Row $rowNumber: Mobile No is required.";
                        continue;
                    }

                    if (isset($phoneToIndexMap[$phone])) {
                        $this->totalErrors++;
                        $this->errorMessages[] = "Row $rowNumber: Duplicate phone number '$phone' found in the same upload batch. Skipping duplicate.";
                        continue;
                    }

                    $phoneToIndexMap[$phone] = $index;
                    $rowNumbersByPhone[$phone] = $rowNumber;

                    $nameParts = $this->splitPlayerName($row[1] ?? '');
                    $studentNames[] = trim(implode(' ', array_filter($nameParts)));

                    $userData = $this->prepareUserDataForBulk($row, $hashedPassword, $hashedPasscode, $now);
                    $studentData = $this->prepareStudentDataForBulk($row, $userData, $firmId, $now);
                    $personalDetailData = $this->preparePersonalDetailDataForBulk($row, $now);

                    $usersToInsert[] = $userData;
                    $studentsToInsert[] = $studentData;
                    $personalDetailEntries[] = [
                        'phone' => $phone,
                        'data' => $personalDetailData,
                    ];
                    $educationDetailEntries[] = [
                        'phone' => $phone,
                        'study_centre_id' => $studyCentreId,
                    ];
                } catch (\Throwable $e) {
                    $this->totalErrors++;
                    $this->errorMessages[] = "Row $rowNumber: " . $e->getMessage();
                }
            }

            $insertedUsers = collect();
            $userPhoneMap = [];
            $failedPhones = [];
            $chunkSuccessCount = 0;

            if (count($usersToInsert) > 0) {
                $insertResult = $this->insertNewUsersOnly($usersToInsert, $rowNumbersByPhone);
                $insertedUsers = $insertResult['users'];
                $userPhoneMap = $insertResult['userMap'];
                $failedPhones = $insertResult['failed'];

                if (!empty($failedPhones)) {
                    foreach ($failedPhones as $phone => $failedRowNumber) {
                        $this->totalErrors++;
                        $prefix = $failedRowNumber ? "Row $failedRowNumber: " : '';
                        $this->errorMessages[] = $prefix . "User already exists for phone '$phone'. Skipping row.";
                    }

                $studentsToInsert = array_filter($studentsToInsert, function ($student) use ($failedPhones) {
                    return !isset($failedPhones[$student['phone']]);
                });
                $personalDetailEntries = array_filter($personalDetailEntries, function ($entry) use ($failedPhones) {
                    $phoneRef = $entry['phone'] ?? null;
                    return $phoneRef && !isset($failedPhones[$phoneRef]);
                });
                    $educationDetailEntries = array_filter($educationDetailEntries, function ($entry) use ($failedPhones) {
                        $phoneRef = $entry['phone'] ?? null;
                        return $phoneRef && !isset($failedPhones[$phoneRef]);
                    });
                }

                $userIdByPhone = [];
                foreach ($userPhoneMap as $phoneKey => $userObj) {
                    if ($userObj) {
                        $userIdByPhone[$phoneKey] = $userObj->id;
                    }
                }

                $personalDetailsByPhone = [];
                foreach ($personalDetailEntries as $entry) {
                    $personalDetailsByPhone[$entry['phone']] = $entry['data'];
                }

                $educationDetailsByPhone = [];
                foreach ($educationDetailEntries as $entry) {
                    $educationDetailsByPhone[$entry['phone']] = $entry['study_centre_id'];
                }

                $studentsReadyForInsert = [];
                $userIds = [];

                foreach ($studentsToInsert as $studentData) {
                    $phone = $studentData['phone'];
                    $userId = $userIdByPhone[$phone] ?? null;

                    if (!$userId) {
                        $rowNumber = $rowNumbersByPhone[$phone] ?? null;
                        $this->totalErrors++;
                        $messagePrefix = $rowNumber ? "Row $rowNumber: " : '';
                        $this->errorMessages[] = $messagePrefix . "Unable to create user for phone '$phone'. Skipping student.";
                        continue;
                    }

                    if (!isset($personalDetailsByPhone[$phone])) {
                        $rowNumber = $rowNumbersByPhone[$phone] ?? null;
                        $this->totalErrors++;
                        $prefix = $rowNumber ? "Row $rowNumber: " : '';
                        $this->errorMessages[] = $prefix . "Missing personal details for phone '$phone'. Skipping student.";
                        continue;
                    }

                    if (!isset($educationDetailsByPhone[$phone])) {
                        $rowNumber = $rowNumbersByPhone[$phone] ?? null;
                        $this->totalErrors++;
                        $prefix = $rowNumber ? "Row $rowNumber: " : '';
                        $this->errorMessages[] = $prefix . "Missing study centre mapping for phone '$phone'. Skipping student.";
                        continue;
                    }

                    $studentData['user_id'] = $userId;
                    $studentsReadyForInsert[] = $studentData;
                    $userIds[] = $userId;
                }

                if (count($studentsReadyForInsert) > 0) {
                    Student::insert($studentsReadyForInsert);
                    $insertedStudents = Student::whereIn('user_id', $userIds)->get();
                    $insertedStudents = $insertedStudents->sortBy(function ($student) use ($userIds) {
                        return array_search($student->user_id, $userIds);
                    })->values();
                    $chunkSuccessCount = count($studentsReadyForInsert);
                } else {
                    $insertedStudents = collect();
                }

                $studentIdByUserId = [];
                foreach ($insertedStudents as $student) {
                    $studentIdByUserId[$student->user_id] = $student->id;
                }

                $finalPersonalDetails = [];
                if (!empty($studentIdByUserId) && !empty($studentsReadyForInsert)) {
                    foreach ($insertedStudents as $student) {
                        $phoneForRow = $student->phone;
                        $detail = $personalDetailsByPhone[$phoneForRow] ?? null;

                        if (!$detail) {
                            $rowNumber = $rowNumbersByPhone[$phoneForRow] ?? null;
                            $prefix = $rowNumber ? "Row $rowNumber: " : '';
                            throw new \Exception($prefix . "Failed to prepare personal details for phone '$phoneForRow'.");
                        }

                        $detail['student_id'] = $student->id;
                        $finalPersonalDetails[$student->id] = $detail;
                    }

                    if (!empty($finalPersonalDetails)) {
                        StudentPersonalDetail::insert(array_values($finalPersonalDetails));
                    } else {
                        throw new \Exception('Failed to prepare personal details for inserted students.');
                    }
                }

                if (!empty($finalPersonalDetails)) {
                    $studentIds = array_keys($finalPersonalDetails);
                    $insertedPersonalDetails = StudentPersonalDetail::whereIn('student_id', $studentIds)->get();
                    $insertedPersonalDetails = $insertedPersonalDetails->sortBy(function ($detail) use ($studentIds) {
                        return array_search($detail->student_id, $studentIds);
                    })->values();
                } else {
                    $insertedPersonalDetails = collect();
                }

                $finalEducationDetails = [];
                if (!empty($studentIdByUserId) && !empty($studentsReadyForInsert)) {
                    foreach ($insertedStudents as $student) {
                        $phoneForRow = $student->phone;
                        $studyCentreId = $educationDetailsByPhone[$phoneForRow] ?? null;

                        if (!$studyCentreId) {
                            $rowNumber = $rowNumbersByPhone[$phoneForRow] ?? null;
                            $prefix = $rowNumber ? "Row $rowNumber: " : '';
                            throw new \Exception($prefix . "Failed to resolve study centre for phone '$phoneForRow'.");
                        }

                        $finalEducationDetails[$student->id] = [
                            'student_id' => $student->id,
                            'study_centre_id' => $studyCentreId,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }

                    if (!empty($finalEducationDetails)) {
                        StudentEducationDetail::insert(array_values($finalEducationDetails));
                    } else {
                        throw new \Exception('Failed to prepare study centre details for inserted students.');
                    }
                }

                if (!empty($finalEducationDetails)) {
                    $educationStudentIds = array_keys($finalEducationDetails);
                    $insertedEducationDetails = StudentEducationDetail::whereIn('student_id', $educationStudentIds)->get();
                    $insertedEducationDetails = $insertedEducationDetails->sortBy(function ($detail) use ($educationStudentIds) {
                        return array_search($detail->student_id, $educationStudentIds);
                    })->values();
                } else {
                    $insertedEducationDetails = collect();
                }

                if ($studentRoleId && !empty($userIds)) {
                    $roleUserPivots = [];
                    foreach (array_unique($userIds) as $userId) {
                        $roleUserPivots[] = [
                            'user_id' => $userId,
                            'role_id' => $studentRoleId,
                            'firm_id' => $firmId,
                        ];
                    }

                    if (!empty($roleUserPivots)) {
                        DB::table('role_user')->insertOrIgnore($roleUserPivots);
                    }
                }

                if ($insertedUsers->isNotEmpty()) {
                    $this->trackBatchItemsBulk($batch, $insertedUsers, $insertedStudents, $insertedPersonalDetails, $insertedEducationDetails);
                }
            }

            $this->processedCount += count($rows);
            $this->totalSuccess += $chunkSuccessCount;
            $this->currentProgress = $this->totalRecords > 0
                ? round(($this->processedCount / $this->totalRecords) * 100, 2)
                : 0;

            if ($this->processedCount > 0) {
                $elapsed = microtime(true) - $this->startTime;
                $avgTimePerRecord = $elapsed / $this->processedCount;
                $remaining = ($this->totalRecords - $this->processedCount) * $avgTimePerRecord;
                $this->estimatedTimeRemaining = max(0, round($remaining));
            }

            if (!empty($studentNames)) {
                $this->currentStudentName = end($studentNames);
            }

            $this->dispatch('progress-updated', [
                'progress' => $this->currentProgress,
                'processed' => $this->processedCount,
                'total' => $this->totalRecords,
                'currentStudent' => $this->currentStudentName,
                'timeRemaining' => $this->estimatedTimeRemaining
            ]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $chunkStartRow = $rows[0]['rowNumber'] ?? 0;
            $this->totalErrors += count($rows);
            $this->errorMessages[] = "Chunk starting at row $chunkStartRow failed: " . $e->getMessage();
            \Log::error("Bulk upload chunk failed: " . $e->getMessage());
        }
    }

    private function validateRow($row)
    {
        // Ensure row has at least 7 elements, pad with empty strings if needed
        $row = array_pad($row, 7, '');
        
        if (count($row) < 7) {
            throw new \Exception("Row has insufficient columns. Expected 7 columns.");
        }

        $this->validateRequiredFields($row);
        $this->validateFieldFormats($row);
    }

    private function validateRequiredFields($row)
    {
        // Safely access array keys with null coalescing
        $centerName = trim($row[0] ?? '');
        $playerName = trim($row[1] ?? '');
        $gender = trim($row[2] ?? '');
        $mobileNo = trim($row[4] ?? '');
        $aadhar = trim($row[5] ?? '');
        $fatherName = trim($row[6] ?? '');
        
        if (empty($centerName)) throw new \Exception("Center Name is required");
        if (empty($playerName)) throw new \Exception("Player Name is required");
        if (empty($gender)) throw new \Exception("Gender is required");
        if (empty($mobileNo)) throw new \Exception("Mobile No is required");
        if (empty($aadhar)) throw new \Exception("Aadhar is required");
        if (empty($fatherName)) throw new \Exception("Father Name is required");
    }

    private function validateFieldFormats($row)
    {
        if (!empty($row[3] ?? '')) {
            $this->validateDate($row[3]);
        }

      // Allow 10-digit real phone OR 12-digit Aadhaar
if (!preg_match('/^\d{10}$/', $row[4]) && !preg_match('/^\d{12}$/', $row[4])) {
    throw new \Exception("Invalid phone/identifier. Must be 10 or 12 digits: " . $row[4]);
}

        if (!empty($row[5] ?? '') && !preg_match('/^\d{12}$/', $row[5])) {
            throw new \Exception("Invalid Aadhar format. Must be 12 digits: " . ($row[5] ?? ''));
        }
    }

    private function validateDate($dateString)
    {
        try {
            \Carbon\Carbon::parse($dateString);
        } catch (\Throwable $e) {
            throw new \Exception("Invalid date format for DOB. Use YYYY-MM-DD format: " . $dateString);
        }
    }


    private function insertNewUsersOnly(array $usersToInsert, array $rowNumbersByPhone)
    {
        if (empty($usersToInsert)) {
            return [
                'users' => collect(),
                'userMap' => [],
                'failed' => [],
            ];
        }

        $uniqueUsers = [];
        $orderedPhones = [];
        foreach ($usersToInsert as $userData) {
            $phone = $userData['phone'];
            if (empty($phone) || isset($uniqueUsers[$phone])) {
                continue;
            }
            $uniqueUsers[$phone] = $userData;
            $orderedPhones[] = $phone;
        }

        $failedPhones = [];

        try {
            User::insert(array_values($uniqueUsers));
        } catch (\Throwable $e) {
            \Log::warning('Bulk user insert failed during student upload: ' . $e->getMessage());
            $conflictingPhones = User::whereIn('phone', array_keys($uniqueUsers))
                ->pluck('phone')
                ->toArray();

            foreach ($conflictingPhones as $phone) {
                $failedPhones[$phone] = $rowNumbersByPhone[$phone] ?? null;
            }
        }

        $insertedUsers = User::whereIn('phone', array_keys($uniqueUsers))
            ->get()
            ->keyBy('phone');

        foreach ($failedPhones as $phone => $rowNumber) {
            unset($insertedUsers[$phone]);
        }

        $orderedUsers = collect($orderedPhones)->map(function ($phone) use ($insertedUsers) {
            return $insertedUsers->get($phone);
        })->filter()->values();

        return [
            'users' => $orderedUsers,
            'userMap' => $insertedUsers->all(),
            'failed' => $failedPhones,
        ];
    }
    
    private function prepareUserDataForBulk($row, $hashedPassword, $hashedPasscode, $now)
    {
        $nameParts = $this->splitPlayerName($row[1] ?? '');
        $fullName = trim(implode(' ', array_filter($nameParts)));
        $email = $this->generateEmailFromName($fullName, $row[4] ?? '');
        
        return [
            'name' => $fullName,
            'email' => $email,
            'password' => $hashedPassword,
            'phone' => $row[4] ?? '',
            'passcode' => $hashedPasscode,
            'role_main' => 'L0_student',
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }
    
    private function prepareStudentDataForBulk($row, $userData, $firmId, $now)
    {
        $nameParts = $this->splitPlayerName($row[1] ?? '');
        
        return [
            'fname' => $nameParts['fname'],
            'mname' => $nameParts['mname'],
            'lname' => $nameParts['lname'],
            'email' => $userData['email'],
            'phone' => $row[4] ?? '',
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
            'gender' => $this->normalizeGender($row[2] ?? ''),
            'dob' => $this->parseDate($row[3] ?? ''),
            'mobile_number' => $row[4] ?? '',
            'adharno' => $row[5] ?? '',
            'fathername' => $row[6] ?? '',
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }
    
    private function trackBatchItemsBulk($batch, $users, $students, $personalDetails, $educationDetails)
    {
        $batchItems = [];
        
        $metadata = json_encode([
            'created_in_batch' => true,
            'created_by_batch_id' => $batch->id,
        ]);

        foreach ($users as $user) {
            $batchItems[] = [
                'batch_id' => $batch->id,
                'operation' => 'insert',
                'model_type' => User::class,
                'model_id' => $user->id,
                'new_data' => $metadata,
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
                'new_data' => $metadata,
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
                'new_data' => $metadata,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        foreach ($educationDetails as $educationDetail) {
            $batchItems[] = [
                'batch_id' => $batch->id,
                'operation' => 'insert',
                'model_type' => StudentEducationDetail::class,
                'model_id' => $educationDetail->id,
                'new_data' => $metadata,
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

    private function generateEmailFromName($name, $phone = '')
    {
        $cleanName = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $name));
        $cleanName = $cleanName !== '' ? $cleanName : 'student';

        $phoneDigits = preg_replace('/\D/', '', (string) $phone);
        if ($phoneDigits === '') {
            $phoneDigits = substr((string) now()->timestamp, -6) . random_int(100, 999);
        }

        return $cleanName . '.' . $phoneDigits . '@student.local';
    }


    private function parseDate($dateString)
    {
        if (empty($dateString)) {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($dateString)->format('Y-m-d');
        } catch (\Throwable $e) {
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

    private function normalizeCentreName($value)
    {
        return trim((string) $value);
    }

    private function normalizeCentreKey(?string $value)
    {
        $name = $this->normalizeCentreName($value);
        return $name === '' ? '' : mb_strtolower($name);
    }

    private function ensureStudyCentresForRows(array $rows, $firmId, $batch)
    {
        $centreNames = [];
        foreach ($rows as $row) {
            $centreName = $row['centre_name'] ?? $this->normalizeCentreName($row['data'][0] ?? '');
            if (empty($centreName)) {
                continue;
            }

            $key = $this->normalizeCentreKey($centreName);
            if ($key === '') {
                continue;
            }

            if (!isset($centreNames[$key])) {
                $centreNames[$key] = $centreName;
            }
        }

        if (empty($centreNames)) {
            return [];
        }

        $existingCentres = StudyCentre::where('firm_id', $firmId)
            ->whereIn('name', array_values($centreNames))
            ->get();

        $centreMap = [];
        foreach ($existingCentres as $centre) {
            $centreMap[$this->normalizeCentreKey($centre->name)] = $centre->id;
        }

        $missingCentres = array_diff_key($centreNames, $centreMap);
        $newlyCreated = collect();

        foreach ($missingCentres as $key => $name) {
            $centre = StudyCentre::create([
                'firm_id' => $firmId,
                'name' => $name,
                'is_inactive' => false,
            ]);
            $centreMap[$key] = $centre->id;
            $newlyCreated->push($centre);
        }

        if ($newlyCreated->isNotEmpty()) {
            $this->trackStudyCentreBatchItems($batch, $newlyCreated);
        }

        return $centreMap;
    }

    private function trackStudyCentreBatchItems($batch, $centres)
    {
        if ($centres->isEmpty()) {
            return;
        }

        $metadata = json_encode([
            'created_in_batch' => true,
            'created_by_batch_id' => $batch->id,
        ]);

        $items = [];
        foreach ($centres as $centre) {
            $items[] = [
                'batch_id' => $batch->id,
                'operation' => 'insert',
                'model_type' => StudyCentre::class,
                'model_id' => $centre->id,
                'new_data' => $metadata,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        $chunks = array_chunk($items, 500);
        foreach ($chunks as $chunk) {
            BatchItem::insert($chunk);
        }
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

