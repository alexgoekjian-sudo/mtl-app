<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use App\Models\User;

class AuthController extends BaseController
{
    public function login(Request $request)
    {
        $this->validate($request, [
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $user = User::where('email', $request->input('email'))->first();
        if (!$user) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        if (!password_verify($request->input('password'), $user->password)) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        // generate token
        $token = bin2hex(random_bytes(24));
        $user->api_token = $token;
        $user->api_token_created_at = date('Y-m-d H:i:s');
        $user->save();

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ]
        ]);
    }

    public function logout(Request $request)
    {
        $user = $request->get('auth_user') ?? $request->attributes->get('auth_user');
        if ($user) {
            $user->api_token = null;
            $user->api_token_created_at = null;
            $user->save();
        }
        return response()->json(['status' => 'ok']);
    }

    public function me(Request $request)
    {
        $user = $request->get('auth_user') ?? $request->attributes->get('auth_user');
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
        ]);
    }
}
