<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClassModel as Classe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ClasseController extends Controller
{

    public function index()
    {
        $classes = Classe::with('schoolYear', 'professeur', 'students')->get();
        if ($classes->isEmpty()) {
            return response()->json(['message' => 'Aucune classe trouvée'], 404);
        }
        return response()->json($classes, 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'school_year_id' => 'required|exists:school_years,id',
            'professeur_id' => 'nullable|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $classe = Classe::create($request->all());
        return response()->json($classe->load('schoolYear', 'professeur'), 201);
    }

    public function show($id)
    {
        $classe = Classe::with('schoolYear', 'professeur', 'students')->find($id);
        if (!$classe) {
            return response()->json(['message' => 'Classe non trouvée'], 404);
        }
        return response()->json($classe, 200);
    }

    public function update(Request $request, $id)
    {
        $classe = Classe::find($id);
        if (!$classe) {
            return response()->json(['message' => 'Classe non trouvée'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'school_year_id' => 'sometimes|required|exists:school_years,id',
            'professeur_id' => 'nullable|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $classe->update($request->all());
        return response()->json($classe->load('schoolYear', 'professeur'), 200);
    }

    public function destroy($id)
    {
        $classe = Classe::find($id);
        if (!$classe) {
            return response()->json(['message' => 'Classe non trouvée'], 404);
        }
        $classe->delete();
        return response()->json(['message' => 'Classe supprimée avec succès'], 200);
    }

    public function students($id)
    {
        $classe = Classe::with('students')->find($id);
        if (!$classe) {
            return response()->json(['message' => 'Classe non trouvée'], 404);
        }
        return response()->json($classe->students, 200);
    }

    public function tutors($id)
    {
        $classe = Classe::with('students.tutor')->find($id);
        if (!$classe) {
            return response()->json(['message' => 'Classe non trouvée'], 404);
        }
        $tutors = $classe->students->pluck('tutor')->filter()->unique('id');
        if ($tutors->isEmpty()) {
            return response()->json(['message' => 'Aucun tuteur trouvé pour cette classe'], 404);
        }
        return response()->json($tutors->values(), 200);
    }
}