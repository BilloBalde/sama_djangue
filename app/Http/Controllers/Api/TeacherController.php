<?php

namespace App\Http\Controllers\Api;

use App\Models\Grade;
use App\Models\Student;
use App\Models\Assignment;
use App\Models\Competency;
use App\Models\Submission;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class TeacherController extends Controller
{
    public function storeGrade(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'competency_id' => 'required|exists:competencies,id',
            'value' => 'required|numeric|min:0|max:20',
            'date' => 'required|date',
            'comment' => 'nullable|string',
        ]);

        $grade = Grade::create($validated);

        return response()->json($grade, 201);
    }


    public function classStatistics($classId)
    {
      

        $students = Student::where('class_id', $classId)->with('grades.competency')->get();
        $stats = [];
        foreach ($students as $student) {
            $average = $student->grades->avg('value');
            $stats[$student->id] = [
                'student_name' => $student->first_name . ' ' . $student->last_name,
                'average' => round($average, 2) ?? 0.00,
            ];
        }

        return response()->json(['class_id' => $classId, 'statistics' => $stats], 200);
    }

    public function storeAssignment(Request $request)
    {
      
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'required|date',
            'class_id' => 'required|exists:classes,id',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $assignment = Assignment::create($request->all());
        return response()->json($assignment, 201);
    }

    public function getSubmissions($assignmentId)
    {

        $submissions = Submission::where('assignment_id', $assignmentId)->with('student')->get();
        return response()->json($submissions, 200);
    }
}
