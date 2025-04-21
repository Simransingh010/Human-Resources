<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\Saas\AuthController;
use App\Http\Controllers\API\Saas\PanelController;
use App\Http\Controllers\API\Saas\FirmController;
use App\Http\Controllers\API\Saas\MenuController;
use App\Http\Controllers\API\Saas\SystemInfoController;
use App\Http\Controllers\API\Hrms\Attendance\AttendanceController;

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
});
