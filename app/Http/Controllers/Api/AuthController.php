<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'in:admin,teacher,parent,student'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Restreindre la création de rôles admin/teacher aux admins
        if (in_array($request->role, ['admin', 'teacher']) && !auth()->user()?->hasRole('admin')) {
            return response()->json(['message' => 'Seul un admin peut créer ce rôle'], 403);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $user->assignRole($request->role);
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json(['user' => $user, 'token' => $token], 201);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if (!auth()->attempt($request->only('email', 'password'))) {
            return response()->json(['message' => 'Identifiants invalides'], 401);
        }

        $user = auth()->user();
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json(['user' => $user, 'token' => $token], 200);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Déconnexion réussie'], 200);
    }

    public function user(Request $request)
    {
        return response()->json($request->user());
    }

    public function tutors()
    {
        $tutors = User::role('parent')->get(['id', 'name', 'email']);
        if ($tutors->isEmpty()) {
            return response()->json(['message' => 'Aucun tuteur trouvé'], 404);
        }
        return response()->json($tutors);
    }

    public function teachers()
    {
        $teachers = User::role('teacher')->get(['id', 'name', 'email']);
        if ($teachers->isEmpty()) {
            return response()->json(['message' => 'Aucun enseignant trouvé'], 404);
        }
        return response()->json($teachers);
    }
}