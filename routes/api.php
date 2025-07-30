<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\NoteController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\ClasseController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\SubjectController;
use App\Http\Controllers\Api\TeacherController;
use App\Http\Controllers\Api\ClassFeeController;
use App\Http\Controllers\Api\ScheduleController;
use App\Http\Controllers\Api\FeePeriodController;
use App\Http\Controllers\Api\AttendanceController;

Route::prefix('auth')->group(function () {
    // Routes publiques
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    // Routes protégées
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/user', [AuthController::class, 'user'])->middleware('role:admin|accountant|teacher|parent|student');
        Route::post('/logout', [AuthController::class, 'logout'])->middleware('role:admin|accountant|teacher|parent|student');
        Route::get('/tutors', [AuthController::class, 'tutors'])->middleware('role:admin|accountant|teacher|parent|student');
        Route::get('/teachers', [AuthController::class, 'teachers'])->middleware('role:admin|accountant|teacher|parent|student');
    });
});

Route::prefix('admin')->middleware('auth:sanctum')->group(function () {
    Route::get('/dashboard-analytics', [AdminController::class, 'dashboardAnalytics'])->middleware('role:admin');
    Route::post('/school-years', [AdminController::class, 'storeSchoolYear'])->middleware('role:admin');
    Route::get('/school-years', [AdminController::class, 'getSchoolYears'])->middleware('role:admin|accountant|teacher|parent|student');
    Route::post('/settings', [AdminController::class, 'updateSettings'])->middleware('role:admin');
    Route::get('/settings', [AdminController::class, 'getSettings'])->middleware('role:admin|accountant|teacher|parent|student');
});

Route::prefix('teachers')->middleware('auth:sanctum')->group(function () {
    Route::post('/notes', [TeacherController::class, 'storeNote'])->middleware('role:teacher');
    Route::get('/class-statistics/{classId}', [TeacherController::class, 'classStatistics'])->middleware('role:teacher');
});

Route::prefix('students')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [StudentController::class, 'index'])->middleware('role:admin');
    Route::post('/', [StudentController::class, 'store'])->middleware('role:admin');
    Route::get('/{id}', [StudentController::class, 'show'])->middleware('role:admin|teacher|parent|student');
    Route::put('/{id}', [StudentController::class, 'update'])->middleware('role:admin');
    Route::delete('/{id}', [StudentController::class, 'destroy'])->middleware('role:admin');
});

Route::prefix('notes')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [NoteController::class, 'index'])->middleware('role:admin|teacher|parent|student');
    Route::post('/', [NoteController::class, 'store'])->middleware('role:teacher');
    Route::get('/{id}', [NoteController::class, 'show'])->middleware('role:admin|teacher|parent|student');
    Route::put('/{id}', [NoteController::class, 'update'])->middleware('role:teacher');
    Route::delete('/{id}', [NoteController::class, 'destroy'])->middleware('role:teacher');
    Route::get('/average/{student_id}', [NoteController::class, 'calculateAverage'])->middleware('role:admin|teacher|parent|student');
    Route::get('/{student_id}/{subject_id}/{term}', [NoteController::class, 'getNotesBySubjectAndTerm'])->middleware('role:admin|teacher|parent|student');
    Route::get('/average/{student_id}/{subject_id}/{term}', [NoteController::class, 'averageByStudentSubjectTerm'])->middleware('role:admin|teacher|parent|student');
    Route::get('/bulletin/{student_id}', [NoteController::class, 'getBulletin'])->middleware('role:admin|teacher|parent|student');
    Route::get('/{student_id}/pdf', [NoteController::class, 'generatePdf'])->middleware('role:admin|teacher');
    Route::get('/{student_id}/word', [NoteController::class, 'generateWord'])->middleware('role:admin|teacher');
    Route::get('/{student_id}/preview', [NoteController::class, 'previewData'])->middleware('role:admin|teacher');
    Route::get('/class/{class_id}', [NoteController::class, 'generateClassBulletins'])->middleware('role:admin|teacher');
    Route::get('/{student_id}/terms', [NoteController::class, 'getAvailableTerms'])->middleware('role:admin|teacher|parent|student');
});

Route::prefix('subjects')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [SubjectController::class, 'index']);
    Route::post('/', [SubjectController::class, 'store'])->middleware('role:admin');
    Route::get('/{id}', [SubjectController::class, 'show']);
    Route::put('/{id}', [SubjectController::class, 'update'])->middleware('role:admin');
    Route::delete('/{id}', [SubjectController::class, 'destroy'])->middleware('role:admin');
});

Route::prefix('schedules')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [ScheduleController::class, 'index']);
    Route::post('/', [ScheduleController::class, 'store'])->middleware('role:admin');
    Route::get('/{id}', [ScheduleController::class, 'show']);
    Route::put('/{id}', [ScheduleController::class, 'update'])->middleware('role:admin');
    Route::delete('/{id}', [ScheduleController::class, 'destroy'])->middleware('role:admin');
    Route::get('/teacher/{id}', [ScheduleController::class, 'getByTeacher']);
    Route::get('/student/{id}', [ScheduleController::class, 'getByStudent']);
    Route::get('/class/{class_id}', [ScheduleController::class, 'byClass']);
});

Route::prefix('classes')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [ClasseController::class, 'index'])->middleware('role:admin');
    Route::post('/', [ClasseController::class, 'store'])->middleware('role:admin');
    Route::get('/{id}', [ClasseController::class, 'show']);
    Route::put('/{id}', [ClasseController::class, 'update'])->middleware('role:admin');
    Route::delete('/{id}', [ClasseController::class, 'destroy'])->middleware('role:admin');
    Route::get('/{id}/students', [ClasseController::class, 'students']);
    Route::get('/{id}/tutors', [ClasseController::class, 'tutors']);
});

Route::prefix('attendances')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [AttendanceController::class, 'index'])->middleware('role:admin|teacher|parent|student');
    Route::post('/', [AttendanceController::class, 'store'])->middleware('role:admin|teacher');
    Route::get('/{id}', [AttendanceController::class, 'show'])->middleware('role:admin|teacher|parent|student');
    Route::put('/{id}', [AttendanceController::class, 'update'])->middleware('role:admin|teacher');
    Route::delete('/{id}', [AttendanceController::class, 'destroy'])->middleware('role:admin|teacher');
});

Route::prefix('class-fees')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [ClassFeeController::class, 'index'])->middleware('role:admin|accountant|teacher|parent|student');
    Route::post('/', [ClassFeeController::class, 'store'])->middleware('role:admin|accountant');
    Route::get('/{id}', [ClassFeeController::class, 'show'])->middleware('role:admin|accountant|teacher|parent|student');
    Route::put('/{id}', [ClassFeeController::class, 'update'])->middleware('role:admin|accountant');
    Route::delete('/{id}', [ClassFeeController::class, 'destroy'])->middleware('role:admin|accountant');
});

Route::prefix('fee-periods')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [FeePeriodController::class, 'index'])->middleware('role:admin|accountant|parent|student');
    Route::post('/', [FeePeriodController::class, 'store'])->middleware('role:admin|accountant');
    Route::get('/{id}', [FeePeriodController::class, 'show'])->middleware('role:admin|accountant|parent|student');
    Route::put('/{id}', [FeePeriodController::class, 'update'])->middleware('role:admin|accountant');
    Route::delete('/{id}', [FeePeriodController::class, 'destroy'])->middleware('role:admin|accountant');
});

Route::prefix('invoices')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [InvoiceController::class, 'index'])->middleware('role:admin|accountant|parent|student');
    Route::post('/', [InvoiceController::class, 'store'])->middleware('role:admin|accountant');
    Route::get('/{id}', [InvoiceController::class, 'show'])->middleware('role:admin|accountant|parent|student');
    Route::put('/{id}', [InvoiceController::class, 'update'])->middleware('role:admin|accountant');
    Route::delete('/{id}', [InvoiceController::class, 'destroy'])->middleware('role:admin|accountant');
});

Route::prefix('messages')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [MessageController::class, 'index'])->middleware('role:admin|teacher|parent|student');
    Route::post('/', [MessageController::class, 'store'])->middleware('role:admin|teacher|parent|student');
    Route::get('/{id}', [MessageController::class, 'show'])->middleware('role:admin|teacher|parent|student');
    Route::put('/{id}', [MessageController::class, 'update'])->middleware('role:admin');
    Route::delete('/{id}', [MessageController::class, 'destroy'])->middleware('role:admin');
});

Route::prefix('payments')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [PaymentController::class, 'index'])->middleware('role:admin|accountant|parent|student');
    Route::post('/', [PaymentController::class, 'store'])->middleware('role:admin|accountant');
    Route::get('/{id}', [PaymentController::class, 'show'])->middleware('role:admin|accountant|parent|student');
    Route::put('/{id}', [PaymentController::class, 'update'])->middleware('role:admin|accountant');
    Route::delete('/{id}', [PaymentController::class, 'destroy'])->middleware('role:admin|accountant');
    Route::post('/{id}/confirm', [PaymentController::class, 'confirm'])->middleware('role:admin|accountant');
});