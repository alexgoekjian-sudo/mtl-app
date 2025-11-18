<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use App\Models\EmailLog;
use Illuminate\Http\Request;

class EmailLogController extends BaseController
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 15);
        
        $query = EmailLog::with('sentBy');
        
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }
        
        if ($request->has('template_name')) {
            $query->where('template_name', $request->input('template_name'));
        }
        
        if ($request->has('recipient_email')) {
            $query->where('recipient_email', 'LIKE', '%' . $request->input('recipient_email') . '%');
        }
        
        $logs = $query->orderBy('created_at', 'desc')->paginate($perPage);
        return response()->json($logs);
    }

    public function show($id)
    {
        $log = EmailLog::with('sentBy')->findOrFail($id);
        return response()->json($log);
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'recipient_email' => 'required|email',
            'recipient_name' => 'nullable|string|max:255',
            'subject' => 'required|string|max:255',
            'body_html' => 'nullable|string',
            'body_text' => 'nullable|string',
            'template_name' => 'nullable|string|max:255',
            'related_entity_type' => 'nullable|string|max:255',
            'related_entity_id' => 'nullable|integer',
            'status' => 'sometimes|in:pending,sent,failed',
            'sent_at' => 'nullable|date',
            'error_message' => 'nullable|string',
            'sent_by_user_id' => 'nullable|exists:users,id',
        ]);

        $log = EmailLog::create($request->all());
        return response()->json($log->load('sentBy'), 201);
    }

    public function update(Request $request, $id)
    {
        $log = EmailLog::findOrFail($id);

        $this->validate($request, [
            'status' => 'sometimes|in:pending,sent,failed',
            'sent_at' => 'nullable|date',
            'error_message' => 'nullable|string',
        ]);

        $log->update($request->all());
        return response()->json($log->load('sentBy'));
    }

    public function destroy($id)
    {
        $log = EmailLog::findOrFail($id);
        $log->delete();
        return response()->json(['status' => 'deleted']);
    }
}
