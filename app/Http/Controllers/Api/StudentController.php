<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class StudentController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:admin');
    }

    public function index()
    {
        $students = Student::with('class', 'tutor', 'notes', 'payments', 'attendances')->get();
        if ($students->isEmpty()) {
            return response()->json(['message' => 'Aucun étudiant trouvé'], 404);
        }
        return response()->json($students, 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'birth_date' => 'required|date',
            'class_id' => 'required|exists:classes,id',
            'tutor_id' => 'nullable|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Créer le User
        $user = User::create([
            'name' => $request->first_name . ' ' . $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'class_id' => $request->class_id,
        ]);
        $user->assignRole('student');

        // Créer le Student lié à ce user
        $student = Student::create([
            'user_id' => $user->id,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'birth_date' => $request->birth_date,
            'class_id' => $request->class_id,
            'tutor_id' => $request->tutor_id ?? null,
        ]);

        return response()->json([
            'message' => 'Étudiant créé avec succès',
            'student' => $student->load('class', 'tutor', 'notes', 'payments', 'attendances'),
        ], 201);
    }

    public function show($id)
    {
        $student = Student::with('class', 'tutor', 'notes', 'payments', 'attendances')->find($id);

        if (!$student) {
            return response()->json(['message' => 'Étudiant non trouvé'], 404);
        }

        return response()->json($student, 200);
    }

    public function update(Request $request, $id)
    {
        $student = Student::find($id);

        if (!$student) {
            return response()->json(['message' => 'Étudiant non trouvé'], 404);
        }

        $validator = Validator::make($request->all(), [
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'birth_date' => 'sometimes|required|date',
            'class_id' => 'sometimes|required|exists:classes,id',
            'tutor_id' => 'nullable|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $student->update($request->all());

        // Mettre à jour l'utilisateur associé si nécessaire
        if ($request->hasAny(['first_name', 'last_name'])) {
            $user = $student->user;
            $user->update(['name' => $request->first_name . ' ' . $request->last_name]);
        }

        return response()->json($student->load('class', 'tutor', 'notes', 'payments', 'attendances'), 200);
    }

    public function destroy($id)
    {
        $student = Student::find($id);

        if (!$student) {
            return response()->json(['message' => 'Étudiant non trouvé'], 404);
        }

        // Supprimer l'utilisateur associé
        $student->user->delete();
        $student->delete();

        return response()->json(['message' => 'Étudiant supprimé avec succès'], 200);
    }
}