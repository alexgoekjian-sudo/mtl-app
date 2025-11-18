<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use App\Models\CourseOffering;

class CourseOfferingController extends BaseController
{
    public function index(Request $request)
    {
        $perPage = (int) $request->get('per_page', 15);
        $page = (int) $request->get('page', 1);
        $result = CourseOffering::paginate($perPage, ['*'], 'page', $page);
        return response()->json($result);
    }

    public function show($id)
    {
        $c = CourseOffering::find($id);
        if (!$c) return response()->json(['error' => 'Not found'], 404);
        return response()->json($c);
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'course_key' => 'required',
            'course_full_name' => 'required'
        ]);
        $data = $request->only(['course_key','course_full_name','level','program','start_date','end_date','hours_total','schedule','price','capacity','location','online']);
        $c = CourseOffering::create($data);
        return response()->json($c, 201);
    }

    public function update(Request $request, $id)
    {
        $c = CourseOffering::find($id);
        if (!$c) return response()->json(['error' => 'Not found'], 404);
        $data = $request->only(['course_key','course_full_name','level','program','start_date','end_date','hours_total','schedule','price','capacity','location','online']);
        $c->fill($data);
        $c->save();
        return response()->json($c);
    }

    public function destroy($id)
    {
        $c = CourseOffering::find($id);
        if (!$c) return response()->json(['error' => 'Not found'], 404);
        $c->delete();
        return response()->json(['status' => 'deleted']);
    }
}
