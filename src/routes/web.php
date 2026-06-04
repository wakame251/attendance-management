<?php

use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\StampCorrectionRequestController;
use App\Http\Controllers\AdminAttendanceController;
use App\Http\Controllers\AdminStaffController;
use App\Http\Controllers\AdminStampCorrectionRequestController;


Route::get('/admin/login', function () {
    return view('admin_login');
})->name('admin.login');

Route::get('/email/verify', function () {
    return view('auth.verify-email'); // ★自作ビュー
})->middleware('auth')->name('verification.notice');

Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();
    return redirect('/attendance'); // 認証後の遷移先
})->middleware(['auth', 'signed'])->name('verification.verify');

Route::post('/email/verification-notification', function (Request $request) {
    $request->user()->sendEmailVerificationNotification();
    return back()->with('message', '認証メールを再送しました');
})->middleware(['auth', 'throttle:6,1'])->name('verification.send');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/attendance', [AttendanceController::class, 'create'])
        ->name('attendance.create');

    Route::get('/attendance/list', [AttendanceController::class, 'index'])
        ->name('attendance.index');

    Route::get('/attendance/detail/{id}', [AttendanceController::class, 'edit'])
        ->name('attendance.edit');

    Route::patch('/attendance/detail/{id}', [AttendanceController::class, 'update'])
        ->name('attendance.update');

    Route::post('/attendance/clock-in', [AttendanceController::class, 'clockIn'])
        ->name('attendance.clock_in');

    Route::post('/attendance/clock-out', [AttendanceController::class, 'clockOut'])
        ->name('attendance.clock_out');

    Route::post('/attendance/break-start', [AttendanceController::class, 'breakStart'])
        ->name('attendance.break_start');

    Route::post('/attendance/break-end', [AttendanceController::class, 'breakEnd'])
        ->name('attendance.break_end');

    Route::get('/stamp_correction_request/list', [StampCorrectionRequestController::class, 'index'])
        ->name('stamp_correction_requests.user.index');
});

Route::middleware('auth:admin')->group(function () {

    Route::get('/admin/attendance/list', [AdminAttendanceController::class, 'index'])
        ->name('admin.attendance.index');

    Route::get('/admin/attendance/{id}', [AdminAttendanceController::class, 'edit'])
        ->name('admin.attendance.edit');

    Route::patch('/admin/attendance/{id}', [AdminAttendanceController::class, 'update'])
        ->name('admin.attendance.update');

    Route::get('/admin/staff/list', [AdminStaffController::class, 'index'])
        ->name('admin.staff.index');

    Route::get('/admin/attendance/staff/{id}', [AdminStaffController::class, 'show'])
        ->name('admin.staff.attendance');

    Route::get('/admin/attendance/staff/{id}/csv', [AdminStaffController::class, 'exportCsv'])
        ->name('admin.staff.attendance.csv');

    Route::get('/admin/stamp_correction_request/list',
        [AdminStampCorrectionRequestController::class,'index'])
        ->name('stamp_correction_requests.admin.index');

    Route::get('/admin/stamp_correction_request/approve/      {attendance_correct_request_id}',
        [AdminStampCorrectionRequestController::class,'create'])
        ->name('stamp_correction_requests.admin.create');

    Route::post('/admin/stamp_correction_request/approve/{attendance_correct_request_id}',
        [AdminStampCorrectionRequestController::class,'store'])->name('stamp_correction_requests.admin.store');
});