<?php
namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;

class HealthController extends BaseController
{
    public function index(Request $request)
    {
        return response()->json(['status' => 'ok']);
    }
}
