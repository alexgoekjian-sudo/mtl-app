<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends BaseController
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 15);
        
        $query = AuditLog::with(['user', 'auditable']);
        
        if ($request->has('auditable_type')) {
            $query->where('auditable_type', $request->input('auditable_type'));
        }
        
        if ($request->has('auditable_id')) {
            $query->where('auditable_id', $request->input('auditable_id'));
        }
        
        if ($request->has('event')) {
            $query->where('event', $request->input('event'));
        }
        
        if ($request->has('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }
        
        $logs = $query->orderBy('created_at', 'desc')->paginate($perPage);
        return response()->json($logs);
    }

    public function show($id)
    {
        $log = AuditLog::with(['user', 'auditable'])->findOrFail($id);
        return response()->json($log);
    }

    // Note: AuditLogs are typically created by system middleware, not via API
    // Store method included for completeness but may not be exposed in routes
    public function store(Request $request)
    {
        $this->validate($request, [
            'auditable_type' => 'required|string|max:255',
            'auditable_id' => 'required|integer',
            'event' => 'required|string|max:255',
            'old_values' => 'nullable|array',
            'new_values' => 'nullable|array',
            'user_id' => 'nullable|exists:users,id',
            'ip_address' => 'nullable|string|max:45',
            'user_agent' => 'nullable|string',
        ]);

        $log = AuditLog::create($request->all());
        return response()->json($log, 201);
    }
}
