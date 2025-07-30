<?php
namespace App\Http\Controllers\Api;

use App\Models\Invoice;
use App\Models\Student;
use App\Models\FeePeriod;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class InvoiceController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:admin|accountant')->only(['store', 'update', 'destroy']);
        $this->middleware('role:admin|accountant|parent|student')->only(['index', 'show']);
    }

    private function checkStudentAccess(Student $student)
    {
        if (!auth()->user()->hasRole('admin|accountant') && 
            auth()->user()->id !== $student->user_id && 
            auth()->user()->id !== $student->tutor_id) {
            abort(403, 'Accès non autorisé');
        }
    }

    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'student_id' => 'required|exists:students,id',
            'school_year_id' => 'sometimes|exists:school_years,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $student = Student::find($request->student_id);
        $this->checkStudentAccess($student);

        $query = Invoice::with('student:id,first_name,last_name', 'feePeriod:id,name')
            ->where('student_id', $request->student_id);
        if ($request->has('school_year_id')) {
            $query->where('school_year_id', $request->school_year_id);
        }

        $invoices = $query->get();

        if ($invoices->isEmpty()) {
            return response()->json(['message' => 'Aucune facture trouvée pour cet étudiant'], 404);
        }

        return response()->json([
            'invoices' => $invoices->map(function ($invoice) {
                return [
                    'id' => $invoice->id,
                    'student_id' => $invoice->student_id,
                    'student_name' => $invoice->student->first_name . ' ' . $invoice->student->last_name,
                    'fee_period_id' => $invoice->fee_period_id,
                    'fee_period_name' => $invoice->feePeriod->name ?? 'Non assigné',
                    'amount' => floatval($invoice->amount),
                    'due_date' => $invoice->due_date,
                    'status' => $invoice->status,
                    'school_year_id' => $invoice->school_year_id,
                    'created_at' => $invoice->created_at->format('d/m/Y H:i')
                ];
            })
        ], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'student_id' => 'required|exists:students,id',
            'fee_period_id' => 'required|exists:fee_periods,id',
            'amount' => 'required|numeric|min:0',
            'due_date' => 'required|date',
            'status' => 'required|in:pending,paid,overdue',
            'school_year_id' => 'required|exists:school_years,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $invoice = Invoice::create($request->all());
        return response()->json([
            'id' => $invoice->id,
            'student_id' => $invoice->student_id,
            'student_name' => $invoice->student->first_name . ' ' . $invoice->student->last_name,
            'fee_period_id' => $invoice->fee_period_id,
            'fee_period_name' => $invoice->feePeriod->name ?? 'Non assigné',
            'amount' => floatval($invoice->amount),
            'due_date' => $invoice->due_date,
            'status' => $invoice->status,
            'school_year_id' => $invoice->school_year_id,
            'created_at' => $invoice->created_at->format('d/m/Y H:i')
        ], 201);
    }

    public function show($id)
    {
        $invoice = Invoice::with('student:id,first_name,last_name', 'feePeriod:id,name')->find($id);

        if (!$invoice) {
            return response()->json(['message' => 'Facture non trouvée'], 404);
        }

        $this->checkStudentAccess($invoice->student);

        return response()->json([
            'id' => $invoice->id,
            'student_id' => $invoice->student_id,
            'student_name' => $invoice->student->first_name . ' ' . $invoice->student->last_name,
            'fee_period_id' => $invoice->fee_period_id,
            'fee_period_name' => $invoice->feePeriod->name ?? 'Non assigné',
            'amount' => floatval($invoice->amount),
            'due_date' => $invoice->due_date,
            'status' => $invoice->status,
            'school_year_id' => $invoice->school_year_id,
            'created_at' => $invoice->created_at->format('d/m/Y H:i')
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $invoice = Invoice::find($id);

        if (!$invoice) {
            return response()->json(['message' => 'Facture non trouvée'], 404);
        }

        $validator = Validator::make($request->all(), [
            'student_id' => 'sometimes|exists:students,id',
            'fee_period_id' => 'sometimes|exists:fee_periods,id',
            'amount' => 'sometimes|numeric|min:0',
            'due_date' => 'sometimes|date',
            'status' => 'sometimes|in:pending,paid,overdue',
            'school_year_id' => 'sometimes|exists:school_years,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $invoice->update($request->all());
        return response()->json([
            'id' => $invoice->id,
            'student_id' => $invoice->student_id,
            'student_name' => $invoice->student->first_name . ' ' . $invoice->student->last_name,
            'fee_period_id' => $invoice->fee_period_id,
            'fee_period_name' => $invoice->feePeriod->name ?? 'Non assigné',
            'amount' => floatval($invoice->amount),
            'due_date' => $invoice->due_date,
            'status' => $invoice->status,
            'school_year_id' => $invoice->school_year_id,
            'created_at' => $invoice->created_at->format('d/m/Y H:i')
        ], 200);
    }

    public function destroy($id)
    {
        $invoice = Invoice::find($id);

        if (!$invoice) {
            return response()->json(['message' => 'Facture non trouvée'], 404);
        }

        $invoice->delete();
        return response()->json(['message' => 'Facture supprimée avec succès'], 200);
    }
}