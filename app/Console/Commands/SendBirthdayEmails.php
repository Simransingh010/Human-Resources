<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Hrms\EmployeePersonalDetail;
use App\Models\NotificationQueue;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SendBirthdayEmails extends Command
{
    protected $signature = 'birthdays:send-emails {--firm-id=} {--dry-run}';
    protected $description = 'Send birthday emails to employees whose birthday is today';

    public function handle()
    {
        $this->info('Starting birthday email process...');
        
        $firmId = $this->option('firm-id');
        $isDryRun = $this->option('dry-run');
        
        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No emails will be sent');
        }

        try {
            // Get today's date
            $today = Carbon::today();
            $day = $today->day;
            $month = $today->month;
            
            $this->info("Checking for birthdays on {$today->format('jS F Y')}");

            // Build query to find employees with birthdays today
            $query = EmployeePersonalDetail::query()
                ->with([
                    'employee' => function ($query) use ($firmId) {
                        $query->where('is_inactive', false);
                        if ($firmId) {
                            $query->where('firm_id', $firmId);
                        }
                    },
                    'employee.user',
                    'employee.firm'
                ])
                ->whereMonth('dob', $month)
                ->whereDay('dob', $day)
                ->whereNotNull('dob');

            // If firm_id is specified, filter by it
            if ($firmId) {
                $query->whereHas('employee', function ($q) use ($firmId) {
                    $q->where('firm_id', $firmId);
                });
            }

            $birthdayEmployees = $query->get();

            if ($birthdayEmployees->isEmpty()) {
                $this->info('No birthdays found today.');
                return 0;
            }

            $this->info("Found {$birthdayEmployees->count()} employee(s) with birthdays today.");

            $processedCount = 0;
            $skippedCount = 0;
            $errorCount = 0;

            foreach ($birthdayEmployees as $employeeDetail) {
                try {
                    $employee = $employeeDetail->employee;
                    
                    if (!$employee || !$employee->user) {
                        $this->warn("Skipping employee {$employeeDetail->id} - no user account found");
                        $skippedCount++;
                        continue;
                    }

                    // Check if birthday email was already sent today to avoid duplicates
                    $existingNotification = NotificationQueue::where('firm_id', $employee->firm_id)
                        ->where('notifiable_type', User::class)
                        ->where('notifiable_id', $employee->user->id)
                        ->where('status', '!=', 'failed')
                        ->whereJsonContains('data', ['type' => 'birthday'])
                        ->whereDate('created_at', $today)
                        ->first();

                    if ($existingNotification) {
                        $this->info("Birthday email already sent today for {$employee->fname} {$employee->lname}");
                        $skippedCount++;
                        continue;
                    }

                    // Calculate age
                    $age = Carbon::parse($employeeDetail->dob)->age;
                    
                    // Prepare email payload
                    $payload = [
                        'firm_id' => $employee->firm_id,
                        'subject' => 'Happy Birthday! 🎉',
                        'message' => "Dear {$employee->fname} {$employee->lname},\n\n" .
                                   "Wishing you a fantastic birthday filled with joy, laughter, and wonderful memories!\n\n" .
                                   "We're grateful to have you as part of our team.\n\n" .
                                   "May this year bring you success, happiness, and all the things you've been wishing for.\n\n" .
                                   "Happy Birthday! 🎂🎈\n\n" .
                                   "Best regards,\n" .
                                   "The {$employee->firm->name} Team",
                        'company_name' => $employee->firm->name ?? 'Company',
                        'type' => 'birthday',
                        'employee_name' => "{$employee->fname} {$employee->lname}",
                      
                    ];

                    if (!$isDryRun) {
                        // Create notification in queue
                        NotificationQueue::create([
                            'firm_id' => $employee->firm_id,
                            'notifiable_type' => User::class,
                            'notifiable_id' => $employee->user->id,
                            'channel' => 'mail',
                            'data' => json_encode($payload),
                            'status' => 'pending',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        $this->info("✓ Birthday email queued for {$employee->fname} {$employee->lname} (Age: {$age})");
                    } else {
                        $this->info("✓ [DRY RUN] Would queue birthday email for {$employee->fname} {$employee->lname} (Age: {$age})");
                    }

                    $processedCount++;

                } catch (\Exception $e) {
                    $this->error("Error processing employee {$employeeDetail->id}: " . $e->getMessage());
                    Log::error("Birthday email error for employee {$employeeDetail->id}: " . $e->getMessage(), [
                        'employee_id' => $employeeDetail->employee_id,
                        'firm_id' => $employeeDetail->firm_id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    $errorCount++;
                }
            }

            // Summary
            $this->newLine();
            $this->info('=== Birthday Email Summary ===');
            $this->info("Total employees with birthdays: {$birthdayEmployees->count()}");
            $this->info("Processed: {$processedCount}");
            $this->info("Skipped: {$skippedCount}");
            $this->info("Errors: {$errorCount}");
            
            if ($isDryRun) {
                $this->warn('DRY RUN COMPLETED - No actual emails were sent');
            } else {
                $this->info('Birthday emails have been queued for processing.');
            }

            return 0;

        } catch (\Exception $e) {
            $this->error("Fatal error: " . $e->getMessage());
            Log::error("Birthday email command fatal error: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }
}
