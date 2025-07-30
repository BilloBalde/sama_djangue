<?php
namespace App\Http\Controllers\Api;

use App\Models\FeePeriod;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class FeePeriodController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:admin|accountant')->only(['store', 'update', 'destroy']);
        $this->middleware('role:admin|accountant|parent|student')->only(['index', 'show']);
    }

    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'school_year_id' => 'sometimes|exists:school_years,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $query = FeePeriod::query();
        if ($request->has('school_year_id')) {
            $query->where('school_year_id', $request->school_year_id);
        }

        $feePeriods = $query->get();

        if ($feePeriods->isEmpty()) {
            return response()->json(['message' => 'Aucune période de frais trouvée'], 404);
        }

        return response()->json([
            'fee_periods' => $feePeriods->map(function ($period) {
                return [
                    'id' => $period->id,
                    'name' => $period->name,
                    'start_date' => $period->start_date,
                    'end_date' => $period->end_date,
                    'school_year_id' => $period->school_year_id,
                    'created_at' => $period->created_at->format('d/m/Y H:i')
                ];
            })
        ], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|min:1|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'school_year_id' => 'required|exists:school_years,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $feePeriod = FeePeriod::create($request->all());
        return response()->json([
            'id' => $feePeriod->id,
            'name' => $feePeriod->name,
            'start_date' => $feePeriod->start_date,
            'end_date' => $feePeriod->end_date,
            'school_year_id' => $feePeriod->school_year_id,
            'created_at' => $feePeriod->created_at->format('d/m/Y H:i')
        ], 201);
    }

    public function show($id)
    {
        $feePeriod = FeePeriod::find($id);

        if (!$feePeriod) {
            return response()->json(['message' => 'Période de frais non trouvée'], 404);
        }

        return response()->json([
            'id' => $feePeriod->id,
            'name' => $feePeriod->name,
            'start_date' => $feePeriod->start_date,
            'end_date' => $feePeriod->end_date,
            'school_year_id' => $feePeriod->school_year_id,
            'created_at' => $feePeriod->created_at->format('d/m/Y H:i')
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $feePeriod = FeePeriod::find($id);

        if (!$feePeriod) {
            return response()->json(['message' => 'Période de frais non trouvée'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|min:1|max:255',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'school_year_id' => 'sometimes|exists:school_years,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $feePeriod->update($request->all());
        return response()->json([
            'id' => $feePeriod->id,
            'name' => $feePeriod->name,
            'start_date' => $feePeriod->start_date,
            'end_date' => $feePeriod->end_date,
            'school_year_id' => $feePeriod->school_year_id,
            'created_at' => $feePeriod->created_at->format('d/m/Y H:i')
        ], 200);
    }

    public function destroy($id)
    {
        $feePeriod = FeePeriod::find($id);

        if (!$feePeriod) {
            return response()->json(['message' => 'Période de frais non trouvée'], 404);
        }

        $feePeriod->delete();
        return response()->json(['message' => 'Période de frais supprimée avec succès'], 200);
    }
}