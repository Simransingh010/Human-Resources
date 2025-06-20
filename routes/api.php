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

Route::post('/login', [AuthController::class, 'login']);
Route::post('/loginmobile', [AuthController::class, 'loginmobile']);
Route::post('/send-otp', [AuthController::class, 'sendOtp']);

Route::middleware('auth:sanctum')->group(function () {
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

    Route::get('hrms/leave/leave-balances', [LeaveController::class, 'leavesBalances']);
    Route::post('hrms/leave/submitleaverequest', [LeaveController::class, 'submitLeaveRequest']);
    Route::post('hrms/leave/submitleaverequestv2', [LeaveController::class, 'submitLeaveRequestv2']);
    Route::get('hrms/leave/leave-requests', [LeaveController::class, 'leaveRequests']);
    Route::get('hrms/leave/team-leaves', [LeaveController::class, 'getTeamLeaves']);
    Route::post('hrms/leave/leave-action', [LeaveController::class, 'handleLeaveAction']);

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

});
 