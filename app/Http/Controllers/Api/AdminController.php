<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Note;
use App\Models\Student;
use App\Models\SchoolYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{
    public function __construct()
    {
        // Vérifier que l'utilisateur est admin
        $this->middleware('role:admin');
    }

    public function dashboardAnalytics()
    {
        $totalStudents = Student::count();
        $genderDistribution = Student::selectRaw('gender, COUNT(*) as count')
            ->groupBy('gender')
            ->get();
        $averageGrades = Note::avg('value');
        $topStudents = Student::with('notes')->get()->sortByDesc(function ($student) {
            return $student->notes->avg('value');
        })->take(5);

        if ($totalStudents === 0) {
            return response()->json(['message' => 'Aucun étudiant trouvé'], 404);
        }

        return response()->json([
            'total_students' => $totalStudents,
            'gender_distribution' => $genderDistribution->map(fn($item) => [
                'gender' => $item->gender ?? 'Non spécifié',
                'count' => $item->count,
            ]),
            'average_grades' => round($averageGrades, 2) ?? 0.00,
            'top_students' => $topStudents->map(function ($student) {
                return [
                    'name' => $student->first_name . ' ' . $student->last_name,
                    'average' => round($student->notes->avg('value'), 2) ?? 0.00,
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

        if ($request->active) {
            SchoolYear::where('active', true)->update(['active' => false]);
        }

        $schoolYear = SchoolYear::create($request->all());
        return response()->json($schoolYear, 201);
    }

    public function getSchoolYears()
    {
        $schoolYears = SchoolYear::all();
        if ($schoolYears->isEmpty()) {
            return response()->json(['message' => 'Aucune année scolaire trouvée'], 404);
        }
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

        // À implémenter : sauvegarde dans une table settings ou fichier config
        $settings = ['max_grade' => $request->max_grade ?? 20, 'default_due_days' => $request->default_due_days ?? 7];
        return response()->json(['settings' => $settings], 200);
    }

    public function getSettings()
    {
        // À implémenter : récupération depuis table settings ou config
        $settings = ['max_grade' => 20, 'default_due_days' => 7];
        return response()->json($settings, 200);
    }
}