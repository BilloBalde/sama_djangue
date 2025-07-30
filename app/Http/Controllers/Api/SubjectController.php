<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SubjectController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:admin')->except(['index', 'show']);
    }

    public function index()
    {
        $subjects = Subject::with('class', 'notes', 'schedules')->get();
        if ($subjects->isEmpty()) {
            return response()->json(['message' => 'Aucune matière trouvée'], 404);
        }
        return response()->json($subjects, 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'class_id' => 'required|exists:classes,id',
            'coefficient' => 'nullable|numeric|min:0|max:10',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $subject = Subject::create($request->all());
        return response()->json($subject->load('class'), 201);
    }

    public function show($id)
    {
        $subject = Subject::with('class', 'notes', 'schedules')->find($id);
        if (!$subject) {
            return response()->json(['message' => 'Matière non trouvée'], 404);
        }
        return response()->json($subject, 200);
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
            'coefficient' => 'sometimes|numeric|min:0|max:10',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $subject->update($request->all());
        return response()->json($subject->load('class'), 200);
    }

    public function destroy($id)
    {
        $subject = Subject::find($id);
        if (!$subject) {
            return response()->json(['message' => 'Matière non trouvée'], 404);
        }

        $subject->delete();
        return response()->json(['message' => 'Matière supprimée avec succès'], 200);
    }
}