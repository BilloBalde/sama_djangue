<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\NoteController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\ClasseController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\SubjectController;
use App\Http\Controllers\Api\TeacherController;
use App\Http\Controllers\Api\ScheduleController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Students
    Route::apiResource('students', StudentController::class);
    Route::apiResource('notes', NoteController::class);
    Route::apiResource('classes', ClasseController::class);
    Route::apiResource('subjects', SubjectController::class);
    Route::apiResource('payments', PaymentController::class);

    // Teacher
    Route::post('/grades', [TeacherController::class, 'storeGrade']);
    Route::get('/class-statistics/{classId}', [TeacherController::class, 'classStatistics']);
    Route::post('/assignments', [TeacherController::class, 'storeAssignment']);
    Route::get('/submissions/{assignmentId}', [TeacherController::class, 'getSubmissions']);

    // Admin
    Route::get('/dashboard-analytics', [AdminController::class, 'dashboardAnalytics']);
    Route::post('/school-years', [AdminController::class, 'storeSchoolYear']);
    Route::get('/school-years', [AdminController::class, 'getSchoolYears']);
    Route::post('/settings', [AdminController::class, 'updateSettings']);
    Route::get('/settings', [AdminController::class, 'getSettings']);

    // Notes
    Route::get('/notes/average/{student_id}', [NoteController::class, 'calculateAverage']);

    // Payments
    Route::post('/payments/{id}/confirm', [PaymentController::class, 'confirm']);

    // Schedules
    Route::get('/schedules/{class_id}', [ScheduleController::class, 'index']);
    Route::post('/schedules', [ScheduleController::class, 'store']);

    // Messages
    Route::get('/messages', [MessageController::class, 'index']);
    Route::post('/messages', [MessageController::class, 'store']);
    Route::post('/messages/{id}/read', [MessageController::class, 'markAsRead']);
});
