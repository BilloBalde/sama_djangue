<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\Student;
use App\Models\Resource;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class StudentController extends Controller
{
    public function index()
    {
        return response()->json(Student::with('class', 'tutor')->get(), 200);
    }

    /*public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:students,email',
            'birth_date' => 'required|date',
            'class_id' => 'required|exists:classes,id',
            'tutor_id' => 'nullable|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $student = Student::create($request->all());
        $student->assignRole('student');
        return response()->json($student, 201);
    }*/

 public function store(Request $request)
{
    $validated = $request->validate([
        'first_name' => 'required|string|max:255',
        'last_name' => 'required|string|max:255',
        'email' => 'required|string|email|max:255|unique:users',
        'password' => 'required|string|min:6',
        'birth_date' => 'required|date',
        'classe_id' => 'required|exists:classes,id',
        'tutor_id' => 'nullable|exists:users,id',
    ]);

    // Créer le User
    $user = User::create([
        'name' => $validated['first_name'].' '.$validated['last_name'],
        'email' => $validated['email'],
        'password' => bcrypt($validated['password']),
        'classe_id' => $validated['classe_id'],
    ]);

    // Créer le Student lié à ce user
    $student = Student::create([
        'first_name' => $validated['first_name'],
        'last_name' => $validated['last_name'],
        'email' => $validated['email'], // <-- AJOUTE CETTE LIGNE
        'birth_date' => $validated['birth_date'],
        'class_id' => $validated['classe_id'],
        'tutor_id' => $validated['tutor_id'] ?? null,
    ]);

    return response()->json([
        'message' => 'Étudiant créé avec succès',
        'student' => $student->load('class', 'tutor'),
    ], 201);
}



    // GET /api/students/{id}
    public function show($id)
    {
        $student = Student::with('class', 'tutor')->find($id);

        if (!$student) {
            return response()->json(['message' => 'Étudiant non trouvé'], 404);
        }

        return response()->json($student, 200);
    }

    // PUT /api/students/{id}
    public function update(Request $request, $id)
    {
        $student = Student::find($id);

        if (!$student) {
            return response()->json(['message' => 'Étudiant non trouvé'], 404);
        }

        $validator = Validator::make($request->all(), [
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:students,email,' . $id,
            'birth_date' => 'sometimes|required|date',
            'class_id' => 'sometimes|required|exists:classes,id',
            'tutor_id' => 'nullable|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $student->update($request->all());

        return response()->json($student->load('class', 'tutor'), 200);
    }

    // DELETE /api/students/{id}
    public function destroy($id)
    {
        $student = Student::find($id);

        if (!$student) {
            return response()->json(['message' => 'Étudiant non trouvé'], 404);
        }

        $student->delete();

        return response()->json(['message' => 'Étudiant supprimé avec succès'], 200);
    }



}
