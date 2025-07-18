<?php

namespace App\Http\Controllers\Api;

use App\Models\Grade;
use App\Models\Student;
use App\Models\SchoolYear;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{
    public function dashboardAnalytics()
    {
 
        $totalStudents = Student::count();
        $averageGrades = Grade::avg('value');
        $topStudents = Student::with('grades')->get()->sortByDesc(function ($student) {
            return $student->grades->avg('value');
        })->take(5);

        return response()->json([
            'total_students' => $totalStudents,
            'average_grades' => round($averageGrades, 2) ?? 0.00,
            'top_students' => $topStudents->map(function ($student) {
                return [
                    'name' => $student->first_name . ' ' . $student->last_name,
                    'average' => round($student->grades->avg('value'), 2) ?? 0.00,
                ];
            }),
        ], 200);
    }

    public function storeSchoolYear(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'active' => 'boolean',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        SchoolYear::where('active', true)->update(['active' => false]);
        $schoolYear = SchoolYear::create($request->all());
        return response()->json($schoolYear, 201);
    }

    public function getSchoolYears()
    {

        $schoolYears = SchoolYear::all();
        return response()->json($schoolYears, 200);
    }  
    
    public function updateSettings(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'max_grade' => 'nullable|numeric|min:0|max:100',
            'default_due_days' => 'nullable|integer|min:1',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $settings = ['max_grade' => $request->max_grade, 'default_due_days' => $request->default_due_days];
        // Simuler sauvegarde dans fichier ou table (ex. config ou table settings)
        return response()->json(['settings' => $settings], 200);
    }

    public function getSettings()
    {
      // Simuler récupération (ex. depuis config ou table settings)
        $settings = ['max_grade' => 20, 'default_due_days' => 7];
        return response()->json($settings, 200);
    }
}