<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Events\PaymentMade;

class PaymentController extends Controller
{


    public function index($student_id)
    {
        $payments = Payment::where('student_id', $student_id)->get();
        return response()->json($payments, 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'student_id' => 'required|exists:students,id',
            'amount' => 'required|numeric|min:0',
            'method' => 'required|in:Orange Money,Wave,Cash',
            'payment_date' => 'required|date',
            'transaction_id' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        event(new PaymentMade($payment));
        $payment = Payment::create(array_merge($request->all(), ['status' => 'pending']));
        return response()->json($payment, 201);
    }

    public function confirm($id)
    {
        $payment = Payment::findOrFail($id);
        $payment->update(['status' => 'completed']);
        return response()->json($payment, 200);
    }
    
    
    public function show($id)
    {
        $payment = Payment::with('student')->find($id);
        if (!$payment) {
            return response()->json(['message' => 'Paiement non trouvé'], 404);
        }
        return response()->json($payment);
    }

    public function update(Request $request, $id)
    {
        $payment = Payment::find($id);
        if (!$payment) {
            return response()->json(['message' => 'Paiement non trouvé'], 404);
        }

        $validator = Validator::make($request->all(), [
            'amount' => 'sometimes|required|numeric|min:0',
            'method' => 'sometimes|required|string|in:Orange Money,Wave,Cash',
            'status' => 'sometimes|required|string|in:pending,completed,failed',
            'transaction_id' => 'nullable|string',
            'payment_date' => 'sometimes|required|date',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $payment->update($request->all());
        return response()->json($payment);
    }

    public function destroy($id)
    {
        $payment = Payment::find($id);
        if (!$payment) {
            return response()->json(['message' => 'Paiement non trouvé'], 404);
        }

        $payment->delete();
        return response()->json(['message' => 'Paiement supprimé avec succès']);
    }
}
