<?php
namespace App\Http\Controllers\Api;

use App\Models\ClassFee;
use App\Models\ClassModel as Classe;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class ClassFeeController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:admin|accountant')->only(['store', 'update', 'destroy']);
        $this->middleware('role:admin|accountant|teacher|parent|student')->only(['index', 'show']);
    }

    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'class_id' => 'sometimes|exists:classes,id',
            'school_year_id' => 'sometimes|exists:school_years,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $query = ClassFee::with('class:id,name');
        if ($request->has('class_id')) {
            $query->where('class_id', $request->class_id);
        }
        if ($request->has('school_year_id')) {
            $query->where('school_year_id', $request->school_year_id);
        }

        $classFees = $query->get();

        if ($classFees->isEmpty()) {
            return response()->json(['message' => 'Aucun frais de classe trouvé'], 404);
        }

        return response()->json([
            'class_fees' => $classFees->map(function ($fee) {
                return [
                    'id' => $fee->id,
                    'class_id' => $fee->class_id,
                    'class_name' => $fee->class->name ?? 'Non assigné',
                    'amount' => floatval($fee->amount),
                    'description' => $fee->description,
                    'school_year_id' => $fee->school_year_id,
                    'created_at' => $fee->created_at->format('d/m/Y H:i')
                ];
            })
        ], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'class_id' => 'required|exists:classes,id',
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:255',
            'school_year_id' => 'required|exists:school_years,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $classFee = ClassFee::create($request->all());
        return response()->json([
            'id' => $classFee->id,
            'class_id' => $classFee->class_id,
            'class_name' => $classFee->class->name ?? 'Non assigné',
            'amount' => floatval($classFee->amount),
            'description' => $classFee->description,
            'school_year_id' => $classFee->school_year_id,
            'created_at' => $classFee->created_at->format('d/m/Y H:i')
        ], 201);
    }

    public function show($id)
    {
        $classFee = ClassFee::with('class:id,name')->find($id);

        if (!$classFee) {
            return response()->json(['message' => 'Frais de classe non trouvé'], 404);
        }

        return response()->json([
            'id' => $classFee->id,
            'class_id' => $classFee->class_id,
            'class_name' => $classFee->class->name ?? 'Non assigné',
            'amount' => floatval($classFee->amount),
            'description' => $classFee->description,
            'school_year_id' => $classFee->school_year_id,
            'created_at' => $classFee->created_at->format('d/m/Y H:i')
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $classFee = ClassFee::find($id);

        if (!$classFee) {
            return response()->json(['message' => 'Frais de classe non trouvé'], 404);
        }

        $validator = Validator::make($request->all(), [
            'class_id' => 'sometimes|exists:classes,id',
            'amount' => 'sometimes|numeric|min:0',
            'description' => 'sometimes|nullable|string|max:255',
            'school_year_id' => 'sometimes|exists:school_years,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $classFee->update($request->all());
        return response()->json([
            'id' => $classFee->id,
            'class_id' => $classFee->class_id,
            'class_name' => $classFee->class->name ?? 'Non assigné',
            'amount' => floatval($classFee->amount),
            'description' => $classFee->description,
            'school_year_id' => $classFee->school_year_id,
            'created_at' => $classFee->created_at->format('d/m/Y H:i')
        ], 200);
    }

    public function destroy($id)
    {
        $classFee = ClassFee::find($id);

        if (!$classFee) {
            return response()->json(['message' => 'Frais de classe non trouvé'], 404);
        }

        $classFee->delete();
        return response()->json(['message' => 'Frais de classe supprimé avec succès'], 200);
    }
}