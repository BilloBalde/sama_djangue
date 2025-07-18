<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ClassModel;
use Illuminate\Support\Facades\Validator;

class ClasseController extends Controller
{
    public function index()
    {
        return response()->json(Classe::all());
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'year' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $classe = ClassModel::create($request->all());
        return response()->json($classe, 201);
    }

    public function show($id)
    {
        $classe = ClassModel::find($id);
        if (!$classe) return response()->json(['message' => 'Classe non trouvée'], 404);
        return response()->json($classe);
    }

    public function update(Request $request, $id)
    {
        $classe = ClassModel::find($id);
        if (!$classe) return response()->json(['message' => 'Classe non trouvée'], 404);

        $classe->update($request->all());
        return response()->json($classe);
    }

    public function destroy($id)
    {
        $classe = ClassModel::find($id);
        if (!$classe) return response()->json(['message' => 'Classe non trouvée'], 404);
        $classe->delete();
        return response()->json(['message' => 'Classe supprimée avec succès']);
    }

    public function students($id)
    {
        $classe = ClassModel::with('students')->findOrFail($id);
        return response()->json($classe->students);
    }
    public function teachers($id)
    {
        $classe = ClassModel::with('teachers')->findOrFail($id);
        return response()->json($classe->teachers);
    }


}

