<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use App\Models\Lead;
use Illuminate\Http\Request;

class LeadController extends BaseController
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 15);
        $leads = Lead::with('bookings')->orderBy('created_at', 'desc')->paginate($perPage);
        return response()->json($leads);
    }

    public function show($id)
    {
        $lead = Lead::with(['bookings', 'student'])->findOrFail($id);
        return response()->json($lead);
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'source' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'languages' => 'nullable|string',
            'activity_notes' => 'nullable|string',
        ]);

        $lead = Lead::create($request->all());
        return response()->json($lead, 201);
    }

    public function update(Request $request, $id)
    {
        $lead = Lead::findOrFail($id);

        $this->validate($request, [
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'source' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'languages' => 'nullable|string',
            'activity_notes' => 'nullable|string',
        ]);

        $lead->update($request->all());
        return response()->json($lead);
    }

    public function destroy($id)
    {
        $lead = Lead::findOrFail($id);
        $lead->delete();
        return response()->json(['status' => 'deleted']);
    }
}
