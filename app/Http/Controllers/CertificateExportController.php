<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use App\Models\CertificateExport;
use Illuminate\Http\Request;

class CertificateExportController extends BaseController
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 15);
        
        $query = CertificateExport::with(['student', 'courseOffering']);
        
        if ($request->has('eligible')) {
            if ($request->boolean('eligible')) {
                $query->where('eligible', true);
            }
        }
        
        if ($request->has('student_id')) {
            $query->where('student_id', $request->input('student_id'));
        }
        
        $exports = $query->orderBy('exported_at', 'desc')->paginate($perPage);
        return response()->json($exports);
    }

    public function show($id)
    {
        $export = CertificateExport::with(['student', 'courseOffering'])->findOrFail($id);
        return response()->json($export);
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'student_id' => 'required|exists:students,id',
            'course_offering_id' => 'required|exists:course_offerings,id',
            'attendance_percent' => 'required|numeric|min:0|max:100',
            'eligible' => 'sometimes|boolean',
            'exported_at' => 'nullable|date',
            'issued_at' => 'nullable|date',
            'certificate_url' => 'nullable|url',
        ]);

        $data = $request->all();
        
        // Auto-determine eligibility based on attendance
        if (!isset($data['eligible'])) {
            $data['eligible'] = $data['attendance_percent'] >= 80;
        }
        
        if (!isset($data['exported_at'])) {
            $data['exported_at'] = now();
        }

        $export = CertificateExport::create($data);
        return response()->json($export->load(['student', 'courseOffering']), 201);
    }

    public function update(Request $request, $id)
    {
        $export = CertificateExport::findOrFail($id);

        $this->validate($request, [
            'attendance_percent' => 'sometimes|numeric|min:0|max:100',
            'eligible' => 'sometimes|boolean',
            'issued_at' => 'nullable|date',
            'certificate_url' => 'nullable|url',
        ]);

        $export->update($request->all());
        return response()->json($export->load(['student', 'courseOffering']));
    }

    public function destroy($id)
    {
        $export = CertificateExport::findOrFail($id);
        $export->delete();
        return response()->json(['status' => 'deleted']);
    }
}
