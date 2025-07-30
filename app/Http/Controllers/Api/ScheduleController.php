<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ScheduleController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:admin')->except(['index', 'show', 'getByTeacher', 'getByStudent', 'byClass']);
    }

    public function index()
    {
        $schedules = Schedule::with('subject', 'teacher', 'class')->get();
        if ($schedules->isEmpty()) {
            return response()->json(['message' => 'Aucun emploi du temps trouvé'], 404);
        }
        return response()->json($schedules, 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'class_id' => 'required|exists:classes,id',
            'subject_id' => 'required|exists:subjects,id',
            'teacher_id' => 'required|exists:users,id',
            'day_of_week' => 'required|in:Lundi,Mardi,Mercredi,Jeudi,Vendredi,Samedi,Dimanche',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'school_year_id' => 'required|exists:school_years,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $schedule = Schedule::create($request->all());
        return response()->json($schedule->load('subject', 'teacher', 'class'), 201);
    }

    public function show($id)
    {
        $schedule = Schedule::with('teacher', 'subject', 'class')->find($id);
        if (!$schedule) {
            return response()->json(['message' => 'Emploi du temps non trouvé'], 404);
        }
        return response()->json($schedule, 200);
    }

    public function update(Request $request, $id)
    {
        $schedule = Schedule::find($id);
        if (!$schedule) {
            return response()->json(['message' => 'Emploi du temps non trouvé'], 404);
        }

        $validator = Validator::make($request->all(), [
            'class_id' => 'sometimes|exists:classes,id',
            'subject_id' => 'sometimes|exists:subjects,id',
            'teacher_id' => 'sometimes|exists:users,id',
            'day_of_week' => 'sometimes|in:Lundi,Mardi,Mercredi,Jeudi,Vendredi,Samedi,Dimanche',
            'start_time' => 'sometimes|date_format:H:i',
            'end_time' => 'sometimes|date_format:H:i|after:start_time',
            'school_year_id' => 'sometimes|exists:school_years,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $schedule->update($request->all());
        return response()->json([
            'message' => 'Emploi du temps mis à jour',
            'data' => $schedule->load('subject', 'teacher', 'class')
        ], 200);
    }

    public function destroy($id)
    {
        $schedule = Schedule::find($id);
        if (!$schedule) {
            return response()->json(['message' => 'Emploi du temps non trouvé'], 404);
        }

        $schedule->delete();
        return response()->json(['message' => 'Emploi du temps supprimé'], 200);
    }

    public function getByTeacher($id)
    {
        $schedules = Schedule::with('subject', 'class')
            ->where('teacher_id', $id)
            ->get();
        if ($schedules->isEmpty()) {
            return response()->json(['message' => 'Aucun emploi du temps trouvé pour cet enseignant'], 404);
        }
        return response()->json($schedules, 200);
    }

    public function getByStudent($id)
    {
        $student = Student::find($id);
        if (!$student) {
            return response()->json(['message' => 'Étudiant non trouvé'], 404);
        }
        $schedules = Schedule::with('teacher', 'subject')
            ->where('class_id', $student->class_id)
            ->get();
        if ($schedules->isEmpty()) {
            return response()->json(['message' => 'Aucun emploi du temps trouvé pour cette classe'], 404);
        }
        return response()->json($schedules, 200);
    }

    public function byClass($class_id)
    {
        $schedules = Schedule::where('class_id', $class_id)->with('subject', 'teacher')->get();
        if ($schedules->isEmpty()) {
            return response()->json(['message' => 'Aucun emploi du temps trouvé pour cette classe'], 404);
        }
        return response()->json($schedules, 200);
    }
}