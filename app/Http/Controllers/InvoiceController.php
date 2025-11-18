<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use App\Models\Invoice;
use Illuminate\Http\Request;

class InvoiceController extends BaseController
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 15);
        
        $query = Invoice::with(['student', 'payments']);
        
        if ($request->has('student_id')) {
            $query->where('student_id', $request->input('student_id'));
        }
        
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }
        
        $invoices = $query->orderBy('issued_date', 'desc')->paginate($perPage);
        return response()->json($invoices);
    }

    public function show($id)
    {
        $invoice = Invoice::with(['student', 'payments'])->findOrFail($id);
        return response()->json($invoice);
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'billing_contact_id' => 'nullable|exists:students,id',
            'student_id' => 'required|exists:students,id',
            'items' => 'required|array',
            'total' => 'required|numeric|min:0',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'discount_reason' => 'nullable|string',
            'status' => 'sometimes|in:draft,sent,paid,overdue,cancelled',
            'issued_date' => 'nullable|date',
            'due_date' => 'nullable|date',
        ]);

        $data = $request->all();
        
        // Generate invoice number if not provided
        if (!isset($data['invoice_number'])) {
            $data['invoice_number'] = 'INV-' . date('Y') . '-' . str_pad(Invoice::count() + 1, 5, '0', STR_PAD_LEFT);
        }
        
        if (!isset($data['issued_date'])) {
            $data['issued_date'] = now();
        }

        $invoice = Invoice::create($data);
        return response()->json($invoice->load(['student', 'payments']), 201);
    }

    public function update(Request $request, $id)
    {
        $invoice = Invoice::findOrFail($id);

        $this->validate($request, [
            'items' => 'sometimes|array',
            'total' => 'sometimes|numeric|min:0',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'discount_reason' => 'nullable|string',
            'status' => 'sometimes|in:draft,sent,paid,overdue,cancelled',
            'issued_date' => 'nullable|date',
            'due_date' => 'nullable|date',
        ]);

        $invoice->update($request->all());
        return response()->json($invoice->load(['student', 'payments']));
    }

    public function destroy($id)
    {
        $invoice = Invoice::findOrFail($id);
        $invoice->delete();
        return response()->json(['status' => 'deleted']);
    }
}
