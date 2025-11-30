<?php

// Quick debug script to test the coach students endpoint
// Run with: php test-coach-api.php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    echo "Testing coach students endpoint...\n\n";
    
    // Get the first user with an employee record
    $user = \App\Models\User::whereHas('employee')->first();
    
    if (!$user) {
        echo "ERROR: No user with employee record found in database\n";
        exit(1);
    }
    
    echo "Found user: {$user->email} (ID: {$user->id})\n";
    
    $employee = $user->employee;
    echo "Employee ID: {$employee->id}\n";
    echo "Employee Name: {$employee->fname} {$employee->lname}\n";
    echo "Employee Firm ID: {$employee->firm_id}\n\n";
    
    // Check for study groups
    $groups = \App\Models\Hrms\StudyGroup::where('coach_id', $employee->id)
        ->where('is_active', true)
        ->get();
    
    echo "Study Groups assigned to coach: " . $groups->count() . "\n";
    
    if ($groups->isEmpty()) {
        echo "No study groups found. Checking for direct student assignments...\n";
        
        $directStudents = \App\Models\Hrms\Student::where('firm_id', $employee->firm_id)
            ->where('is_inactive', false)
            ->whereHas('student_education_detail', function($q) use ($employee) {
                $q->where('reporting_coach_id', $employee->id);
            })
            ->count();
        
        echo "Direct student assignments: {$directStudents}\n";
    } else {
        foreach ($groups as $group) {
            echo "  - Group: {$group->name} (Study Centre ID: {$group->study_centre_id})\n";
        }
        
        $studyCentreIds = $groups->pluck('study_centre_id')->filter()->unique()->toArray();
        echo "\nStudy Centre IDs: " . implode(', ', $studyCentreIds) . "\n";
        
        if (!empty($studyCentreIds)) {
            $students = \App\Models\Hrms\Student::where('firm_id', $employee->firm_id)
                ->where('is_inactive', false)
                ->whereHas('student_education_detail', function($q) use ($studyCentreIds) {
                    $q->whereIn('study_centre_id', $studyCentreIds);
                })
                ->count();
            
            echo "Students in those study centres: {$students}\n";
        }
    }
    
    echo "\n✓ Test completed successfully\n";
    
} catch (\Exception $e) {
    echo "\n✗ ERROR: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
