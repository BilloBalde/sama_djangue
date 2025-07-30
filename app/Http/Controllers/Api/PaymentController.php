<?php

namespace App\Http\Controllers\Api;

use App\Models\Payment;
use App\Models\Student;
use App\Models\Invoice;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Events\PaymentMade;

class PaymentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->middleware('role:admin|accountant')->only(['store', 'update', 'destroy', 'confirm']);
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

        $query = Payment::with('student:id,first_name,last_name', 'invoice:id,amount')
            ->where('student_id', $request->student_id);
        if ($request->has('school_year_id')) {
            $query->where('school_year_id', $request->school_year_id);
        }

        $payments = $query->get();

        if ($payments->isEmpty()) {
            return response()->json(['message' => 'Aucun paiement trouvé pour cet étudiant'], 404);
        }

        return response()->json([
            'payments' => $payments->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'student_id' => $payment->student_id,
                    'student_name' => $payment->student->first_name . ' ' . $payment->student->last_name,
                    'invoice_id' => $payment->invoice_id,
                    'invoice_amount' => $payment->invoice->amount ?? 0,
                    'amount' => floatval($payment->amount),
                    'payment_date' => $payment->payment_date,
                    'method' => $payment->method,
                    'reference' => $payment->reference,
                    'status' => $payment->status,
                    'school_year_id' => $payment->school_year_id,
                    'created_at' => $payment->created_at->format('d/m/Y H:i')
                ];
            })
        ], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'student_id' => 'required|exists:students,id',
            'invoice_id' => 'required|exists:invoices,id',
            'amount' => 'required|numeric|min:0',
            'payment_date' => 'required|date',
            'method' => 'required|string|in:Orange Money,Wave,Cash,check,bank_transfer,mobile',
            'reference' => 'nullable|string|max:255',
            'status' => 'sometimes|string|in:pending,completed,failed',
            'school_year_id' => 'required|exists:school_years,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = array_merge($request->all(), ['status' => $request->input('status', 'pending')]);
        $payment = Payment::create($data);
        event(new PaymentMade($payment));

        return response()->json([
            'id' => $payment->id,
            'student_id' => $payment->student_id,
            'student_name' => $payment->student->first_name . ' ' . $payment->student->last_name,
            'invoice_id' => $payment->invoice_id,
            'invoice_amount' => $payment->invoice->amount ?? 0,
            'amount' => floatval($payment->amount),
            'payment_date' => $payment->payment_date,
            'method' => $payment->method,
            'reference' => $payment->reference,
            'status' => $payment->status,
            'school_year_id' => $payment->school_year_id,
            'created_at' => $payment->created_at->format('d/m/Y H:i')
        ], 201);
    }

    public function show($id)
    {
        $payment = Payment::with('student:id,first_name,last_name', 'invoice:id,amount')->find($id);

        if (!$payment) {
            return response()->json(['message' => 'Paiement non trouvé'], 404);
        }

        $this->checkStudentAccess($payment->student);

        return response()->json([
            'id' => $payment->id,
            'student_id' => $payment->student_id,
            'student_name' => $payment->student->first_name . ' ' . $payment->student->last_name,
            'invoice_id' => $payment->invoice_id,
            'invoice_amount' => $payment->invoice->amount ?? 0,
            'amount' => floatval($payment->amount),
            'payment_date' => $payment->payment_date,
            'method' => $payment->method,
            'reference' => $payment->reference,
            'status' => $payment->status,
            'school_year_id' => $payment->school_year_id,
            'created_at' => $payment->created_at->format('d/m/Y H:i')
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $payment = Payment::find($id);

        if (!$payment) {
            return response()->json(['message' => 'Paiement non trouvé'], 404);
        }

        $validator = Validator::make($request->all(), [
            'student_id' => 'sometimes|exists:students,id',
            'invoice_id' => 'sometimes|exists:invoices,id',
            'amount' => 'sometimes|numeric|min:0',
            'payment_date' => 'sometimes|date',
            'method' => 'sometimes|string|in:Orange Money,Wave,Cash,check,bank_transfer,mobile',
            'reference' => 'sometimes|nullable|string|max:255',
            'status' => 'sometimes|string|in:pending,completed,failed',
            'school_year_id' => 'sometimes|exists:school_years,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $payment->update($request->all());
        return response()->json([
            'id' => $payment->id,
            'student_id' => $payment->student_id,
            'student_name' => $payment->student->first_name . ' ' . $payment->student->last_name,
            'invoice_id' => $payment->invoice_id,
            'invoice_amount' => $payment->invoice->amount ?? 0,
            'amount' => floatval($payment->amount),
            'payment_date' => $payment->payment_date,
            'method' => $payment->method,
            'reference' => $payment->reference,
            'status' => $payment->status,
            'school_year_id' => $payment->school_year_id,
            'created_at' => $payment->created_at->format('d/m/Y H:i')
        ], 200);
    }

    public function confirm($id)
    {
        $payment = Payment::find($id);

        if (!$payment) {
            return response()->json(['message' => 'Paiement non trouvé'], 404);
        }

        $payment->update(['status' => 'completed']);
        return response()->json([
            'id' => $payment->id,
            'student_id' => $payment->student_id,
            'student_name' => $payment->student->first_name . ' ' . $payment->student->last_name,
            'invoice_id' => $payment->invoice_id,
            'invoice_amount' => $payment->invoice->amount ?? 0,
            'amount' => floatval($payment->amount),
            'payment_date' => $payment->payment_date,
            'method' => $payment->method,
            'reference' => $payment->reference,
            'status' => $payment->status,
            'school_year_id' => $payment->school_year_id,
            'created_at' => $payment->created_at->format('d/m/Y H:i')
        ], 200);
    }

    public function destroy($id)
    {
        $payment = Payment::find($id);

        if (!$payment) {
            return response()->json(['message' => 'Paiement non trouvé'], 404);
        }

        $payment->delete();
        return response()->json(['message' => 'Paiement supprimé avec succès'], 200);
    }
}