<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\LoginController as UserLoginController;
use App\Http\Controllers\Admin\Auth\LoginController as AdminLoginController;
use App\Http\Controllers\CorrectionRequestController;
use App\Http\Controllers\RequestController;
use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\StaffAttendanceController;
use App\Http\Controllers\Admin\RequestController as AdminRequestController;

Route::get('/', function () {
    return redirect('/login');
});

// ユーザーログイン・登録
Route::post('/register', [RegisterController::class, 'store'])->name('register');

Route::post('/login', [UserLoginController::class, 'store'])->name('login');

Route::middleware('auth')->group(function () {
    Route::get('/attendance', [AttendanceController::class, 'create'])->name('attendance.create');
    Route::post('/attendance', [AttendanceController::class, 'store'])->name('attendance.store');

    Route::get('/attendance/list', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::get('/attendance/detail/{attendance}', [AttendanceController::class, 'show'])->name('attendance.show');

    Route::post('/attendance/{attendance}/request-correction', [CorrectionRequestController::class, 'store'])
        ->name('attendance.requestCorrection');

    Route::get('/stamp_correction_request/list', [RequestController::class, 'index'])
        ->name('requests.index');
});

// 管理者用ルート
Route::prefix('admin')->name('admin.')->group(function () {
    // ログイン系
    Route::get('/login', [AdminLoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AdminLoginController::class, 'login'])->name('login.submit');
    Route::post('/logout', [AdminLoginController::class, 'logout'])->name('logout');

    // 管理者専用ページ（ログイン必須）
    Route::middleware('auth:admin')->group(function () {
        Route::get('/attendances', [AdminAttendanceController::class, 'index'])
            ->name('attendance.index');

        Route::get('/attendances/{attendance}', [AdminAttendanceController::class, 'show'])
            ->name('attendance.show');
        Route::patch('/attendances/{attendance}', [AdminAttendanceController::class, 'update'])
            ->name('attendance.update');

        Route::get('/users', [StaffController::class, 'index'])
            ->name('staff.index');

        Route::get('/users/{user}/attendances', [StaffAttendanceController::class, 'index'])
            ->name('staff.attendance');
        Route::get('/users/{user}/attendances/csv', [StaffAttendanceController::class, 'exportCsv'])
            ->name('staff.attendance.csv');

        Route::get('/requests', [AdminRequestController::class, 'index'])->name('requests.index');

        Route::get('/requests/{submissionId}', [AdminRequestController::class, 'show'])->name('requests.show');
        Route::post('/requests/{submissionId}/approve', [AdminRequestController::class, 'approve'])
            ->name('requests.approve');
    });
});
