<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Note;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TeacherController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:teacher');
    }

    public function storeNote(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'student_id' => 'required|exists:students,id',
            'subject_id' => 'required|exists:subjects,id',
            'value' => 'required|numeric|min:0|max:20',
            'term' => 'required|string',
            'date' => 'required|date',
            'comment' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $note = Note::create(array_merge($request->all(), ['teacher_id' => auth()->id()]));

        return response()->json($note, 201);
    }

    public function classStatistics($classId)
    {
        $students = Student::where('class_id', $classId)->with('notes.subject')->get();
        if ($students->isEmpty()) {
            return response()->json(['message' => 'Aucun étudiant trouvé pour cette classe'], 404);
        }

        $stats = $students->map(function ($student) {
            return [
                'student_name' => $student->first_name . ' ' . $student->last_name,
                'average' => round($student->notes->avg('value'), 2) ?? 0.00,
            ];
        });

        return response()->json(['class_id' => $classId, 'statistics' => $stats], 200);
    }

    // Méthodes supprimées pour V1 :
    // public function storeAssignment(Request $request)
    // public function getSubmissions($assignmentId)
}