<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use App\Models\Student;

class StudentController extends BaseController
{
    public function index(Request $request)
    {
        $perPage = (int) $request->get('per_page', 15);
        $page = (int) $request->get('page', 1);
        $query = Student::query();
        $result = $query->paginate($perPage, ['*'], 'page', $page);
        return response()->json($result);
    }

    public function show($id)
    {
        $s = Student::find($id);
        if (!$s) return response()->json(['error' => 'Not found'], 404);
        return response()->json($s);
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'first_name' => 'required',
            'last_name' => 'required'
        ]);

        $data = $request->only(['lead_id','first_name','last_name','email','phone','initial_level','current_level','profile_notes']);
        $student = Student::create($data);
        return response()->json($student, 201);
    }

    public function update(Request $request, $id)
    {
        $s = Student::find($id);
        if (!$s) return response()->json(['error' => 'Not found'], 404);
        $data = $request->only(['lead_id','first_name','last_name','email','phone','initial_level','current_level','profile_notes']);
        $s->fill($data);
        $s->save();
        return response()->json($s);
    }

    public function destroy($id)
    {
        $s = Student::find($id);
        if (!$s) return response()->json(['error' => 'Not found'], 404);
        $s->delete();
        return response()->json(['status' => 'deleted']);
    }
}
