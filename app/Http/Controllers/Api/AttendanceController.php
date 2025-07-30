<?php
namespace App\Http\Controllers\Api;

use App\Models\Attendance;
use App\Models\Student;
use App\Models\ClassModel as Classe;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class AttendanceController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:teacher|admin')->only(['store', 'update', 'destroy']);
        $this->middleware('role:admin|teacher|parent|student')->only(['index', 'show']);
    }

    private function checkStudentAccess(Student $student)
    {
        if (!auth()->user()->hasRole('admin|teacher') && 
            auth()->user()->id !== $student->user_id && 
            auth()->user()->id !== $student->tutor_id) {
            abort(403, 'Accès non autorisé');
        }
    }

    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'student_id' => 'required|exists:students,id',
            'date' => 'sometimes|date',
            'school_year_id' => 'sometimes|exists:school_years,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $student = Student::find($request->student_id);
        $this->checkStudentAccess($student);

        $query = Attendance::with('student:id,first_name,last_name', 'class:id,name')
            ->where('student_id', $request->student_id);
        if ($request->has('date')) {
            $query->whereDate('date', $request->date);
        }
        if ($request->has('school_year_id')) {
            $query->where('school_year_id', $request->school_year_id);
        }

        $attendances = $query->get();

        if ($attendances->isEmpty()) {
            return response()->json(['message' => 'Aucune présence trouvée pour cet étudiant'], 404);
        }

        return response()->json([
            'attendances' => $attendances->map(function ($attendance) {
                return [
                    'id' => $attendance->id,
                    'student_id' => $attendance->student_id,
                    'student_name' => $attendance->student->first_name . ' ' . $attendance->student->last_name,
                    'class_id' => $attendance->class_id,
                    'class_name' => $attendance->class->name ?? 'Non assigné',
                    'date' => $attendance->date,
                    'status' => $attendance->status,
                    'comment' => $attendance->comment,
                    'school_year_id' => $attendance->school_year_id,
                    'created_at' => $attendance->created_at->format('d/m/Y H:i')
                ];
            })
        ], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'student_id' => 'required|exists:students,id',
            'class_id' => 'required|exists:classes,id',
            'date' => 'required|date',
            'status' => 'required|in:present,absent,late,excused',
            'comment' => 'nullable|string|max:255',
            'school_year_id' => 'required|exists:school_years,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $attendance = Attendance::create($request->all());
        return response()->json([
            'id' => $attendance->id,
            'student_id' => $attendance->student_id,
            'student_name' => $attendance->student->first_name . ' ' . $attendance->student->last_name,
            'class_id' => $attendance->class_id,
            'class_name' => $attendance->class->name ?? 'Non assigné',
            'date' => $attendance->date,
            'status' => $attendance->status,
            'comment' => $attendance->comment,
            'school_year_id' => $attendance->school_year_id,
            'created_at' => $attendance->created_at->format('d/m/Y H:i')
        ], 201);
    }

    public function show($id)
    {
        $attendance = Attendance::with('student:id,first_name,last_name', 'class:id,name')->find($id);

        if (!$attendance) {
            return response()->json(['message' => 'Présence non trouvée'], 404);
        }

        $this->checkStudentAccess($attendance->student);

        return response()->json([
            'id' => $attendance->id,
            'student_id' => $attendance->student_id,
            'student_name' => $attendance->student->first_name . ' ' . $attendance->student->last_name,
            'class_id' => $attendance->class_id,
            'class_name' => $attendance->class->name ?? 'Non assigné',
            'date' => $attendance->date,
            'status' => $attendance->status,
            'comment' => $attendance->comment,
            'school_year_id' => $attendance->school_year_id,
            'created_at' => $attendance->created_at->format('d/m/Y H:i')
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $attendance = Attendance::find($id);

        if (!$attendance) {
            return response()->json(['message' => 'Présence non trouvée'], 404);
        }

        $validator = Validator::make($request->all(), [
            'student_id' => 'sometimes|exists:students,id',
            'class_id' => 'sometimes|exists:classes,id',
            'date' => 'sometimes|date',
            'status' => 'sometimes|in:present,absent,late,excused',
            'comment' => 'sometimes|nullable|string|max:255',
            'school_year_id' => 'sometimes|exists:school_years,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $attendance->update($request->all());
        return response()->json([
            'id' => $attendance->id,
            'student_id' => $attendance->student_id,
            'student_name' => $attendance->student->first_name . ' ' . $attendance->student->last_name,
            'class_id' => $attendance->class_id,
            'class_name' => $attendance->class->name ?? 'Non assigné',
            'date' => $attendance->date,
            'status' => $attendance->status,
            'comment' => $attendance->comment,
            'school_year_id' => $attendance->school_year_id,
            'created_at' => $attendance->created_at->format('d/m/Y H:i')
        ], 200);
    }

    public function destroy($id)
    {
        $attendance = Attendance::find($id);

        if (!$attendance) {
            return response()->json(['message' => 'Présence non trouvée'], 404);
        }

        $attendance->delete();
        return response()->json(['message' => 'Présence supprimée avec succès'], 200);
    }
}
