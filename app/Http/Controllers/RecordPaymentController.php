<?php
namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;

class RecordPaymentController extends BaseController
{
    /**
     * Handle a record-payment request from Retool.
     * Expected JSON body: { invoice_id: int, amount: decimal, method: string, external_reference?: string, idempotency_key?: string }
     */
    public function handle(Request $request)
    {
        $data = $request->only(['invoice_id','amount','method','external_reference','idempotency_key']);

        // Basic validation
        if (!isset($data['invoice_id']) || !isset($data['amount']) || !isset($data['method'])) {
            return response()->json(['error' => 'Missing parameters'], 422);
        }

        // TODO: implement idempotency checks and persist Payment to DB
        // This is a stub that immediately returns success for Retool development.

        return response()->json(['ok' => true, 'received' => $data]);
    }
}
