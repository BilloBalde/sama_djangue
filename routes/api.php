<?php
use App\Events\PaymentMade;
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


Route::post('/test-paiement', function (\Illuminate\Http\Request $request) {
    $payment = $request->input('payment');
    $parentId = $request->input('parent_id');

    event(new PaymentMade($payment, $parentId));

    return response()->json(['status' => 'Paiement broadcasté avec succès']);
});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/test-api', function () {
    return response()->json(['message' => 'API OK']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Students
    Route::apiResource('students', StudentController::class);
    Route::apiResource('notes', NoteController::class);
    Route::apiResource('classes', ClasseController::class);
    Route::apiResource('subjects', SubjectController::class);
    Route::apiResource('payments', PaymentController::class);
    Route::apiResource('schedules', ScheduleController::class);
    Route::get('/classes/{id}/students', [ClasseController::class, 'students']);


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
    Route::get('/notes/{student_id}/{subject_id}/{term}', [NoteController::class, 'getNotesBySubjectAndTerm']);
    Route::get('/notes/average/{student_id}/{subject_id}/{term}', [NoteController::class, 'averageByStudentSubjectTerm']);
    Route::get('/bulletin/{student_id}/{term}', [NoteController::class, 'generateBulletin']);
    Route::get('/bulletin/{student_id}/{term}', [NoteController::class, 'getBulletin']);
    Route::get('/bulletin/{id}/{trimestre}/{format}', [NoteController::class, 'exportBulletin']);

    // Payments
    Route::post('/payments/{id}/confirm', [PaymentController::class, 'confirm']);

    // Schedules
    Route::get('schedules/teacher/{id}', [ScheduleController::class, 'getByTeacher']);
    Route::get('schedules/student/{id}', [ScheduleController::class, 'getByStudent']);
    Route::get('schedules/class/{class_id}', [ScheduleController::class, 'byClass']);


    // Messages
    Route::get('/messages', [MessageController::class, 'index']);
    Route::post('/messages', [MessageController::class, 'store']);
    Route::post('/messages/{id}/read', [MessageController::class, 'markAsRead']);
});
