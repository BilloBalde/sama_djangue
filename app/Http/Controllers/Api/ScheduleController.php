<?php

namespace App\Http\Controllers\Api;

use App\Models\Student;
use App\Models\Schedule;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class ScheduleController extends Controller
{


    public function index()
    {
        $schedules = Schedule::with('subject', 'teacher')->get();
        return response()->json($schedules, 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'class_id' => 'required|exists:classes,id',
            'subject_id' => 'required|exists:subjects,id',
            'teacher_id' => 'required|exists:users,id',
            'day_of_week' => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $schedule = Schedule::create($request->all());
        return response()->json($schedule, 201);
    }

        public function show($id)
    {
        $schedule = Schedule::with(['teacher', 'subject', 'class'])->find($id);

        if (!$schedule) {
            return response()->json(['message' => 'Planning non trouvé.'], 404);
        }

        return response()->json($schedule);
    }

    public function update(Request $request, $id)
    {
        $schedule = Schedule::find($id);

        if (!$schedule) {
            return response()->json(['message' => 'Planning non trouvé.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'teacher_id'    => 'sometimes|exists:teachers,id',
            'subject_id'    => 'sometimes|exists:subjects,id',
            'class_id'      => 'sometimes|exists:classes,id',
            'day_of_week'   => 'sometimes|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'start_time'    => 'sometimes|date_format:H:i',
            'end_time'      => 'sometimes|date_format:H:i|after:start_time',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $schedule->update($request->all());

        return response()->json([
            'message' => 'Planning mis à jour.',
            'data' => $schedule
        ]);
    }

    public function destroy($id)
    {
        $schedule = Schedule::find($id);

        if (!$schedule) {
            return response()->json(['message' => 'Planning non trouvé.'], 404);
        }

        $schedule->delete();

        return response()->json(['message' => 'Planning supprimé.']);
    }

    public function getByTeacher($id)
    {
        return Schedule::with(['subject', 'class'])
            ->where('teacher_id', $id)
            ->get();
    }

    public function getByStudent($id)
    {
        $student = Student::findOrFail($id);
        return Schedule::with(['teacher', 'subject'])
            ->where('class_id', $student->classroom_id)
            ->get();
    }

    public function byClass($class_id)
    {
        $schedules = Schedule::where('class_id', $class_id)->with('subject', 'teacher')->get();
        return response()->json($schedules, 200);
    }
}
