<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use App\Models\DiscountRule;
use Illuminate\Http\Request;

class DiscountRuleController extends BaseController
{
    public function index(Request $request)
    {
        $query = DiscountRule::query();
        
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }
        
        if ($request->has('rule_type')) {
            $query->where('rule_type', $request->input('rule_type'));
        }
        
        $rules = $query->orderBy('name')->get();
        return response()->json($rules);
    }

    public function show($id)
    {
        $rule = DiscountRule::findOrFail($id);
        return response()->json($rule);
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|string|max:255',
            'percent' => 'required|numeric|min:0|max:100',
            'rule_type' => 'required|string|max:255',
            'is_active' => 'sometimes|boolean',
            'description' => 'nullable|string',
        ]);

        $rule = DiscountRule::create($request->all());
        return response()->json($rule, 201);
    }

    public function update(Request $request, $id)
    {
        $rule = DiscountRule::findOrFail($id);

        $this->validate($request, [
            'name' => 'sometimes|string|max:255',
            'percent' => 'sometimes|numeric|min:0|max:100',
            'rule_type' => 'sometimes|string|max:255',
            'is_active' => 'sometimes|boolean',
            'description' => 'nullable|string',
        ]);

        $rule->update($request->all());
        return response()->json($rule);
    }

    public function destroy($id)
    {
        $rule = DiscountRule::findOrFail($id);
        $rule->delete();
        return response()->json(['status' => 'deleted']);
    }
}
