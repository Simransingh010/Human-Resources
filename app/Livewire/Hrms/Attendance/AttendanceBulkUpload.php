<?php

namespace App\Livewire\Hrms\Attendance;

use App\Livewire\Hrms\Reports\AttendanceReports\exports\AttendanceSummaryExport;
use App\Models\BatchItem;
use App\Models\Hrms\EmployeeJobProfile;
use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Hrms\EmpAttendance;
use App\Models\Batch;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use App\Models\Hrms\Employee;
use App\Models\Hrms\WorkShiftDay;
use Carbon\Carbon;
use App\Models\Hrms\FlexiWeekOff;

class AttendanceBulkUpload extends Component
{
    use WithFileUploads;

    public $csvFile;
    public $currentBatch;
    public $uploadErrors = [];
    public $uploadSuccess = false;
    public $batches;
    public $selectedBatch = null;
    public $selectedBatchId = null;
    public $filters = [
        'date_range' => null,
        'employee_id' => null,
        'department_id' => null,
        'employment_type_id' => null,
    ];
    public array $listsForFields = [];
    public const ATTENDANCE_STATUS_MAIN_SELECT = [
        'P'   => 'Present',
        'A'   => 'Absent',
        'HD'  => 'Half Day',
        'PW'  => 'Partial Working',
        'L'   => 'Leave',
        'WFR' => 'Work from Remote',
        'CW'  => 'Compensatory Work',
        'OD'  => 'On Duty',
        'H'   => 'Holiday',
        'W'   => 'Week Off',
        'S'   => 'Suspended',
        'POW' => 'Present on Work off',
    ];

    protected $messages = [
        'filters.date_range.start.required' => 'Please select date range first',
        'filters.date_range.end.required' => 'Please select date range first',
        'filters.date_range.end.after_or_equal' => 'End date must be after or equal to start date',
    ];

    public function mount()
    {
        $this->initListsForFields();
        $this->filters['date_from'] = now()->startOfMonth()->format('Y-m-d');
        $this->filters['date_to'] = now()->format('Y-m-d');
        $this->batches = Batch::where('modulecomponent', 'hrms.attendance.attendance-bulk-upload')
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
        try {
            $validated = $this->validate([
                'filters.date_range.start' => 'required|date',
                'filters.date_range.end' => 'required|date|after_or_equal:filters.date_range.start',
            ]);

            $filename = 'attendance_template.csv';
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];

            $callback = function() {
                $file = fopen('php://output', 'w');

                // Get dates from selected date range
                $start = Carbon::parse($this->filters['date_range']['start']);
                $end = Carbon::parse($this->filters['date_range']['end']);
                $dates = [];
                while ($start->lte($end)) {
                    $dates[] = $start->format('d-M-Y');
                    $start->addDay();
                }

                // Prepare header row
                $header = [
                    'Employee Code',
                    'Department',
                    'Employment Type',
                ];
                $header = array_merge($header, $dates);
                $totalColumns = count($header);

                // Add instructions at the top with proper formatting
                $instructionHeader = array_fill(0, $totalColumns, '');
                $instructionHeader[0] = '!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!';
                fputcsv($file, $instructionHeader);
                
                $mainHeader = array_fill(0, $totalColumns, '');
                $mainHeader[0] = '!!!           INSTRUCTIONS FOR FILLING ATTENDANCE           !!!';
                fputcsv($file, $mainHeader);
                
                $instructionFooter = array_fill(0, $totalColumns, '');
                $instructionFooter[0] = '!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!';
                fputcsv($file, $instructionFooter);
                
                // Empty row for spacing
                fputcsv($file, array_fill(0, $totalColumns, ''));
                
                // Status codes instruction
                $statusCodes = [];
                foreach (self::ATTENDANCE_STATUS_MAIN_SELECT as $code => $label) {
                    $statusCodes[] = "[$code = $label]";
                }
                
                $statusInstructions = array_fill(0, $totalColumns, '');
                $statusInstructions[0] = '>>>>> VALID STATUS CODES:';
                $statusInstructions[1] = implode(' | ', $statusCodes);
                fputcsv($file, $statusInstructions);
                
                $noteInstructions = array_fill(0, $totalColumns, '');
                $noteInstructions[0] = '***** NOTE: Please use only the above status codes while filling the attendance *****';
                fputcsv($file, $noteInstructions);
                
                // Empty rows for spacing
                fputcsv($file, array_fill(0, $totalColumns, ''));
                fputcsv($file, array_fill(0, $totalColumns, ''));

                // Add the header row
                fputcsv($file, $header);

                // Build query with filters
                $query = Employee::with(['emp_job_profile.department', 'emp_job_profile.employment_type'])
                    ->whereHas('emp_job_profile', function($query) {
                        $query->where('firm_id', session('firm_id'));
                    })
                    ->where('firm_id', session('firm_id'))
                    ->where('is_inactive', false);

                // Apply filters
                if (!empty($this->filters['employee_id'])) {
                    $query->whereIn('id', (array)$this->filters['employee_id']);
                }
                if (!empty($this->filters['department_id'])) {
                    $query->whereHas('emp_job_profile.department', function($q) {
                        $q->whereIn('id', (array)$this->filters['department_id']);
                    });
                }
                if (!empty($this->filters['employment_type_id'])) {
                    $query->whereHas('emp_job_profile.employment_type', function($q) {
                        $q->whereIn('id', (array)$this->filters['employment_type_id']);
                    });
                }

                $employees = $query->get();

                // Add employee rows
                foreach ($employees as $employee) {
                    if ($employee->emp_job_profile) {
                        $row = [
                            $employee->emp_job_profile->employee_code ?? '',
                            $employee->emp_job_profile->department->title ?? '',
                            $employee->emp_job_profile->employment_type->title ?? '',
                        ];
                        
                        // Add default status 'P' for all dates
                        foreach ($dates as $date) {
                            $row[] = 'P';
                        }
                        fputcsv($file, $row);
                    }
                }

                fclose($file);
            };

            return response()->streamDownload($callback, $filename, $headers);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Please select date range first'
            ]);
            throw $e;
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
            $deletedFlexiCount = 0;

            foreach ($items as $item) {
                if ($item->model_type === EmpAttendance::class) {
                    $attendance = EmpAttendance::find($item->model_id);
                    if ($attendance) {
                        // Delete associated FlexiWeekOff records first
                        $deletedFlexiRecords = FlexiWeekOff::where([
                            'firm_id' => $attendance->firm_id,
                            'employee_id' => $attendance->employee_id,
                            'availed_emp_attendance_id' => $attendance->id
                        ])->delete();
                        
                        $deletedFlexiCount += $deletedFlexiRecords;
                        
                        // Then delete the attendance record
                        $attendance->delete();
                        $deletedCount++;
                    }
                }
            }

            $batch->items()->delete();
            $batch->delete();

            DB::commit();

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => "Successfully deleted $deletedCount attendance records and $deletedFlexiCount flexi week-off records for Batch ID: $batchId"
            ]);

            // Refresh batches and selection
            $this->batches = Batch::where('modulecomponent', 'hrms.attendance.attendance-bulk-upload')
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

    public function uploadAttendance()
    {
        $this->validate([
            'csvFile' => 'required|file|mimes:csv,txt,xlsx|max:10240',
        ]);

        try {
            $this->uploadErrors = [];
            $this->uploadSuccess = false;

            DB::beginTransaction();

            $batch = new Batch();
            $batch->firm_id = session('firm_id');
            $batch->user_id = auth()->id();
            $batch->modulecomponent = 'hrms.attendance.attendance-bulk-upload';
            $batch->action = 'bulk_upload';
            $batch->title = 'Bulk Upload Attendance';
            $batch->save();

            $file = fopen($this->csvFile->getRealPath(), 'r');
            
            // Skip rows until we find the header row
            $headerFound = false;
            $header = null;
            while (!$headerFound && ($row = fgetcsv($file)) !== false) {
                // Check if this row matches our expected header pattern
                if (isset($row[0]) && isset($row[1]) && isset($row[2]) && 
                    trim($row[0]) === 'Employee Code' && 
                    trim($row[1]) === 'Department' && 
                    trim($row[2]) === 'Employment Type') {
                    $header = $row;
                    $headerFound = true;
                    break;
                }
            }

            if (!$headerFound) {
                throw new \Exception('Invalid file format: Header row not found. Please use the template provided.');
            }

            \Log::info('Attendance Upload: Header', $header);

            // Find date columns (skip first columns until the first date column)
            $dateColumns = [];
            foreach ($header as $i => $col) {
                if (preg_match('/\d{2}-[A-Za-z]{3}-\d{2,4}/', $col)) {
                    $dateColumns[$i] = $col;
                }
            }

            if (empty($dateColumns)) {
                throw new \Exception('No date columns found in the file. Please use the template provided.');
            }

            \Log::info('Attendance Upload: Date Columns', $dateColumns);
            $employeeCodeIndex = array_search('Employee Code', $header);
            if ($employeeCodeIndex === false) {
                throw new \Exception('Employee Code column not found. Please use the template provided.');
            }

            $successCount = 0;
            $errorCount = 0;
            $flexiWeekOffsCreated = 0;
            $errors = [];
            $rowNumber = 1;

            while (($row = fgetcsv($file)) !== false) {
                \Log::info('Attendance Upload: Processing Row', ['rowNumber' => $rowNumber, 'row' => $row]);
                try {
                    if (!isset($row[$employeeCodeIndex])) {
                        $rowNumber++;
                        continue;
                    }

                    $employeeCode = trim($row[$employeeCodeIndex]);
                    if (empty($employeeCode)) {
                        $rowNumber++;
                        continue;
                    }

                    // Find employee by code
                    $employee = Employee::whereHas('emp_job_profile', function($query) use ($employeeCode) {
                        $query->where('employee_code', $employeeCode)
                            ->where('firm_id', session('firm_id'));
                    })->first();

                    if (!$employee) {
                        throw new \Exception("Employee with code {$employeeCode} not found");
                    }

                    foreach ($dateColumns as $colIndex => $dateStr) {
                        if (!isset($row[$colIndex])) {
                            continue;
                        }

                        $value = trim($row[$colIndex]);
                        if ($value === '' || strtolower($value) === 'not marked') {
                            continue;
                        }

                        // Parse date
                        $workDate = \DateTime::createFromFormat('d-M-y', $dateStr);
                        if ($workDate) {
                            $year = (int)$workDate->format('Y');
                            if ($year < 100) {
                                $year += 2000;
                                $workDate->setDate($year, (int)$workDate->format('m'), (int)$workDate->format('d'));
                            }
                        } else {
                            $workDate = \DateTime::createFromFormat('d-M-Y', $dateStr);
                        }

                        if (!$workDate) {
                            throw new \Exception("Invalid date format in column: $dateStr");
                        }
                        $workDateStr = $workDate->format('Y-m-d');

                        // Check for existing attendance record
                        $existingAttendance = EmpAttendance::where('firm_id', session('firm_id'))
                            ->where('employee_id', $employee->id)
                            ->where('work_date', $workDateStr)
                            ->first();

                        // Determine attendance status
                        $status = 'A'; // Default Absent
                        $value = strtoupper($value);
                        if (isset(self::ATTENDANCE_STATUS_MAIN_SELECT[$value])) {
                            $status = $value;
                        } else {
                            foreach (self::ATTENDANCE_STATUS_MAIN_SELECT as $code => $label) {
                                if (stripos($value, $label) !== false) {
                                    $status = $code;
                                    break;
                                }
                            }
                        }

                        \Log::info('Attendance Upload: Attendance Status', ['rowNumber' => $rowNumber, 'status' => $status, 'value' => $value]);

                        $attendanceData = [
                            'firm_id' => session('firm_id'),
                            'employee_id' => $employee->id,
                            'work_date' => $workDateStr,
                            'attendance_status_main' => $status,
                            'attend_location_id' => null,
                            'ideal_working_hours' => 8.0,
                            'actual_worked_hours' => ($status === 'P' || $status === 'POW' ? 8.0 : ($status === 'HD' ? 4.0 : 0.0)),
                            'final_day_weightage' => 1.0,
                            'attend_remarks' => $value
                        ];

                        if ($existingAttendance) {
                            $oldStatus = $existingAttendance->attendance_status_main;
                            $existingAttendance->update($attendanceData);
                            $attendance = $existingAttendance;
                            $operation = 'update';

                            // Handle FlexiWeekOff based on new status
                            if ($status === 'POW') {
                                // Create FlexiWeekOff entry if it doesn't exist
                                FlexiWeekOff::firstOrCreate([
                                    'firm_id' => session('firm_id'),
                                    'employee_id' => $employee->id,
                                    'availed_emp_attendance_id' => $attendance->id,
                                ], [
                                    'attendance_status_main' => 'W',
                                    'consumed_emp_attendance_id' => null,
                                    'week_off_Status' => 'A'
                                ]);
                                $flexiWeekOffsCreated++;
                            } 
                            // Delete FlexiWeekOff if status is changed from POW to something else
                            elseif ($oldStatus === 'POW') {
                                FlexiWeekOff::where([
                                    'firm_id' => session('firm_id'),
                                    'employee_id' => $employee->id,
                                    'availed_emp_attendance_id' => $attendance->id
                                ])->delete();
                            }
                        } else {
                            $attendance = EmpAttendance::create($attendanceData);
                            $operation = 'insert';

                            // Create FlexiWeekOff entry if status is POW
                            if ($status === 'POW') {
                                FlexiWeekOff::create([
                                    'firm_id' => session('firm_id'),
                                    'employee_id' => $employee->id,
                                    'attendance_status_main' => 'W',
                                    'availed_emp_attendance_id' => $attendance->id,
                                    'consumed_emp_attendance_id' => null,
                                    'week_off_Status' => 'A'
                                ]);
                                $flexiWeekOffsCreated++;
                            }
                        }

                        BatchItem::create([
                            'batch_id' => $batch->id,
                            'operation' => $operation,
                            'model_type' => EmpAttendance::class,
                            'model_id' => $attendance->id,
                            'new_data' => json_encode($attendance->toArray())
                        ]);
                        $successCount++;
                    }
                } catch (\Exception $e) {
                    $errorCount++;
                    $errors[] = "Row $rowNumber: " . implode(',', $row) . " - " . $e->getMessage();
                    \Log::error('Attendance Upload: Exception', ['rowNumber' => $rowNumber, 'error' => $e->getMessage(), 'row' => $row]);
                }
                $rowNumber++;
            }
            fclose($file);

            \Log::info('Attendance Upload: Summary', ['successCount' => $successCount, 'errorCount' => $errorCount, 'flexiWeekOffsCreated' => $flexiWeekOffsCreated, 'errors' => $errors]);

            if ($successCount > 0 && $errorCount == 0) {
                DB::commit();
                $this->currentBatch = $batch;
                $this->uploadSuccess = true;
                $this->dispatch('notify', [
                    'type' => 'success',
                    'message' => "Successfully imported $successCount attendance records and created $flexiWeekOffsCreated flexi week-off records."
                ]);
            } elseif ($successCount > 0 && $errorCount > 0) {
                DB::commit();
                $this->currentBatch = $batch;
                $this->uploadSuccess = true;
                $this->uploadErrors = $errors;
                $this->dispatch('notify', [
                    'type' => 'warning',
                    'message' => "Imported $successCount records (with $flexiWeekOffsCreated flexi week-offs), but $errorCount failed. See errors below."
                ]);
            } else {
                DB::rollBack();
                $this->uploadErrors = $errors;
                $this->dispatch('notify', [
                    'type' => 'error',
                    'message' => "Failed to import any records. Please check the errors below."
                ]);
            }

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Attendance Upload: Fatal Exception', ['error' => $e->getMessage()]);
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    protected function initListsForFields(): void
    {
        $this->listsForFields['employees'] = Employee::select('id', 'fname', 'lname')->where('firm_id',session('firm_id'))
            ->get()
            ->pluck('fname', 'id')
            ->toArray();

        $this->listsForFields['departments'] = EmployeeJobProfile::with('department')->where('firm_id',session('firm_id'))
            ->get()
            ->pluck('department.title', 'department.id')
            ->unique()
            ->filter()
            ->toArray();

        $this->listsForFields['employment_types'] = EmployeeJobProfile::with('employment_type')->where('firm_id',session('firm_id'))
            ->get()
            ->pluck('employment_type.title', 'employment_type.id')
            ->unique()
            ->filter()
            ->toArray();
    }

    public function export()
    {
        try {
            $validated = $this->validate([
                'filters.date_range.start' => 'required|date',
                'filters.date_range.end' => 'required|date|after_or_equal:filters.date_range.start',
            ]);

            $filename = 'attendance_report.csv';
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];

            $callback = function() {
                $file = fopen('php://output', 'w');

                // Get dates from filters
                $start = Carbon::parse($this->filters['date_range']['start']);
                $end = Carbon::parse($this->filters['date_range']['end']);
                $dates = [];
                while ($start->lte($end)) {
                    $dates[] = $start->format('d-M-Y');
                    $start->addDay();
                }

                // Prepare header row
                $header = [
                    'Employee Code',
                    'Department',
                    'Employment Type',
                ];
                $header = array_merge($header, $dates);
                $totalColumns = count($header);

                // Add instructions at the top with proper formatting
                $instructionHeader = array_fill(0, $totalColumns, '');
                $instructionHeader[0] = '!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!';
                fputcsv($file, $instructionHeader);
                
                $mainHeader = array_fill(0, $totalColumns, '');
                $mainHeader[0] = '!!!           ATTENDANCE REPORT           !!!';
                fputcsv($file, $mainHeader);
                
                $instructionFooter = array_fill(0, $totalColumns, '');
                $instructionFooter[0] = '!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!';
                fputcsv($file, $instructionFooter);
                
                // Empty row for spacing
                fputcsv($file, array_fill(0, $totalColumns, ''));
                
                // Status codes instruction
                $statusCodes = [];
                foreach (self::ATTENDANCE_STATUS_MAIN_SELECT as $code => $label) {
                    $statusCodes[] = "[$code = $label]";
                }
                
                $statusInstructions = array_fill(0, $totalColumns, '');
                $statusInstructions[0] = '>>>>> VALID STATUS CODES:';
                $statusInstructions[1] = implode(' | ', $statusCodes);
                fputcsv($file, $statusInstructions);
                
                $noteInstructions = array_fill(0, $totalColumns, '');
                $noteInstructions[0] = '***** NOTE: Please use only the above status codes while filling the attendance *****';
                fputcsv($file, $noteInstructions);
                
                // Empty rows for spacing
                fputcsv($file, array_fill(0, $totalColumns, ''));
                fputcsv($file, array_fill(0, $totalColumns, ''));

                // Add the header row
                fputcsv($file, $header);

                // Query to get employees with their attendance data
                $query = Employee::with([
                    'emp_job_profile.department',
                    'emp_job_profile.employment_type',
                    'emp_attendances' => fn($q) => $q->whereBetween('work_date', [
                        $this->filters['date_range']['start'],
                        $this->filters['date_range']['end']
                    ])
                ])
                ->where('firm_id', session('firm_id'));

                // Apply filters
                if (!empty($this->filters['employee_id'])) {
                    $query->whereIn('id', (array)$this->filters['employee_id']);
                }
                if (!empty($this->filters['department_id'])) {
                    $query->whereHas('emp_job_profile.department', function($q) {
                        $q->whereIn('id', (array)$this->filters['department_id']);
                    });
                }
                if (!empty($this->filters['employment_type_id'])) {
                    $query->whereHas('emp_job_profile.employment_type', function($q) {
                        $q->whereIn('id', (array)$this->filters['employment_type_id']);
                    });
                }

                $employees = $query->get();

                // Add employee rows with attendance data
                foreach ($employees as $employee) {
                    if ($employee->emp_job_profile) {
                        $attendances = $employee->emp_attendances->keyBy(fn($a) => $a->work_date->format('d-M-Y'));
                        
                        $row = [
                            $employee->emp_job_profile->employee_code ?? '',
                            $employee->emp_job_profile->department->title ?? '',
                            $employee->emp_job_profile->employment_type->title ?? '',
                        ];
                        
                        // Add attendance status for each date
                        foreach ($dates as $date) {
                            $row[] = $attendances[$date]->attendance_status_main ?? '-';
                        }
                        fputcsv($file, $row);
                    }
                }

                fclose($file);
            };

            return response()->streamDownload($callback, $filename, $headers);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Please select date range first'
            ]);
            throw $e;
        }
    }

    public function render()
    {
        return view()->file(app_path('Livewire/Hrms/Attendance/blades/attendance-bulk-upload.blade.php'));
    }
} 