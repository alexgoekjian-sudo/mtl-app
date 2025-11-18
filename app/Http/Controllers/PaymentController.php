<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentController extends BaseController
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 15);
        
        $query = Payment::with('invoice');
        
        if ($request->has('invoice_id')) {
            $query->where('invoice_id', $request->input('invoice_id'));
        }
        
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }
        
        if ($request->has('is_refund')) {
            $query->where('is_refund', $request->boolean('is_refund'));
        }
        
        $payments = $query->orderBy('recorded_at', 'desc')->paginate($perPage);
        return response()->json($payments);
    }

    public function show($id)
    {
        $payment = Payment::with('invoice')->findOrFail($id);
        return response()->json($payment);
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'invoice_id' => 'required|exists:invoices,id',
            'amount' => 'required|numeric|min:0',
            'status' => 'sometimes|in:pending,completed,refunded,failed',
            'is_refund' => 'sometimes|boolean',
            'method' => 'sometimes|string|max:255',
            'external_reference' => 'nullable|string|max:255',
            'recorded_at' => 'nullable|date',
        ]);

        $data = $request->all();
        if (!isset($data['recorded_at'])) {
            $data['recorded_at'] = now();
        }

        $payment = Payment::create($data);
        
        // Update invoice status if payment is completed
        if ($payment->status === 'completed' && !$payment->is_refund) {
            $invoice = $payment->invoice;
            $totalPaid = $invoice->totalPaid();
            if ($totalPaid >= $invoice->total) {
                $invoice->update(['status' => 'paid']);
            }
        }
        
        return response()->json($payment->load('invoice'), 201);
    }

    public function update(Request $request, $id)
    {
        $payment = Payment::findOrFail($id);

        $this->validate($request, [
            'amount' => 'sometimes|numeric|min:0',
            'status' => 'sometimes|in:pending,completed,refunded,failed',
            'is_refund' => 'sometimes|boolean',
            'method' => 'sometimes|string|max:255',
            'external_reference' => 'nullable|string|max:255',
        ]);

        $payment->update($request->all());
        return response()->json($payment->load('invoice'));
    }

    public function destroy($id)
    {
        $payment = Payment::findOrFail($id);
        $payment->delete();
        return response()->json(['status' => 'deleted']);
    }
}
