<?php


use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');
Route::get('iim-sirmaur', function (){
    return view('index-page');
})->name('iim-sirmaur');
Route::redirect('/', '/login');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {

    Route::get('onboard-dashboard',App\Livewire\Hrms\Onboard\OnboardDashboard::class)->name('onboard-dashboard');
    Route::get('/hrms/onboard/employees',App\Livewire\Hrms\Onboard\Employees::class)->name('hrms.onboard.employee');
    Route::get('/agencies',App\Livewire\Saas\Agencies\Index::class)->name('agencies.index');
    Route::get('/hrms/employee-addresses', App\Livewire\Hrms\EmployeesMeta\EmployeeAddresses::class)->name('employee-addresses.index');
    Route::get('/hrms/employee-bank-accounts', App\Livewire\Hrms\EmployeesMeta\EmployeeBankAccounts::class)->name('employee-bank-accounts.index');
    Route::get('/hrms/employee-contacts', App\Livewire\Hrms\EmployeesMeta\EmployeeContacts::class)->name('employee-contacts.index');
    Route::get('/hrms/employee-docs', App\Livewire\Hrms\EmployeesMeta\EmployeeDocs::class)->name('employee-docs.index');
    Route::get('/hrms/employee-job-profiles', App\Livewire\Hrms\EmployeesMeta\EmployeeJobProfiles::class)->name('employee-job-profiles.index');
    Route::get('/hrms/employee-personal-details', App\Livewire\Hrms\EmployeesMeta\EmployeePersonalDetails::class)->name('employee-personal-details.index');
    Route::get('/hrms/employee-relations', App\Livewire\Hrms\EmployeesMeta\EmployeeRelations::class)->name('employee-relations.index');
    Route::get('/hrms/attendance/attendance-policies', App\Livewire\Hrms\Attendance\AttendancePolicies::class)->name('attendance-policies.index');
    Route::get('/hrms/attendance/leave-types', App\Livewire\Hrms\Attendance\LeaveTypes::class)->name('leave-types.index');
    Route::get('/hrms/attendance/leaves-quota-template-setups', App\Livewire\Hrms\Attendance\LeavesQuotaTemplateSetups::class)->name('leaves-quota-template-setups.index');
    Route::get('/hrms/attendance/emp-leave-allocations', App\Livewire\Hrms\Attendance\EmpLeaveAllocations::class)->name('emp-leave-allocations.index');
    Route::get('/hrms/attendance//emp-leave-request-logs', App\Livewire\Hrms\Attendance\EmpLeaveRequestLogs::class)->name('emp-leave-request-logs.index');
    
    Route::get('/hrms/onboard/work-breaks', App\Livewire\Hrms\Onboard\WorkBreaks::class)->name('hrms.onboard.work-breaks');
    Route::get('/hrms/onboard/work-shifts', App\Livewire\Hrms\Onboard\WorkShifts::class)->name('hrms.onboard.work-shifts');
    Route::get('/work-shift-days', App\Livewire\Hrms\Attendance\WorkShiftDays::class)->name('work-shift-days.index');
    Route::get('/work-shift-days-breaks', App\Livewire\Hrms\Attendance\WorkShiftDaysBreaks::class)->name('work-shift-days-breaks.index');
    Route::get('/work-shifts-algos', App\Livewire\Hrms\Attendance\WorkShiftsAlgos::class)->name('work-shifts-algos.index');
    Route::get('/emp-leave-requests', App\Livewire\Hrms\Attendance\EmpLeaveRequests::class)->name('employee-leave-requests.index');
    Route::get('/hrms/attendance/emp-attendances', App\Livewire\Hrms\Attendance\EmpAttendances::class)->name('hrms.attendance.emp-attendances');
    Route::get('emp-work-shifts', App\Livewire\Hrms\Attendance\EmpWorkShifts::class)->name('emp-work-shifts.index');
    Route::get('/emp-punches', App\Livewire\Hrms\Attendance\EmpPunches::class)->name('emp-punches.index');
    Route::get('/todays-attendance', App\Livewire\Hrms\Attendance\TodayAttendanceStats::class)->name('todays-attendance');
    Route::get('/hrms/onboard/holiday-calendars',App\Livewire\Hrms\Onboard\HolidayCalendars::class)->name('hrms.onboard.holiday-calendars');

    Route::get('/saas/firms', App\Livewire\Saas\Firms::class)->name('saas.firms');
    Route::get('/saas/users', App\Livewire\Saas\Users::class)->name('saas.users');
    Route::get('/saas/panels', App\Livewire\Saas\Panels::class)->name('saas.panels');
    Route::get('/saas/apps', App\Livewire\Saas\Apps::class)->name('saas.apps');
    Route::get('/saas/versions', App\Livewire\Saas\Versions::class)->name('saas.versions');
    Route::get('/saas/module-groups', App\Livewire\Saas\ModuleGroups::class)->name('saas.module-groups');
    Route::get('/saas/actionclusters', App\Livewire\Saas\Actionclusters::class)->name('saas.actionclusters');
    Route::get('/saas/moduleclusters', App\Livewire\Saas\Moduleclusters::class)->name('saas.moduleclusters');
    Route::get('/saas/componentclusters', App\Livewire\Saas\Componentclusters::class)->name('saas.componentclusters');
    Route::get('/saas/panel-structuring', App\Livewire\Saas\PanelStructuring::class)->name('saas.panel-structuring');
    Route::get('/saas/permissions', App\Livewire\Saas\Components::class)->name('saas.permissions');
    Route::get('/saas/permission-groups', App\Livewire\Saas\PermissionGroups::class)->name('saas.permission-groups');
    Route::get('/saas/app-modules', App\Livewire\Saas\AppsMeta\AppModules::class)->name('saas.app-modules');
    Route::get('/saas/firm-branding', App\Livewire\Saas\FirmBrandings::class)->name('saas.firm-branding');
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');
});

require __DIR__.'/auth.php';
