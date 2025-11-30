<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\Saas\AuthController;
use App\Http\Controllers\API\Saas\PanelController;
use App\Http\Controllers\API\Saas\FirmController;
use App\Http\Controllers\API\Saas\MenuController;
use App\Http\Controllers\API\Saas\SystemInfoController;
use App\Http\Controllers\API\Hrms\Attendance\AttendanceController;
use App\Http\Controllers\API\Hrms\Leave\LeaveController;
use App\Http\Controllers\API\Hrms\Onboard\OnboardController;
use App\Http\Controllers\API\Hrms\Payroll\PayrollController;
use App\Http\Controllers\API\Hrms\Students\StudentAttendanceController;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/loginmobile', [AuthController::class, 'loginmobile']);
Route::post('/send-otp', [AuthController::class, 'sendOtp']);




Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/panels', [PanelController::class, 'index']);
    Route::get('/firms', [FirmController::class, 'index']);
    Route::get('/menu', [MenuController::class, 'index']);
    Route::post('/addDeviceToken', [AuthController::class, 'addDeviceToken']);



    Route::get('/system-info', [SystemInfoController::class, 'getSystemInfo']);
    Route::post('/system-usage', [SystemInfoController::class, 'saveSystemUsage']);

    Route::post('/attendance/punch', [AttendanceController::class, 'punch']);
    Route::get('/attendance/punchStatus', [AttendanceController::class, 'punchStatus']);
    Route::get('/attendance/attendanceWithPunches', [AttendanceController::class, 'attendanceWithPunches']);
    Route::get('/attendance/available-week-offs', [AttendanceController::class, 'availableWeekOffs']);
    Route::post('/attendance/apply-week-off', [AttendanceController::class, 'applyWeekOff']);
    Route::get('/attendance/statuses', [AttendanceController::class, 'getAttendanceStatuses']);
    Route::post('/attendance/mark-status', [AttendanceController::class, 'markAttendanceStatus']);

    Route::get('/attendance/last-week-offs', [AttendanceController::class, 'lastWeekOffs']);

    Route::get('hrms/leave/leave-balances', [LeaveController::class, 'leavesBalances']);
    Route::get('hrms/leave/leave-balances-v2', [LeaveController::class, 'leavesBalancesV2']);
    Route::post('hrms/leave/submitleaverequest', [LeaveController::class, 'submitLeaveRequest']);
    Route::post('hrms/leave/submitleaverequestv2', [LeaveController::class, 'submitLeaveRequestv2']);
    Route::post('hrms/leave/submitleaverequestv3', [LeaveController::class, 'submitLeaveRequestV3']);
    Route::get('hrms/leave/leave-requests', [LeaveController::class, 'leaveRequests']);
    Route::get('hrms/leave/team-leaves', [LeaveController::class, 'getTeamLeaves']);
    Route::post('hrms/leave/leave-action', [LeaveController::class, 'handleLeaveAction']);
    Route::post('hrms/leave/bulk-leave-action', [LeaveController::class, 'handleBulkLeaveAction']);
    Route::get('hrms/leave/pol-attendances', [LeaveController::class, 'getPolAttendancesForApprover']);
    Route::post('hrms/leave/pol-attendance-action', [LeaveController::class, 'handlePolAttendanceAction']);
    Route::post('hrms/leave/off-days-in-range', [LeaveController::class, 'getOffDaysInRange']);
    Route::get('hrms/colleges', [LeaveController::class, 'getColleges']);
    Route::get('hrms/employee-details', [LeaveController::class, 'getEmployeeDetails']);

    Route::get('/hrms/employees/job-profile', [OnboardController::class, 'getEmployeeJobProfile']);
    Route::post('/hrms/employees/post-job-profile', [OnboardController::class, 'saveEmployeeJobProfile']);
    Route::post('/hrms/employees/post-personal-details', [OnboardController::class, 'saveEmployeePersonalDetail']);
//    Route::post('/hrms/employees/post-personal-details-one', [OnboardController::class, 'saveEmployeePersonalDetailsNew']);
    
    Route::post('/hrms/employees/post-bank-account', [OnboardController::class, 'saveEmployeeBankAccount']);
    Route::get('/hrms/employees/personal-details', [OnboardController::class, 'getEmployeePersonalDetail']);
//    Route::get('/hrms/employees/personal-details-one', [OnboardController::class, 'getEmployeePersonalDetailNew']);
    Route::get('/hrms/employees/bank-account', [OnboardController::class, 'getEmployeeBankAccount']);
    Route::get('/hrms/employees/contacts', [OnboardController::class, 'getEmployeeContacts']);
    Route::post('/hrms/employees/post-contacts', [OnboardController::class, 'saveEmployeeContacts']);
    Route::post('/hrms/employees/delete-contact', [OnboardController::class, 'deleteEmployeeContact']);
    Route::get('/hrms/contact-types', [OnboardController::class, 'getContactTypes']);
    Route::get('/hrms/departments', [OnboardController::class, 'getDepartments']);
    Route::get('/hrms/designations', [OnboardController::class, 'getDesignations']);
    Route::get('/hrms/employment-types', [OnboardController::class, 'getEmploymentTypes']);
    Route::get('/hrms/reporting-managers', [OnboardController::class, 'getReportingManagers']);
    Route::get('/hrms/job-locations', [OnboardController::class, 'getJobLocations']);

    Route::get('/hrms/employees/docs', [OnboardController::class, 'getEmployeeDocs']);
    Route::post('/hrms/employees/post-doc', [OnboardController::class, 'saveEmployeeDoc']);
    Route::post('/hrms/employees/delete-doc', [OnboardController::class, 'deleteEmployeeDoc']);
//    Route::post('/hrms/employees/post-delete-doc', [OnboardController::class, 'postDeleteEmployeeDoc']);

    Route::get('/hrms/document-types', [OnboardController::class, 'getDocumentTypes']);

    Route::get('/hrms/relations', [OnboardController::class, 'getRelations']);

    Route::get('/hrms/employees/employee-relations', [OnboardController::class, 'getEmployeeRelations']);
    Route::post('/hrms/employees/post-employee-relation', [OnboardController::class, 'saveEmployeeRelation']);
    Route::post('/hrms/employees/delete-employee-relation', [OnboardController::class, 'deleteEmployeeRelation']);
    Route::get('/hrms/employees/profile-completion', [OnboardController::class, 'getProfileCompletion']);

    // Payroll related routes
    Route::get('/hrms/employees/holidays', [PayrollController::class, 'getEmployeeHolidays']);
    Route::get('/hrms/employees/salary-structure', [PayrollController::class, 'getEmployeeSalaryStructure']);
    Route::get('/hrms/employees/payroll-slots', [PayrollController::class, 'getEmployeePayrollSlots']);
    Route::get('/hrms/employees/payroll-components', [PayrollController::class, 'getEmployeePayrollComponents']);
    Route::post('/payroll/salary-slip/download', [PayrollController::class, 'downloadSalarySlipPdf']);

    // Student attendance & punches
    Route::post('/students/attendance/punch', [StudentAttendanceController::class, 'punch']);
    Route::get('/students/attendance/punch-status', [StudentAttendanceController::class, 'punchStatus']);
    Route::get('/students/attendance', [StudentAttendanceController::class, 'attendanceWithPunches']);
    
   
    
    Route::get('/students/attendance/coach/students', [StudentAttendanceController::class, 'coachStudents']);
    Route::post('/students/attendance/coach/mark', [StudentAttendanceController::class, 'coachMarkAttendance']);
    Route::get('/students/details', [StudentAttendanceController::class, 'studentDetails']);
});
 