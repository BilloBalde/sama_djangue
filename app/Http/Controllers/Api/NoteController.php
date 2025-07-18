<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Note;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NoteController extends Controller
{


    public function index($student_id)
    {
        $notes = Note::where('student_id', $student_id)->with('subject')->get();
        return response()->json($notes, 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'student_id' => 'required|exists:students,id',
            'subject_id' => 'required|exists:subjects,id',
            'value' => 'required|numeric|between:0,20',
            'date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $note = Note::create($request->all());
        return response()->json($note, 201);
    }


    public function show($id)
    {
        $note = Note::find($id);

        if (!$note) {
            return response()->json(['message' => 'Note non trouvée'], 404);
        }

        return response()->json($note);
    }
    
    // Mise à jour d'une note
    public function update(Request $request, $id)
    {
        $note = Note::find($id);

        if (!$note) {
            return response()->json(['message' => 'Note non trouvée'], 404);
        }

        $validator = Validator::make($request->all(), [
            'student_id' => 'sometimes|exists:students,id',
            'subject_id' => 'sometimes|exists:subjects,id',
            'value' => 'sometimes|numeric|between:0,20',
            'date' => 'sometimes|date',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $note->update($request->all());
        return response()->json($note);
    }

    // Suppression d'une note
    public function destroy($id)
    {
        $note = Note::find($id);

        if (!$note) {
            return response()->json(['message' => 'Note non trouvée'], 404);
        }

        $note->delete();
        return response()->json(['message' => 'Note supprimée avec succès']);
    }

    // Calcul de la moyenne d’un élève
    public function calculateAverage($student_id)
    {
        $notes = Note::where('student_id', $student_id)->get();

        if ($notes->isEmpty()) {
            return response()->json(['message' => 'Aucune note trouvée pour cet élève.'], 404);
        }

        $average = $notes->avg('value');
        return response()->json([
            'student_id' => $student_id,
            'average' => round($average, 2)
        ]);
    }
    /*public function calculateAverage($student_id)
    {
        $notes = Note::where('student_id', $student_id)->get();
        $average = $notes->avg('value');
        return response()->json(['student_id' => $student_id, 'average' => round($average, 2)], 200);
    }*/

}
