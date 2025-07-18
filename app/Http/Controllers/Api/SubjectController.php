<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Subject;
use Illuminate\Support\Facades\Validator;

class SubjectController extends Controller
{
    public function index()
    {
        return response()->json(Subject::with('classe')->get(), 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'class_id' => 'required|exists:classes,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $subject = Subject::create($request->all());
        return response()->json($subject, 201);
    }

    public function show($id)
    {
        $subject = Subject::with('classe')->find($id);
        if (!$subject) {
            return response()->json(['message' => 'Matière non trouvée'], 404);
        }
        return response()->json($subject);
    }

    public function update(Request $request, $id)
    {
        $subject = Subject::find($id);
        if (!$subject) {
            return response()->json(['message' => 'Matière non trouvée'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'class_id' => 'sometimes|required|exists:classes,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $subject->update($request->all());
        return response()->json($subject);
    }

    public function destroy($id)
    {
        $subject = Subject::find($id);
        if (!$subject) {
            return response()->json(['message' => 'Matière non trouvée'], 404);
        }

        $subject->delete();
        return response()->json(['message' => 'Matière supprimée avec succès']);
    }
}

